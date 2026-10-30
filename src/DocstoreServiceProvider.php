<?php

namespace LaurentMeuwly\Docstore;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;
use LaurentMeuwly\Docstore\Services\AllowAllVisibilityResolver;

class DocstoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/docstore.php', 'docstore');

        $this->app->singleton(DocumentVisibilityResolver::class, function (Application $app): DocumentVisibilityResolver {
            $class = config('docstore.visibility.resolver', AllowAllVisibilityResolver::class);

            $resolver = $app->make($class);

            if (! $resolver instanceof DocumentVisibilityResolver) {
                throw new InvalidArgumentException(sprintf(
                    'The configured docstore visibility resolver [%s] must implement %s.',
                    is_string($class) ? $class : get_debug_type($class),
                    DocumentVisibilityResolver::class,
                ));
            }

            return $resolver;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/docstore.php' => config_path('docstore.php'),
            ], 'docstore-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_docstore_tables.php.stub' => $this->getMigrationFileName('create_docstore_tables.php'),
            ], 'docstore-migrations');

            $this->commands([
                Console\InstallCommand::class,
            ]);
        }

        if (config('docstore.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }

    /**
     * Return the already published migration if it exists, otherwise a new
     * timestamped path, so that re-publishing does not create duplicates.
     */
    protected function getMigrationFileName(string $migrationFileName): string
    {
        $directory = $this->app->databasePath('migrations');

        $existing = $this->app->make(Filesystem::class)
            ->glob($directory.DIRECTORY_SEPARATOR.'*_'.$migrationFileName);

        // glob() is documented as returning an array but may return false on
        // some systems; `??` covers both cases without a warning.
        return $existing[0] ?? $directory.DIRECTORY_SEPARATOR.date('Y_m_d_His').'_'.$migrationFileName;
    }
}

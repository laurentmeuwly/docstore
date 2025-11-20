<?php

namespace LaurentMeuwly\Docstore;

use Illuminate\Support\Facades\Route;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;
use LaurentMeuwly\Docstore\Services\AllowAllVisibilityResolver;

class DocstoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../config/docstore.php' => config_path('docstore.php'),
            ], 'docstore-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_docstore_tables.php.stub'
                    => $this->getMigrationFileName('create_docstore_tables.php'),
            ], 'docstore-migrations');

            $this->commands([
                Console\InstallCommand::class,
            ]);
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/docstore.php', 'docstore');

        $this->app->singleton(DocumentVisibilityResolver::class, function ($app) {
            $resolver = config('docstore.visibility.resolver', AllowAllVisibilityResolver::class);
            return $app->make($resolver);
        });
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     */
    protected function getMigrationFileName(string $migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        $filesystem = $this->app->make(Filesystem::class);

        return Collection::make([
                $this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR
            ])
            ->flatMap(fn ($path) => $filesystem->glob($path.'*_'.$migrationFileName))
            ->push($this->app->databasePath()."/migrations/{$timestamp}_{$migrationFileName}")
            ->first();
    }
}

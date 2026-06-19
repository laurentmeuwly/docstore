<?php

namespace LaurentMeuwly\Docstore\Tests;

use LaurentMeuwly\Docstore\DocstoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DocstoreServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('docstore.storage_disk', 'local');
        $app['config']->set('docstore.base_path', 'docstore');

        $app['config']->set(
            'auth.providers.users.model',
            \LaurentMeuwly\Docstore\Tests\Fixtures\User::class
        );
    }

    protected function defineDatabaseMigrations(): void
    {
        $migration = include __DIR__ . '/../database/migrations/create_docstore_tables.php.stub';

        $migration->up();
    }
}

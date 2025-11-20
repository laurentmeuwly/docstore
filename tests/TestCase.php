<?php

namespace LaurentMeuwly\Docstore\Tests;

use LaurentMeuwly\Docstore\DocstoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            DocstoreServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('docstore.storage_disk', 'local');
        $app['config']->set('docstore.base_path', 'docstore');

        // Auth user fixture
        $app['config']->set(
            'auth.providers.users.model',
            \LaurentMeuwly\Docstore\Tests\Fixtures\User::class
        );
    }
}

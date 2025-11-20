<?php

use Illuminate\Support\Facades\File;

it('can publish the migration', function () {
    $migrationPath = database_path('migrations');

    File::cleanDirectory($migrationPath);

    $this->artisan('vendor:publish', [
        '--tag' => 'docstore-migrations',
    ])->assertExitCode(0);

    $files = collect(File::files($migrationPath))
        ->map(fn ($f) => $f->getFilename());

    expect($files->join(','))->toContain('create_docstore_tables');
});

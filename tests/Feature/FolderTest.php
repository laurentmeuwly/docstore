<?php

use LaurentMeuwly\Docstore\Models\Folder;

it('can create a folder', function () {
    $folder = Folder::create(['name' => 'Test']);

    expect($folder)->toBeInstanceOf(Folder::class)
        ->and($folder->name)->toBe('Test');
});

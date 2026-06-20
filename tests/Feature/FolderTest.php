<?php

use LaurentMeuwly\Docstore\Models\Folder;
use LaurentMeuwly\Docstore\Tests\Fixtures\CustomFolder;

it('can create a folder', function () {
    $folder = Folder::create(['name' => 'Test']);

    expect($folder)->toBeInstanceOf(Folder::class)
        ->and($folder->name)->toBe('Test');
});

it('resolves parent() and children() to the model configured via docstore.models.folder', function () {
    config()->set('docstore.models.folder', CustomFolder::class);

    $root = CustomFolder::create(['name' => 'Root']);
    $child = CustomFolder::create(['name' => 'Child', 'parent_id' => $root->id]);

    expect($child->parent)->toBeInstanceOf(CustomFolder::class)
        ->and($root->children->first())->toBeInstanceOf(CustomFolder::class);
});

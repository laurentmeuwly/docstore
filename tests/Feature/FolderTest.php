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

it('orders children by position then id', function () {
    $root = Folder::create(['name' => 'Root']);
    Folder::create(['name' => 'B', 'parent_id' => $root->id, 'position' => 2]);
    Folder::create(['name' => 'A', 'parent_id' => $root->id, 'position' => 1]);

    expect($root->children->pluck('name')->all())->toBe(['A', 'B']);
});

it('builds the full path from the root', function () {
    $root = Folder::create(['name' => 'Root']);
    $sub = Folder::create(['name' => 'Sub', 'parent_id' => $root->id]);
    $leaf = Folder::create(['name' => 'Leaf', 'parent_id' => $sub->id]);

    expect($leaf->full_path)->toBe('Root / Sub / Leaf')
        ->and($root->full_path)->toBe('Root');
});

it('does not loop forever on a cyclic hierarchy', function () {
    $a = Folder::create(['name' => 'A']);
    $b = Folder::create(['name' => 'B', 'parent_id' => $a->id]);
    $a->update(['parent_id' => $b->id]);

    expect($a->fresh()->full_path)->toBe('B / A');
});

it('scopes root folders', function () {
    $root = Folder::create(['name' => 'Root']);
    Folder::create(['name' => 'Child', 'parent_id' => $root->id]);

    expect(Folder::root()->pluck('name')->all())->toBe(['Root']);
});

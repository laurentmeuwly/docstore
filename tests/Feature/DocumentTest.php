<?php

use LaurentMeuwly\Docstore\Models\Document;
use LaurentMeuwly\Docstore\Models\Folder;

it('can create a document', function () {
    $doc = Document::create([
        'title' => 'Test Doc',
        'filename' => 'test.pdf',
        'path' => 'docstore/test.pdf',
    ]);

    expect($doc)->toBeInstanceOf(Document::class)
        ->and($doc->title)->toBe('Test Doc');
});

it('generates a document url', function () {
    $doc = new Document;
    $doc->id = 12;

    expect($doc->url())->toEndWith('/docstore/12/download');
});

it('belongs to a folder', function () {
    $folder = Folder::create(['name' => 'Invoices']);

    $doc = Document::create([
        'folder_id' => $folder->id,
        'title' => 'Invoice',
        'filename' => 'invoice.pdf',
        'path' => 'docstore/invoice.pdf',
    ]);

    expect($doc->folder)->toBeInstanceOf(Folder::class)
        ->and($doc->folder->is($folder))->toBeTrue()
        ->and($folder->documents)->toHaveCount(1);
});

it('formats the document size', function (int $bytes, string $expected) {
    $doc = new Document(['size' => $bytes]);

    expect($doc->formatted_size)->toBe($expected);
})->with([
    [0, '0 B'],
    [512, '512 B'],
    [1536, '1.5 kB'],
    [5 * 1024 ** 2, '5 MB'],
    [3 * 1024 ** 3, '3 GB'],
]);

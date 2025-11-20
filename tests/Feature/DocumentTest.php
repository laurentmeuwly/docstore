<?php

use LaurentMeuwly\Docstore\Models\Document;

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
    $doc = new Document();
    $doc->id = 12;

    $url = $doc->url();

    expect($url)->toBeString();
    expect($url)->toContain((string) $doc->getKey());
    expect($url)->toContain('download');
});

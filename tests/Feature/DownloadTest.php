<?php

use Illuminate\Support\Facades\Storage;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;
use LaurentMeuwly\Docstore\Models\Document;
use LaurentMeuwly\Docstore\Tests\Fixtures\DenyAllResolver;
use LaurentMeuwly\Docstore\Tests\Fixtures\User;

beforeEach(function () {
    Storage::fake('local');
});

function makeStoredDocument(array $attributes = [], string $content = 'PDF CONTENT'): Document
{
    $document = Document::create(array_merge([
        'disk' => 'local',
        'title' => 'Doc',
        'filename' => 'doc.pdf',
        'mime_type' => 'application/pdf',
        'path' => 'docstore/doc.pdf',
    ], $attributes));

    Storage::disk($document->disk)->put($document->path, $content);

    return $document;
}

it('downloads a document as an attachment', function () {
    $document = makeStoredDocument();

    $response = $this->actingAs(new User)
        ->get(route('docstore.download', $document->id));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment')
        ->and($response->headers->get('Content-Disposition'))->toContain('doc.pdf')
        ->and($response->streamedContent())->toBe('PDF CONTENT');
});

it('serves PDF documents inline when requested', function () {
    $document = makeStoredDocument();

    $response = $this->actingAs(new User)
        ->get(route('docstore.download', ['id' => $document->id, 'inline' => 1]));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline');
});

it('never serves non-PDF documents inline', function () {
    $document = makeStoredDocument([
        'filename' => 'page.html',
        'mime_type' => 'text/html',
        'path' => 'docstore/page.html',
    ], '<script>alert(1)</script>');

    $response = $this->actingAs(new User)
        ->get(route('docstore.download', ['id' => $document->id, 'inline' => 1]));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment');
});

it('escapes special characters in the downloaded file name', function () {
    $document = makeStoredDocument(['filename' => 'Rapport "final" été.pdf']);

    $response = $this->actingAs(new User)
        ->get(route('docstore.download', $document->id));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))
        ->toStartWith('attachment')
        ->toContain("filename*=utf-8''Rapport%20%22final%22%20%C3%A9t%C3%A9.pdf");
});

it('returns 403 when the visibility resolver denies access', function () {
    $document = makeStoredDocument();

    app()->forgetInstance(DocumentVisibilityResolver::class);
    app()->bind(DocumentVisibilityResolver::class, DenyAllResolver::class);

    $this->actingAs(new User)
        ->get(route('docstore.download', $document->id))
        ->assertForbidden();
});

it('returns 404 when the document does not exist', function () {
    $this->actingAs(new User)
        ->get(route('docstore.download', 999))
        ->assertNotFound();
});

it('returns 404 when the file is missing from the disk', function () {
    $document = Document::create([
        'disk' => 'local',
        'title' => 'Ghost',
        'filename' => 'ghost.pdf',
        'path' => 'docstore/ghost.pdf',
    ]);

    $this->actingAs(new User)
        ->get(route('docstore.download', $document->id))
        ->assertNotFound();
});

it('requires authentication', function () {
    $document = makeStoredDocument();

    $this->getJson(route('docstore.download', $document->id))
        ->assertUnauthorized();
});

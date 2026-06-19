<?php

use Illuminate\Support\Facades\Storage;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;
use LaurentMeuwly\Docstore\Models\Document;
use LaurentMeuwly\Docstore\Tests\Fixtures\DenyAllResolver;
use LaurentMeuwly\Docstore\Tests\Fixtures\User;

it('denies download without resolver', function () {
    Storage::fake('local');

    $doc = Document::create([
        'disk' => 'local',
        'title' => 'Doc',
        'filename' => 'doc.pdf',
        'path' => 'docstore/doc.pdf',
    ]);

    Storage::disk('local')->put('docstore/doc.pdf', 'PDF CONTENT');

    // Use deny-all resolver for this test
    config()->set('docstore.visibility.resolver', DenyAllResolver::class);
    app()->forgetInstance(DocumentVisibilityResolver::class);

    app()->bind(
        DocumentVisibilityResolver::class,
        DenyAllResolver::class
    );

    $this->actingAs(new User);

    $this->get(route('docstore.download', $doc->id))
        ->assertStatus(403);
});

<?php

namespace LaurentMeuwly\Docstore\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;
use LaurentMeuwly\Docstore\Models\Document;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController
{
     public function __construct(
        protected DocumentVisibilityResolver $resolver
    ) {}

    public function __invoke(string|int $id): StreamedResponse
    {
        $model = config('docstore.models.document', Document::class);

        /** @var Document $document */
        $document = $model::findOrFail($id);

        if (! $this->resolver->canAccess($document, auth()->user())) {
            abort(403);
        }

        $disk = $document->disk ?: config('docstore.storage_disk');

        if (! Storage::disk($disk)->exists($document->path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk($disk)
            ->download(
                $document->path,
                $document->filename
            );
    }
}

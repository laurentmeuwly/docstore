<?php

namespace LaurentMeuwly\Docstore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;
use LaurentMeuwly\Docstore\Models\Document;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController
{
    public function __construct(
        protected DocumentVisibilityResolver $resolver
    ) {}

    public function __invoke(Request $request, string|int $id): StreamedResponse
    {
        $model = config('docstore.models.document', Document::class);

        /** @var Document $document */
        $document = $model::findOrFail($id);

        if (! $this->resolver->canAccess($document, $request->user())) {
            abort(403);
        }

        $disk = $document->disk ?: config('docstore.storage_disk');
        $path = $document->path;

        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'Document file not found.');
        }

        $mimeType = $document->mime_type ?: 'application/octet-stream';
        $inline = $request->boolean('inline') && $mimeType === 'application/pdf';

        return Storage::disk($disk)->response(
            $path,
            $document->filename,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$document->filename.'"',
            ]
        );
    }
}

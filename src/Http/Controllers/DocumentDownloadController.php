<?php

namespace LaurentMeuwly\Docstore\Http\Controllers;

use Illuminate\Filesystem\FilesystemAdapter;
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
        /** @var class-string<Document> $model */
        $model = Document::model();

        $document = $model::query()->findOrFail($id);

        if (! $this->resolver->canAccess($document, $request->user())) {
            abort(403);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($document->disk ?: config('docstore.storage_disk'));

        if (! $disk->exists($document->path)) {
            abort(404, 'Document file not found.');
        }

        $mimeType = $document->mime_type ?: 'application/octet-stream';
        $inline = $request->boolean('inline') && $mimeType === 'application/pdf';

        // Let the filesystem adapter build the Content-Disposition header
        // (Symfony HeaderUtils): it escapes quotes and handles non-ASCII
        // names. Path separators are not allowed in a disposition file name.
        $filename = basename(str_replace('\\', '/', $document->filename ?: $document->path));

        return $disk->response(
            $document->path,
            $filename,
            [
                'Content-Type' => $mimeType,
                'X-Content-Type-Options' => 'nosniff',
            ],
            $inline ? 'inline' : 'attachment',
        );
    }
}

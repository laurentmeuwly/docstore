<?php

namespace LaurentMeuwly\Docstore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        if (! $this->resolver->canAccess($document, auth()->user())) {
            abort(403);
        }

        $disk = $document->disk ?: config('docstore.storage_disk');

        // Build the real file path: directory/uuid.extension
        // The `path` column stores the folder path; the actual file is path/uuid.ext
        $fullPath = $document->path . '/' . $document->uuid . '.' . $document->extension;

        // ── Diagnostic block ────────────────────────────────────────────────
        // Temporary, verbose logging to pin down the exact failure point of
        // "Unable to retrieve the file_size" — affects every document, so the
        // cause is environmental (disk root, permissions, adapter config)
        // rather than per-document data. Once root-caused, this block (and
        // the try/catch below) can be trimmed back down.
        $absolutePath = null;
        try {
            $absolutePath = Storage::disk($disk)->path($fullPath);
        } catch (\Throwable $e) {
            Log::warning('[docstore.download] could not resolve absolute path', [
                'disk'  => $disk,
                'path'  => $fullPath,
                'error' => $e->getMessage(),
            ]);
        }

        $diagnostics = [
            'document_id'      => $document->id,
            'disk'             => $disk,
            'relative_path'    => $fullPath,
            'absolute_path'    => $absolutePath,
            'exists_flysystem' => null,
            'is_readable_php'  => $absolutePath ? is_readable($absolutePath) : null,
            'file_exists_php'  => $absolutePath ? file_exists($absolutePath) : null,
            'filesize_php'     => ($absolutePath && file_exists($absolutePath)) ? @filesize($absolutePath) : null,
            'php_user'         => function_exists('posix_getpwuid') && function_exists('posix_geteuid')
                ? (posix_getpwuid(posix_geteuid())['name'] ?? null)
                : null,
            'file_owner'       => ($absolutePath && file_exists($absolutePath) && function_exists('posix_getpwuid'))
                ? (posix_getpwuid(fileowner($absolutePath))['name'] ?? fileowner($absolutePath))
                : null,
            'file_perms'       => ($absolutePath && file_exists($absolutePath))
                ? substr(sprintf('%o', fileperms($absolutePath)), -4)
                : null,
        ];

        try {
            $diagnostics['exists_flysystem'] = Storage::disk($disk)->exists($fullPath);
        } catch (\Throwable $e) {
            $diagnostics['exists_flysystem_error'] = $e->getMessage();
        }

        Log::info('[docstore.download] diagnostics', $diagnostics);
        // ── End diagnostic block ────────────────────────────────────────────

        if (! Storage::disk($disk)->exists($fullPath)) {
            abort(404, 'Document file not found.');
        }

        $inline = $request->boolean('inline') && $document->mime_type === 'application/pdf';

        $headers = [
            'Content-Type'        => $document->mime_type,
            'Content-Disposition' => ($inline ? 'inline' : 'attachment')
                . '; filename="' . $document->filename . '"',
        ];

        try {
            return Storage::disk($disk)->response($fullPath, $document->filename, $headers);
        } catch (\Throwable $e) {
            Log::error('[docstore.download] Storage::response() failed', [
                'disk'        => $disk,
                'path'        => $fullPath,
                'exception'   => get_class($e),
                'message'     => $e->getMessage(),
                'diagnostics' => $diagnostics,
            ]);

            throw $e;
        }
    }
}

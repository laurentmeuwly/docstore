<?php

namespace LaurentMeuwly\Docstore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $folder_id
 * @property string $title
 * @property string $filename
 * @property string|null $mime_type
 * @property string|null $disk
 * @property string $path
 * @property array<string, mixed>|null $meta
 * @property int|null $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Folder|null $folder
 * @property-read string $formatted_size
 * @property-read string|null $formatted_date
 */
class Document extends Model
{
    protected $fillable = [
        'folder_id',
        'title',
        'filename',
        'mime_type',
        'disk',
        'path',
        'meta',
        'size',
    ];

    protected $casts = [
        'meta' => 'array',
        'size' => 'integer',
    ];

    public static function model(): string
    {
        return config('docstore.models.document', self::class);
    }

    /**
     * @return BelongsTo<Folder, self>
     */
    public function folder(): BelongsTo
    {
        $folderClass = config('docstore.models.folder', Folder::class);

        /** @var BelongsTo<Folder, self> $relation */
        $relation = $this->belongsTo($folderClass);

        return $relation;
    }

    /**
     * Return the download URL.
     */
    public function url(): string
    {
        return route('docstore.download', ['id' => $this->getKey()]);
    }

    /**
     * Formatted size (B, kB, MB, GB).
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = max(0, (int) ($this->size ?? 0));

        return match (true) {
            $bytes < 1024 => $bytes.' B',
            $bytes < 1024 ** 2 => round($bytes / 1024, 2).' kB',
            $bytes < 1024 ** 3 => round($bytes / 1024 ** 2, 2).' MB',
            default => round($bytes / 1024 ** 3, 2).' GB',
        };
    }

    /**
     * Formatted date of last update.
     */
    public function getFormattedDateAttribute(): ?string
    {
        return $this->updated_at?->format('d.m.Y H:i');
    }
}

<?php

namespace LaurentMeuwly\Docstore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property int|null $position
 *
 * @property-read Folder|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Folder> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Document> $documents
 */
class Folder extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'position'
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'position' => 'integer',
    ];

    public static function model(): string
    {
        return config('docstore.models.folder', self::class);
    }

    public function parent(): BelongsTo
    {
        $model = static::model();

        return $this->belongsTo($model, 'parent_id');
    }

    public function children(): HasMany
    {
        $model = static::model();

        return $this->hasMany($model, 'parent_id')
            ->orderBy('position')
            ->orderBy('id');
    }

    public function documents(): HasMany
    {
        $documentClass = config('docstore.models.document', Document::class);

        return $this->hasMany($documentClass);
    }

    public function getFullPathAttribute(): string
    {
        if (!$this->parent) {
            return $this->name;
        }

        return $this->parent->full_path . ' / ' . $this->name;
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}

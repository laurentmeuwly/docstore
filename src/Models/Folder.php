<?php

namespace LaurentMeuwly\Docstore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property int|null $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Folder|null $parent
 * @property-read Collection<int, Folder> $children
 * @property-read Collection<int, Document> $documents
 * @property-read string $full_path
 */
class Folder extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'position',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'position' => 'integer',
    ];

    public static function model(): string
    {
        return config('docstore.models.folder', self::class);
    }

    /**
     * @return BelongsTo<Folder, self>
     */
    public function parent(): BelongsTo
    {
        $model = static::model();

        /** @var BelongsTo<Folder, self> $relation */
        $relation = $this->belongsTo(self::class, 'parent_id');

        return $relation;
    }

    /**
     * @return HasMany<Folder, self>
     */
    public function children(): HasMany
    {
        $model = static::model();

        /** @var HasMany<Folder, self> $relation */
        $relation = $this->hasMany(self::class, 'parent_id')
            ->orderBy('position')
            ->orderBy('id');

        return $relation;
    }

    /**
     * @return HasMany<Document, self>
     */
    public function documents(): HasMany
    {
        $documentClass = config('docstore.models.document', Document::class);

        /** @var HasMany<Document, self> $relation */
        $relation = $this->hasMany($documentClass);

        return $relation;
    }

    public function getFullPathAttribute(): string
    {
        if (! $this->parent) {
            return $this->name;
        }

        return $this->parent->full_path.' / '.$this->name;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}

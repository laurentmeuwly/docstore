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
    /**
     * Explicit table name so that subclasses with another class name
     * (e.g. App\Models\ProjectFolder) keep using the package table.
     */
    protected $table = 'folders';

    protected $fillable = [
        'parent_id',
        'name',
        'position',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'position' => 'integer',
    ];

    /**
     * The folder model class configured in `docstore.models.folder`.
     *
     * @return class-string<Folder>
     */
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
        $relation = $this->belongsTo($model, 'parent_id');

        return $relation;
    }

    /**
     * @return HasMany<Folder, self>
     */
    public function children(): HasMany
    {
        $model = static::model();

        /** @var HasMany<Folder, self> $relation */
        $relation = $this->hasMany($model, 'parent_id')
            ->orderBy('position')
            ->orderBy('id');

        return $relation;
    }

    /**
     * @return HasMany<Document, self>
     */
    public function documents(): HasMany
    {
        /** @var HasMany<Document, self> $relation */
        $relation = $this->hasMany(Document::model(), 'folder_id');

        return $relation;
    }

    /**
     * Human-readable path from the root folder, e.g. "Root / Sub / Leaf".
     *
     * Note: each ancestor is loaded lazily (one query per level unless the
     * `parent` relations are eager-loaded). A cycle in the hierarchy is
     * detected and stops the traversal instead of recursing forever.
     */
    public function getFullPathAttribute(): string
    {
        $segments = [$this->name];
        $visited = [$this->getKey() => true];
        $current = $this->parent;

        while ($current !== null && ! isset($visited[$current->getKey()])) {
            $visited[$current->getKey()] = true;
            array_unshift($segments, $current->name);
            $current = $current->parent;
        }

        return implode(' / ', $segments);
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

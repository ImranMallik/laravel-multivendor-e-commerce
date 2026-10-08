<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Keeps `slug` in step with `name`, unique inside the model's scope.
 *
 * The slug is generated on create and regenerated when the name (or the parent that defines the
 * scope) changes. Soft-deleted rows are included in the uniqueness check because the database
 * unique index still covers them.
 */
trait GeneratesSlug
{
    /**
     * Column and value that bound uniqueness, or null when the slug is globally unique.
     *
     * @return array{0: string, 1: mixed}|null
     */
    abstract protected function slugScope(): ?array;

    protected static function bootGeneratesSlug(): void
    {
        static::saving(function ($model) {
            $scope = $model->slugScope();

            if (blank($model->slug) || $model->isDirty('name') || ($scope && $model->isDirty($scope[0]))) {
                $model->slug = $model->makeUniqueSlug();
            }
        });
    }

    public function makeUniqueSlug(): string
    {
        $base = Str::slug((string) $this->name) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($this->slugTaken($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function slugTaken(string $slug): bool
    {
        $scope = $this->slugScope();

        return static::withTrashed()
            ->where('slug', $slug)
            ->when($scope, fn ($query) => $query->where($scope[0], $scope[1]))
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();
    }
}

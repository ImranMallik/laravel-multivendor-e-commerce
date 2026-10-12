<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Saves attributes with their values (admin only) and guards the ones variants depend on.
 */
class AttributeService
{
    /**
     * Creates or updates an attribute and syncs its values in one transaction. The posted order is the
     * display order. Values that are no longer posted are deleted, unless a variant uses them.
     *
     * @param  array{name: string, type: string, sort_order?: int|null, is_active: bool, values: array<int, array{id: int|null, value: string, color_code: string|null, is_active: bool}>}  $data
     *
     * @throws DomainException when a removed value is used by a variant
     */
    public function save(array $data, ?Attribute $attribute = null): Attribute
    {
        return DB::transaction(function () use ($data, $attribute) {
            $attribute ??= new Attribute;
            $attribute->fill([
                'name' => $data['name'],
                'type' => $data['type'],
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'],
            ])->save();

            $kept = [];

            foreach (array_values($data['values']) as $position => $row) {
                // An id from another attribute is ignored (a new value is created instead).
                $value = $row['id'] ? $attribute->values()->whereKey($row['id'])->first() : null;
                $value ??= new AttributeValue;

                $value->fill([
                    'value' => $row['value'],
                    'color_code' => $row['color_code'],
                    'sort_order' => $position,
                    'is_active' => $row['is_active'],
                ]);
                $attribute->values()->save($value);

                $kept[] = $value->id;
            }

            foreach ($attribute->values()->whereNotIn('id', $kept)->get() as $value) {
                if ($value->isInUse()) {
                    throw new DomainException('"'.$value->value.'" is used by product variants and cannot be removed. Mark it inactive instead.');
                }

                $value->delete();
            }

            return $attribute->load('values');
        });
    }

    public function toggleActive(Attribute $attribute): Attribute
    {
        $attribute->is_active = ! $attribute->is_active;
        $attribute->save();

        return $attribute;
    }

    /**
     * @throws DomainException when a variant uses one of the attribute's values
     */
    public function delete(Attribute $attribute): void
    {
        if ($attribute->isInUse()) {
            throw new DomainException('This attribute is used by product variants and cannot be deleted. Mark it inactive instead.');
        }

        $attribute->delete(); // its values go with it (cascade)
    }
}

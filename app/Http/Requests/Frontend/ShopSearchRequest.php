<?php

namespace App\Http\Requests\Frontend;

use App\Enums\ProductSort;
use App\Support\SearchTerm;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Query string of the shop page: ?search=&sort=&category=&brand= (all optional, shareable URLs).
 * A long search is cut to 100 characters (SearchTerm) rather than rejected; an unknown sort, category or brand
 * falls back to the default instead of an error page.
 */
class ShopSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'sort' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:200'],
            'brand' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function term(): SearchTerm
    {
        return SearchTerm::from($this->validated('search'));
    }

    public function sort(bool $searching): ProductSort
    {
        $sort = ProductSort::tryFrom((string) $this->validated('sort'));

        return $sort && in_array($sort, ProductSort::options($searching), true) ? $sort : ProductSort::default($searching);
    }

    public function categorySlug(): ?string
    {
        return $this->validated('category');
    }

    public function brandSlug(): ?string
    {
        return $this->validated('brand');
    }
}

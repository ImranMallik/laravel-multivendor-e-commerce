<?php

namespace App\Http\Requests\Frontend;

use App\Support\SearchTerm;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Live suggestion query: ?q=&category= (category = top-level category slug, optional).
 * The term is cleaned and cut to 100 characters by SearchTerm; absurdly long input is rejected.
 */
class SearchSuggestionRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function term(): SearchTerm
    {
        return SearchTerm::from($this->validated('q'));
    }

    public function categorySlug(): ?string
    {
        return $this->validated('category');
    }
}

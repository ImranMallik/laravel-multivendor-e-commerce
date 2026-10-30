<?php

namespace App\View\Composers;

use App\Support\SearchTerm;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The header and mobile menu search boxes keep the current ?search= term (cleaned; an array in the query string
 * is ignored instead of breaking the page).
 */
class SearchFormComposer
{
    public function __construct(private readonly Request $request) {}

    public function compose(View $view): void
    {
        $search = $this->request->query('search');

        $view->with('searchTerm', SearchTerm::from(is_string($search) ? $search : null)->value);
    }
}

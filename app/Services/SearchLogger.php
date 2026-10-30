<?php

namespace App\Services;

use App\Models\SearchLog;
use App\Support\SearchTerm;

/**
 * Records submitted storefront searches (results page only, never live suggestions) for the admin
 * "Search Terms" report. The row is written after the response has been sent, so searching stays fast.
 */
class SearchLogger
{
    public function record(SearchTerm $term, int $resultsCount, ?int $userId = null): void
    {
        if ($term->isEmpty()) {
            return;
        }

        $attributes = ['term' => $term->normalized(), 'results_count' => $resultsCount, 'user_id' => $userId];
        $written = false;

        // Terminating callbacks stay registered for the life of the application, which can serve several
        // requests (tests, long-running workers): the flag keeps this row from being written again later.
        app()->terminating(function () use ($attributes, &$written) {
            if (! $written) {
                $written = true;
                SearchLog::create($attributes);
            }
        });
    }
}

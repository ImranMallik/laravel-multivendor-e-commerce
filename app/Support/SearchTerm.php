<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * A cleaned storefront search term: trimmed, runs of whitespace collapsed to one space, at most 100 characters.
 * An empty term means "no search". Only ProductSearchService and the search log read it.
 */
final class SearchTerm
{
    public const MAX_LENGTH = 100;

    /** Shortest term the FULLTEXT index can match (InnoDB innodb_ft_min_token_size defaults to 3). */
    public const FULLTEXT_MIN_LENGTH = 3;

    /**
     * LIKE escape character, used as "... LIKE ? ESCAPE '!'". Not the backslash: MySQL and SQLite read a
     * backslash in a string literal differently, "!" means the same everywhere.
     */
    public const LIKE_ESCAPE = '!';

    private function __construct(public readonly string $value) {}

    public static function from(?string $input): self
    {
        $clean = trim((string) preg_replace('/\s+/u', ' ', (string) $input));

        return new self(rtrim(Str::substr($clean, 0, self::MAX_LENGTH)));
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function length(): int
    {
        return Str::length($this->value);
    }

    /**
     * Lowercase form used to group the same search in the admin report.
     */
    public function normalized(): string
    {
        return Str::lower($this->value);
    }

    /**
     * "%term%" with LIKE wildcards and the escape character escaped, so % and _ match themselves
     * (use with ESCAPE '!').
     */
    public function contains(): string
    {
        return '%'.$this->escaped().'%';
    }

    /**
     * "term%" (escaped), for "name starts with the term".
     */
    public function startsWith(): string
    {
        return $this->escaped().'%';
    }

    public function escaped(): string
    {
        $e = self::LIKE_ESCAPE;

        return str_replace([$e, '%', '_'], [$e.$e, $e.'%', $e.'_'], $this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

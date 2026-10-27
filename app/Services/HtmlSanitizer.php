<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Cleans rich-text HTML (product descriptions written in Summernote) before it is saved:
 * scripts, event handlers, iframes, forms and unsafe URLs are removed; ordinary formatting stays.
 */
class HtmlSanitizer
{
    /** Template classes allowed in rich text: the blog quote box (div.wsus__blog_quot_area with i.far.fa-quote-left). */
    public const ALLOWED_CLASSES = ['wsus__blog_quot_area', 'far', 'fa-quote-left'];

    private ?HTMLPurifier $purifier = null;

    public function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = trim($this->purifier()->purify($html));

        return $clean === '' ? null : $clean;
    }

    private function purifier(): HTMLPurifier
    {
        if ($this->purifier) {
            return $this->purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'hr', 'div[class]', 'span[style]', 'b', 'strong', 'i[class]', 'em', 'u', 's', 'strike', 'sub', 'sup',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'code',
            'ul', 'ol', 'li', 'a[href|title|target]', 'img[src|alt|width|height]',
            'table', 'thead', 'tbody', 'tr', 'th[colspan|rowspan]', 'td[colspan|rowspan]',
        ]));
        // Only the storefront template's own content classes survive (the blog quote box), nothing else.
        $config->set('Attr.AllowedClasses', self::ALLOWED_CLASSES);
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'text-align', 'font-weight', 'font-style', 'text-decoration']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('AutoFormat.RemoveEmpty', true);
        // Keep empty table cells (HTMLPurifier default) and the quote box icon (<i class="far fa-quote-left"></i>).
        $config->set('AutoFormat.RemoveEmpty.Predicate', ['colgroup' => [], 'th' => [], 'td' => [], 'iframe' => ['src'], 'i' => ['class']]);

        $cache = storage_path('framework/cache/htmlpurifier');

        if (! is_dir($cache)) {
            @mkdir($cache, 0755, true);
        }

        $config->set('Cache.SerializerPath', $cache);

        return $this->purifier = new HTMLPurifier($config);
    }
}

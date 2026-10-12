<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vendor product approval
    |--------------------------------------------------------------------------
    |
    | When true, products created by vendors start as Pending, and editing the important fields
    | (name, prices, descriptions, images, category) of an approved vendor product sends it back
    | to Pending. When false, vendor products are approved straight away.
    |
    */

    'vendor_product_approval' => (bool) env('VENDOR_PRODUCT_APPROVAL', true),

    // Shown before prices in the store.
    'currency_symbol' => '₹',

    // Products created within this many days get the "New" badge.
    'new_badge_days' => 14,

    // Gallery images per product (the thumbnail is separate).
    'max_gallery_images' => 8,

    // Specification rows per product (the "Information" tab).
    'max_specifications' => 30,

    // How many products each home page section shows.
    'home_section_limit' => 12,

    // Home page category sections ("Consumer Electronics", "Apparels & Clothings"): category slugs, in order.
    // TODO: move to an admin settings screen.
    'home_category_sections' => ['electronics', 'fashion'],

];

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // When true, price / offer_price / stock_qty on the product are a mirror of its variants
            // (kept in step by ProductVariantService) and the variants are the source of truth.
            $table->boolean('has_variants')->default(false)->after('stock_qty')->index();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('has_variants');
        });
    }
};

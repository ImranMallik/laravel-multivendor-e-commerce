<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('combination_hash', 40);            // sha1 of the sorted attribute value ids
            $table->decimal('price', 12, 2);
            $table->decimal('offer_price', 12, 2)->nullable(); // runs in the product's offer dates
            $table->unsignedInteger('stock_qty')->default(0);
            $table->string('image')->nullable();               // relative path on the public disk
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();                              // never hard-deleted: orders will reference variants

            // Soft-deleted variants keep their hash, so a removed combination is restored, not duplicated.
            $table->unique(['product_id', 'combination_hash']);
            $table->index(['product_id', 'is_active']);
        });

        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->restrictOnDelete();

            $table->primary(['product_variant_id', 'attribute_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
    }
};

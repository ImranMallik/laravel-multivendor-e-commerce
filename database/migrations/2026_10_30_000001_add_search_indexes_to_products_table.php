<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product search (ProductSearchService): a FULLTEXT index on name + short description for natural language
 * matching (MySQL / MariaDB only; other drivers fall back to LIKE), and an index for the visibility check
 * every storefront query runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'approval_status'], 'products_status_approval_index');

            if ($this->supportsFullText()) {
                $table->fullText(['name', 'short_description'], 'products_search_fulltext');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if ($this->supportsFullText()) {
                $table->dropFullText('products_search_fulltext');
            }

            $table->dropIndex('products_status_approval_index');
        });
    }

    private function supportsFullText(): bool
    {
        return Schema::getConnection()->getDriverName() === 'mysql';
    }
};

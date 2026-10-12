<?php

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // NULL = in-house (admin) product. A vendor with products cannot be hard deleted.
            $table->foreignId('vendor_id')->nullable()->constrained()->restrictOnDelete();
            $table->nullableMorphs('created_by'); // App\Models\Admin or App\Models\User (audit)

            $table->string('name', 200);
            $table->string('slug')->unique();
            $table->string('sku', 100)->unique();

            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('child_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            $table->string('thumbnail');                       // relative path on the public disk
            $table->string('short_description', 500)->nullable();
            $table->longText('long_description')->nullable();  // sanitized HTML

            $table->decimal('price', 12, 2);
            $table->decimal('offer_price', 12, 2)->nullable();
            $table->date('offer_start_date')->nullable();
            $table->date('offer_end_date')->nullable()->index(); // inclusive; flash sale ordering
            $table->unsignedInteger('stock_qty')->default(0);

            $table->string('video_url')->nullable();
            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 500)->nullable();

            $table->string('status')->default(ProductStatus::Draft->value)->index();
            $table->string('approval_status')->default(ProductApprovalStatus::Pending->value)->index();
            $table->text('rejection_reason')->nullable();

            // Home page sections (admin only).
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_top')->default(false)->index();
            $table->boolean('is_best')->default(false)->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

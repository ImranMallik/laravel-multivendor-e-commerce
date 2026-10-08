<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('top_text', 100)->nullable();      // small kicker above the title (<h3>)
            $table->string('title', 150);                     // main heading (<h1>)
            $table->string('offer_text', 150)->nullable();    // offer line under the title (<h6>)
            $table->string('button_text', 50)->nullable();
            $table->string('button_url')->nullable();
            $table->string('image');                          // relative path on the public disk
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};

<?php

use App\Enums\VendorSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('source')->default(VendorSource::Application->value)->index()->after('status');
            $table->foreignId('created_by')->nullable()->after('source')->constrained('admins')->nullOnDelete();
            $table->timestamp('applied_at')->nullable()->after('approved_at');
            $table->foreignId('reviewed_by')->nullable()->after('applied_at')->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        // Shop addresses may be longer than 255 characters (the form allows 500). SQLite (tests) has no length limit.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE vendors MODIFY address TEXT NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['source', 'applied_at', 'reviewed_at']);
        });

        // The address column is left as TEXT: shrinking it back could cut off saved addresses.
    }
};

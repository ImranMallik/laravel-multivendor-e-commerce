<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vendor access now depends on the vendors record, not on users.role.
 *
 * - Every existing vendor gets applied_at (its creation time) and a first status log entry.
 * - Users that own a shop become plain "customer" accounts; their shop keeps working through the record.
 *   (The role column and enum are kept, but nothing reads them for access.)
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('vendors')->orderBy('id')->each(function ($vendor) use ($now) {
            DB::table('vendors')->where('id', $vendor->id)->update([
                'applied_at' => $vendor->applied_at ?? $vendor->created_at,
                'reviewed_at' => $vendor->reviewed_at ?? ($vendor->status === 'pending' ? null : $vendor->approved_at),
            ]);

            $hasLog = DB::table('vendor_status_logs')->where('vendor_id', $vendor->id)->exists();

            if (! $hasLog) {
                DB::table('vendor_status_logs')->insert([
                    'vendor_id' => $vendor->id,
                    'from_status' => null,
                    'to_status' => $vendor->status,
                    'reason' => $vendor->status === 'rejected' ? $vendor->rejection_reason : null,
                    'admin_id' => null,
                    'note' => 'Backfilled when status history was introduced',
                    'created_at' => $vendor->approved_at ?? $vendor->updated_at ?? $now,
                ]);
            }
        });

        DB::table('users')
            ->where('role', 'vendor')
            ->whereIn('id', DB::table('vendors')->select('user_id'))
            ->update(['role' => 'customer']);

        // A "vendor" account without any shop record cannot sell either; make it a customer too.
        DB::table('users')->where('role', 'vendor')->update(['role' => 'customer']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'customer')
            ->whereIn('id', DB::table('vendors')->select('user_id'))
            ->update(['role' => 'vendor']);

        DB::table('vendor_status_logs')->where('note', 'Backfilled when status history was introduced')->delete();
    }
};

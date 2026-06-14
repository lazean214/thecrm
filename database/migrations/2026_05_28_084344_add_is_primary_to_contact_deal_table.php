<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_deal', function (Blueprint $table) {
            $table->boolean('is_primary')
                ->default(false)
                ->after('contact_id');
        });

        /**
         * Set first contact
         * as primary for old records
         */
        $deals = DB::table('contact_deal')
            ->select('deal_id')
            ->distinct()
            ->pluck('deal_id');

        foreach ($deals as $dealId) {
            $first = DB::table('contact_deal')
                ->where('deal_id', $dealId)
                ->first();

            if ($first) {
                DB::table('contact_deal')
                    ->where('deal_id', $dealId)
                    ->where('contact_id', $first->contact_id)
                    ->update([
                        'is_primary' => true,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('contact_deal', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};

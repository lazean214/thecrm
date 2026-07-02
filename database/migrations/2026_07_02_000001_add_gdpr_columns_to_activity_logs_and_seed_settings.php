<?php

use App\Models\GdprSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->timestamp('anonymised_at')->nullable()->after('status');
        });

        GdprSetting::insert([
            [
                'entity_type' => 'activity_logs',
                'retention_months' => 24,
                'is_enabled' => true,
                'custom_action' => 'anonymize',
            ],
            [
                'entity_type' => 'deal_histories',
                'retention_months' => 84,
                'is_enabled' => true,
                'custom_action' => 'anonymize',
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('anonymised_at');
        });

        GdprSetting::whereIn('entity_type', ['activity_logs', 'deal_histories'])->delete();
    }
};

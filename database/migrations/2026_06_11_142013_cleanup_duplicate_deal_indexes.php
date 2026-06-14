<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove redundant stage index (covered by deals_stage_user_updated_idx)
        if (Schema::hasIndex('deals', '', 'deals_stage_index')) {
            Schema::table('deals', function ($table) {
                $table->dropIndex('deals_stage_index');
            });
        }
    }

    public function down(): void
    {
        // Re-add the standalone stage index
        Schema::table('deals', function ($table) {
            $table->index('stage', 'deals_stage_index');
        });
    }
};

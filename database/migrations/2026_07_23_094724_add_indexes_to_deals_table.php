<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (! Schema::hasIndex('deals', 'deals_stage_index')) {
                $table->index('stage');
            }
            if (! Schema::hasIndex('deals', 'deals_user_id_index')) {
                $table->index('user_id');
            }
            if (! Schema::hasIndex('deals', 'deals_created_at_index')) {
                $table->index('created_at');
            }
            if (! Schema::hasIndex('deals', 'deals_user_id_stage_index')) {
                $table->index(['user_id', 'stage']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (Schema::hasIndex('deals', 'deals_stage_index')) {
                $table->dropIndex('deals_stage_index');
            }
            if (Schema::hasIndex('deals', 'deals_user_id_index')) {
                $table->dropIndex('deals_user_id_index');
            }
            if (Schema::hasIndex('deals', 'deals_created_at_index')) {
                $table->dropIndex('deals_created_at_index');
            }
            if (Schema::hasIndex('deals', 'deals_user_id_stage_index')) {
                $table->dropIndex('deals_user_id_stage_index');
            }
        });
    }
};

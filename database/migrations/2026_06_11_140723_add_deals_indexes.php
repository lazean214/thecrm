<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Main Kanban Query
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['stage', 'user_id', 'updated_at'],
                'deals_stage_user_updated_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Sorting
            |--------------------------------------------------------------------------
            */
            $table->index(
                'updated_at',
                'deals_updated_at_idx'
            );

            $table->index(
                'stage_updated_at',
                'deals_stage_updated_at_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Financial filtering
            |--------------------------------------------------------------------------
            */
            $table->index(
                'amount',
                'deals_amount_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Date + Stage filtering
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['created_at', 'stage'],
                'deals_created_stage_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex('deals_stage_user_updated_idx');
            $table->dropIndex('deals_updated_at_idx');
            $table->dropIndex('deals_stage_updated_at_idx');
            $table->dropIndex('deals_amount_idx');
            $table->dropIndex('deals_created_stage_idx');
        });
    }
};

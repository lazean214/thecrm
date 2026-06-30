<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add indexes for pivot table foreign keys and activity_logs.user_email
     * to improve query performance at scale.
     */
    public function up(): void
    {
        // Pivot table foreign key indexes
        Schema::table('company_contact', function (Blueprint $table) {
            $table->index('company_id', 'company_contact_company_id_index');
            $table->index('contact_id', 'company_contact_contact_id_index');
        });

        Schema::table('company_deal', function (Blueprint $table) {
            $table->index('company_id', 'company_deal_company_id_index');
        });

        Schema::table('contact_deal', function (Blueprint $table) {
            $table->index('contact_id', 'contact_deal_contact_id_index');
        });

        // Activity logs user_email index for faster user lookups
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('user_email', 'activity_logs_user_email_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_contact', function (Blueprint $table) {
            $table->dropIndex('company_contact_company_id_index');
            $table->dropIndex('company_contact_contact_id_index');
        });

        Schema::table('company_deal', function (Blueprint $table) {
            $table->dropIndex('company_deal_company_id_index');
        });

        Schema::table('contact_deal', function (Blueprint $table) {
            $table->dropIndex('contact_deal_contact_id_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_user_email_index');
        });
    }
};

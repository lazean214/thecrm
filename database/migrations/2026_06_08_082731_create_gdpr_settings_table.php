<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gdpr_settings', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type')->unique(); // contacts, users, deals, email_logs
            $table->integer('retention_months')->default(24);
            $table->boolean('is_enabled')->default(true);
            $table->string('custom_action')->nullable(); // delete, anonymize, notify
            $table->timestamps();
        });

        // Seed default settings
        DB::table('gdpr_settings')->insert([
            ['entity_type' => 'contacts', 'retention_months' => 24, 'is_enabled' => true, 'custom_action' => 'anonymize'],
            ['entity_type' => 'users', 'retention_months' => 36, 'is_enabled' => true, 'custom_action' => 'anonymize'],
            ['entity_type' => 'deals', 'retention_months' => 84, 'is_enabled' => false, 'custom_action' => 'delete'],
            ['entity_type' => 'email_logs', 'retention_months' => 12, 'is_enabled' => true, 'custom_action' => 'delete'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('gdpr_settings');
    }
};

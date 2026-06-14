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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('name');
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('stage')->nullable();
            $table->decimal('hours')->nullable();
            $table->decimal('rate')->nullable();
            $table->string('recruitment_agency')->nullable();
            $table->string('consultant_name')->nullable();
            $table->decimal('agency_deal_value', 15, 2)->nullable();
            $table->decimal('margin_agreed', 15, 2)->nullable();
            $table->date('date_sent')->nullable();
            $table->date('date_signed')->nullable();
            $table->string('who_signed')->nullable();
            $table->string('signed_doc')->nullable();
            $table->string('right_to_work')->nullable();
            $table->string('proof_of_address')->nullable();
            $table->string('photo_id_passport')->nullable();
            $table->string('mda_setup')->nullable();
            $table->string('mda_reference_number')->nullable();
            $table->date('date_set_up')->nullable();
            $table->string('remittance_received')->nullable();
            $table->date('date_logged')->nullable();

            // Compliance fields
            $table->date('starter_checklist_recieved_date')->nullable();
            $table->string('starter_form')->nullable();
            $table->string('tax_code')->nullable();
            $table->date('contract_recieved_date')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};

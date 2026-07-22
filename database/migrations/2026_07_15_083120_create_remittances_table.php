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
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->integer('week_no')->nullable();
            $table->foreignId('contact_id')->constrained()->nullable();
            $table->string('user_id')->nullable();
            $table->double('amount')->nullable();
            $table->date('date_added')->nullable();
            $table->string('status')->nullable();
            $table->integer('deal_owner')->nullable();
            $table->integer('company_id')->nullable();
            $table->string('from')->nullable();
            $table->string('invoice')->nullable();
            $table->string('batch')->nullable();
            $table->string('agency_funds')->nullable();
            $table->string('payment_status')->nullable();
            $table->double('margin_agreed')->nullable();
            $table->boolean('compliance')->nullable();
            $table->string('hours')->nullable();
            $table->double('rate')->nullable();
            $table->date('we_date')->nullable();
            $table->date('shirft_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};

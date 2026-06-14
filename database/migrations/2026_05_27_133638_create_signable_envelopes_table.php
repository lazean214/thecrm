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
        Schema::create('signable_envelopes', function (Blueprint $table) {
            $table->id();

            // Foreign key relation linking to your Deal
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();

            // The absolute unique identifier returned by the Signable API
            $table->string('envelope_fingerprint')->unique()->index();

            // Storing descriptive details
            $table->string('title');

            // Current status: processing, failed, draft, sent, signed, cancelled, expired, rejected
            $table->string('status')->default('sent');

            // Storage for signing links or completed download paths
            $table->string('download_url')->nullable();
            $table->string('signed_pdf_path')->nullable(); // Local storage path or S3 if saved locally

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signable_envelopes');
    }
};

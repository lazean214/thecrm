<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_email_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deal_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('email_template_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('to_email');

            $table->string('subject');

            $table->longText('body');

            $table->enum('status', [
                'pending',
                'sent',
                'failed',
            ])->default('pending');

            $table->timestamp('sent_at')
                ->nullable();

            $table->longText('error_message')
                ->nullable();

            $table->timestamps();

            $table->index([
                'deal_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_email_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        DB::table('business_settings')->insert([
            [
                'key' => 'fiscal_year_start_month',
                'value' => json_encode(4),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fiscal_year_start_day',
                'value' => json_encode(6),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fiscal_year_end_month',
                'value' => json_encode(4),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fiscal_year_end_day',
                'value' => json_encode(5),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};

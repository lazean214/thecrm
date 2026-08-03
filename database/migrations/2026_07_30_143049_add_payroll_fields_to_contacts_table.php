<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('payroll_company')->nullable();
            $table->string('payroll_source')->nullable();
            $table->string('payroll_reference')->nullable();
            $table->date('payroll_start_date')->nullable();
            $table->string('payroll_status')->nullable()->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['payroll_company', 'payroll_source', 'payroll_reference', 'payroll_start_date', 'payroll_status']);
        });
    }
};

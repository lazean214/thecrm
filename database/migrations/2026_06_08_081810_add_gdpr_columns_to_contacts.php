
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->timestamp('anonymised_at')->nullable();
            $table->date('marked_for_deletion_on')->nullable();
            $table->timestamp('last_activity_at')->nullable()->after('updated_at');
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['anonymised_at', 'marked_for_deletion_on', 'last_activity_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('firm_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('role')->after('firm_id')->default('contador');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('firm_id');
            $table->dropColumn('role');
        });
    }
};

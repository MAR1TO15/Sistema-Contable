<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiscal_periods', function (Blueprint $table) {
            $table->foreignId('closed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->after('closed_by');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->boolean('is_closing')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('is_closing');
        });

        Schema::table('fiscal_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn('closed_at');
        });
    }
};

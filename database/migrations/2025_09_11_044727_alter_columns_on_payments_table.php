<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            $table->string('msisdn')->nullable()->after('order_id');
            $table->string('card_number')->nullable()->after('msisdn');
            $table->string('mm_yr')->nullable()->after('card_number');
            $table->string('response_notes')->nullable()->after('status');
            $table->text('notes')->nullable()->after('response_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('msisdn');
            $table->dropColumn('card_number');
            $table->dropColumn('mm_yr');
            $table->dropColumn('notes');
            $table->dropColumn('response_notes');
        });
    }
};

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
        Schema::table('academy_lessons', function (Blueprint $table) {
            Schema::table('academy_lessons', function (Blueprint $table) {

                $table->dropForeign(['category_id']);


                $table->renameColumn('category_id', 'academy_id');


                $table->foreign('academy_id')
                    ->references('id')
                    ->on('academies')
                    ->cascadeOnDelete();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_lessons', function (Blueprint $table) {
            Schema::table('academy_lessons', function (Blueprint $table) {
                $table->dropForeign(['academy_id']);
                $table->renameColumn('academy_id', 'category_id');
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });
        });
    }
};

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->string('workplace')->nullable();
            $table->string('position')->nullable();
            $table->boolean('is_authed')->default(false);
            $table->string('tg_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->dropColumn('workplace');
            $table->dropColumn('position');
            $table->dropColumn('is_authed');
            $table->dropColumn('tg_name');
        });
    }
};

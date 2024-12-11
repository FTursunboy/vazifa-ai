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
            $table->string('email')->nullable()->unique();
            $table->integer('daily_request_limit')->nullable();
            $table->integer('daily_requests_used')->nullable();
            $table->dateTime('daily_requests_reset_at')->nullable();
            $table->string('state')->default('new');
            $table->string('code')->nullable();
            $table->integer('tariff')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email');
            $table->dropColumn('daily_request_limit');
            $table->dropColumn('daily_requests_used');
            $table->dropColumn('daily_requests_reset_at');
            $table->dropColumn('state');
            $table->dropColumn('code');
            $table->dropColumn('tariff');
        });
    }
};

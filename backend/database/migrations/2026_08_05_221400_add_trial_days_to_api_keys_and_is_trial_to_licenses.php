<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->unsignedInteger('trial_days')->default(0)->after('rate_limit');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->index()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('trial_days');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('is_trial');
        });
    }
};

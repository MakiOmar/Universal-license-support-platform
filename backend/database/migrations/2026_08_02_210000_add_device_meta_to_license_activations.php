<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('license_activations', function (Blueprint $table) {
            $table->string('device_name', 120)->nullable()->after('user_agent');
            $table->string('platform', 50)->nullable()->after('device_name');
            $table->string('app_version', 50)->nullable()->after('platform');
        });
    }

    public function down(): void
    {
        Schema::table('license_activations', function (Blueprint $table) {
            $table->dropColumn(['device_name', 'platform', 'app_version']);
        });
    }
};

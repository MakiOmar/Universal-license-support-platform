<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('license_activations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('license_id')->constrained('licenses')->onDelete('cascade');
            $table->string('activation_type', 50);
            $table->string('activation_value', 255);
            $table->string('activation_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('activated_at')->useCurrent();
            $table->timestamp('last_check')->nullable();
            $table->unique(['license_id', 'activation_hash']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_activations');
    }
};



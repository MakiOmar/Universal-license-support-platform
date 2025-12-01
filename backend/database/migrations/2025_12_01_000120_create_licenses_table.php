<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('license_key')->unique();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('license_type', 50);
            $table->integer('max_activations')->default(1);
            $table->string('status', 20)->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('support_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};



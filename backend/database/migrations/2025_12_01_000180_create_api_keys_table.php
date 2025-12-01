<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('product_id')->constrained('products');
            $table->string('api_key', 255)->unique();
            $table->string('api_secret', 255);
            $table->integer('rate_limit')->default(1000);
            $table->string('status', 20)->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};



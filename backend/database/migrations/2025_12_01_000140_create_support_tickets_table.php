<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('license_id')->nullable()->constrained('licenses');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->string('subject', 255);
            $table->text('description');
            $table->string('priority', 20)->default('medium');
            $table->string('status', 20)->default('open');
            $table->string('category', 50)->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};



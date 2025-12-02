<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('activity_type', 50); // license_activated, license_purchased, ticket_created, profile_updated, payment_made, etc.
            $table->string('description', 500);
            $table->string('entity_type', 100)->nullable(); // App\Models\License, App\Models\SupportTicket, etc.
            $table->unsignedBigInteger('entity_id')->nullable(); // ID of the related entity
            $table->json('metadata')->nullable(); // Additional data about the activity
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');
        });

        Schema::table('customer_activities', function (Blueprint $table) {
            $table->index(['customer_id', 'created_at']);
            $table->index(['activity_type', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_activities');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->string('user_type', 20);
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
    }
};



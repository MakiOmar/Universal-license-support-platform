<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('reply_id')->nullable()->constrained('ticket_replies')->onDelete('cascade');
            $table->string('filename', 255);
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};



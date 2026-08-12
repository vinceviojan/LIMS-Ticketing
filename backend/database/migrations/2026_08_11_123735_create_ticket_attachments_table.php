<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->string('file_name');
            $table->string('file_type')->nullable(); // pdf, png, jpeg, doc, docx, gdrive
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('external_url')->nullable(); // For Google Drive / External URLs
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};

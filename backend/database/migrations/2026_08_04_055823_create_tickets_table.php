<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('ticket_no')->nullable();
            $table->string('issue')->nullable();
            $table->foreignId('problem_category_id')->nullable()->constrained('problem_categories')->nullOnDelete();
            $table->timestamp('date_submitted')->useCurrent();
            $table->enum('status', ['OPEN', 'ESCALATED', 'CANCEL', 'CLOSE'])->default('OPEN');
            $table->enum('urgency', ['LOW', 'NORMAL', 'HIGH'])->default('NORMAL');
            $table->string('upload_intralab')->nullable();
            $table->string('upload_limsportal')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

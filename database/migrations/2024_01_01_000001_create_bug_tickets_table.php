<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_tickets', function (Blueprint $table) {
            $table->id();

            // Basic ticket info
            $table->string('title');
            $table->string('module');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('laravel_version')->default('11.x');
            $table->text('description')->nullable();

            // Screenshot
            $table->string('image_path');

            // Status flow: pending → approved → processing → fixed | failed
            $table->enum('status', ['pending', 'approved', 'processing', 'fixed', 'failed'])
                  ->default('pending');

            // AI-generated fix stored as JSON
            $table->json('fix_result')->nullable();

            // Error message if AI call fails
            $table->text('error_message')->nullable();

            // Who approved it
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_tickets');
    }
};

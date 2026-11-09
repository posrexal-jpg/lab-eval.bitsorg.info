<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_template_id')->constrained()->restrictOnDelete();
            $table->json('answers');
            $table->decimal('average_score', 4, 2)->nullable();
            $table->unsignedTinyInteger('rating_count')->default(0);
            $table->string('status', 10)->default('visible'); // visible | flagged | hidden
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'created_at']);
            $table->index('status');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60);
            $table->string('subject')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('evaluations');
    }
};

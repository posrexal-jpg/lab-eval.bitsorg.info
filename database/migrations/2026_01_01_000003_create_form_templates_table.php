<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->longText('html');          // sanitized HTML
            $table->json('schema');            // detected fields
            $table->boolean('is_active')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_templates');
    }
};

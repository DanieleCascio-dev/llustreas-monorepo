<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('text_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_image_id')->constrained()->cascadeOnDelete();
            $table->string('subtitle')->nullable();
            $table->string('title');
            $table->string('text_color')->default('#000000');
            $table->string('background_color')->default('#ffffff');
            $table->enum('layout', ['text-left', 'text-right'])->default('text-left');
            $table->string('image_position')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('text_blocks');
    }
};

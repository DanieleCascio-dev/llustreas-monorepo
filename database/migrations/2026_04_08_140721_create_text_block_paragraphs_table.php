<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('text_block_paragraphs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('text_block_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('');
            $table->text('text');
            $table->text('text_html')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('text_block_paragraphs');
    }
};

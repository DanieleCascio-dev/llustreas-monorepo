<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_layers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['image', 'slideshow'])->default('image');
            $table->integer('z_index')->default(0);
            $table->float('parallax_speed')->default(0);
            $table->integer('breakpoint')->default(450);
            $table->json('css_config')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_layers');
    }
};

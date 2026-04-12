<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_posts', function (Blueprint $table) {
            $table->text('thumbnail')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('instagram_posts', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->string('layout', 10)->default('desktop')->after('is_preview');
            $table->index(['layout', 'column_index', 'order']);
        });
    }

    public function down(): void
    {
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->dropIndex(['layout', 'column_index', 'order']);
            $table->dropColumn('layout');
        });
    }
};

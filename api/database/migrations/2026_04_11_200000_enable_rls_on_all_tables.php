<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'users',
        'projects',
        'project_images',
        'text_blocks',
        'text_block_paragraphs',
        'gallery_images',
        'site_settings',
        'hero_layers',
        'hero_layer_images',
        'personal_access_tokens',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};

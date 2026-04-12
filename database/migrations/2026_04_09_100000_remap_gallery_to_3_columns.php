<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Merge old 4 columns (0-3) into 3 columns (0-2).
        // Col 0 stays 0, Col 1 stays 1, Col 2 merges into Col 1, Col 3 becomes Col 2.
        DB::table('gallery_images')
            ->where('column_index', 2)
            ->update(['column_index' => 1]);

        DB::table('gallery_images')
            ->where('column_index', 3)
            ->update(['column_index' => 2]);

        // Re-sequence order within each column so there are no gaps
        foreach ([0, 1, 2] as $col) {
            $ids = DB::table('gallery_images')
                ->where('column_index', $col)
                ->where('is_preview', false)
                ->orderBy('order')
                ->pluck('id');

            foreach ($ids as $i => $id) {
                DB::table('gallery_images')
                    ->where('id', $id)
                    ->update(['order' => $i]);
            }
        }
    }

    public function down(): void
    {
        // Best-effort reverse: split column 1 back into 1 and 2
        // by assigning the second half of column 1 to column 2
        $col1Ids = DB::table('gallery_images')
            ->where('column_index', 1)
            ->where('is_preview', false)
            ->orderBy('order')
            ->pluck('id');

        $half = (int) ceil($col1Ids->count() / 2);
        $toMove = $col1Ids->slice($half);

        if ($toMove->isNotEmpty()) {
            DB::table('gallery_images')
                ->whereIn('id', $toMove->values())
                ->update(['column_index' => 2]);
        }

        DB::table('gallery_images')
            ->where('column_index', 2)
            ->where('is_preview', false)
            ->update(['column_index' => 3]);

        // Old column 2 items that were merged into 1 become column 2 again
        if ($toMove->isNotEmpty()) {
            $i = 0;
            foreach ($toMove as $id) {
                DB::table('gallery_images')->where('id', $id)->update([
                    'column_index' => 2,
                    'order' => $i++,
                ]);
            }
        }
    }
};

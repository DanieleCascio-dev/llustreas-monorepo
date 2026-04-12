<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(): JsonResponse
    {
        $images = GalleryImage::where('is_preview', false)
            ->orderBy('column_index')
            ->orderBy('order')
            ->get();

        return response()->json($images);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'src' => 'required|string',
            'title' => 'nullable|string|max:255',
            'column_index' => 'required|integer|min:0|max:2',
            'order' => 'integer',
            'is_preview' => 'boolean',
        ]);

        $maxOrder = GalleryImage::where('column_index', $data['column_index'])->max('order') ?? -1;
        $data['order'] = $data['order'] ?? $maxOrder + 1;

        $image = GalleryImage::create($data);

        return response()->json($image, 201);
    }

    public function update(Request $request, GalleryImage $gallery): JsonResponse
    {
        if ($gallery->is_preview) {
            abort(403, 'Immagine di preview non modificabile da questa route');
        }

        $data = $request->validate([
            'src' => 'sometimes|string',
            'title' => 'nullable|string|max:255',
            'column_index' => 'sometimes|integer|min:0|max:2',
            'order' => 'sometimes|integer',
            'is_preview' => 'sometimes|boolean',
        ]);

        $gallery->update($data);

        return response()->json($gallery);
    }

    public function destroy(GalleryImage $gallery): JsonResponse
    {
        if ($gallery->is_preview) {
            abort(403, 'Immagine di preview non eliminabile da questa route');
        }

        $gallery->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:gallery_images,id',
            'order.*.column_index' => 'required|integer|min:0|max:2',
            'order.*.order' => 'required|integer',
        ]);

        foreach ($data['order'] as $item) {
            GalleryImage::where('id', $item['id'])
                ->where('is_preview', false)
                ->update([
                    'column_index' => $item['column_index'],
                    'order' => $item['order'],
                ]);
        }

        return response()->json(['message' => 'Gallery reordered']);
    }
}

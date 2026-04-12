<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryPreviewController extends Controller
{
    public function index(): JsonResponse
    {
        $images = GalleryImage::where('is_preview', true)
            ->orderBy('order')
            ->get();

        return response()->json($images);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'src' => 'required|string',
            'title' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0|max:8',
        ]);

        $count = GalleryImage::where('is_preview', true)->count();
        if ($count >= 9) {
            return response()->json(['message' => 'Massimo 9 immagini nella preview'], 422);
        }

        $order = $data['order'] ?? $count;

        $image = GalleryImage::create([
            'src' => $data['src'],
            'title' => $data['title'] ?? null,
            'column_index' => 0,
            'order' => $order,
            'is_preview' => true,
        ]);

        return response()->json($image, 201);
    }

    public function update(Request $request, GalleryImage $galleryPreview): JsonResponse
    {
        if (!$galleryPreview->is_preview) {
            abort(403, 'Immagine non appartenente alla preview');
        }

        $data = $request->validate([
            'src' => 'sometimes|string',
            'title' => 'nullable|string|max:255',
        ]);

        $galleryPreview->update($data);

        return response()->json($galleryPreview);
    }

    public function destroy(GalleryImage $galleryPreview): JsonResponse
    {
        if (!$galleryPreview->is_preview) {
            abort(403, 'Immagine non appartenente alla preview');
        }

        $galleryPreview->delete();

        $remaining = GalleryImage::where('is_preview', true)->orderBy('order')->get();
        foreach ($remaining as $idx => $img) {
            if ($img->order !== $idx) {
                $img->update(['order' => $idx]);
            }
        }

        return response()->json(null, 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:gallery_images,id',
            'order.*.order' => 'required|integer',
        ]);

        foreach ($data['order'] as $item) {
            GalleryImage::where('id', $item['id'])
                ->where('is_preview', true)
                ->update([
                    'order' => $item['order'],
                ]);
        }

        return response()->json(['message' => 'Preview reordered']);
    }
}

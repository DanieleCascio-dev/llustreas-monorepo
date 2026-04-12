<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroLayer;
use App\Models\HeroLayerImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeroController extends Controller
{
    // ── Layer CRUD ──

    public function index(): JsonResponse
    {
        $layers = HeroLayer::ordered()->with('images')->get();

        return response()->json($layers);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:image,slideshow',
            'z_index'        => 'required|integer|min:0',
            'parallax_speed' => 'required|numeric|min:0|max:1',
            'breakpoint'     => 'nullable|integer|min:0',
            'css_config'     => 'nullable|array',
        ]);

        $data['order'] = HeroLayer::count();
        $data['breakpoint'] = $data['breakpoint'] ?? 450;

        $layer = HeroLayer::create($data);

        return response()->json($layer->load('images'), 201);
    }

    public function update(Request $request, HeroLayer $layer): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'sometimes|string|max:100',
            'type'           => 'sometimes|in:image,slideshow',
            'z_index'        => 'sometimes|integer|min:0',
            'parallax_speed' => 'sometimes|numeric|min:0|max:1',
            'breakpoint'     => 'nullable|integer|min:0',
            'css_config'     => 'nullable|array',
        ]);

        $layer->update($data);

        return response()->json($layer->load('images'));
    }

    public function destroy(HeroLayer $layer): JsonResponse
    {
        $layer->delete();

        $remaining = HeroLayer::ordered()->get();
        foreach ($remaining as $idx => $l) {
            if ($l->order !== $idx) {
                $l->update(['order' => $idx]);
            }
        }

        return response()->json(null, 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order'         => 'required|array',
            'order.*.id'    => 'required|exists:hero_layers,id',
            'order.*.order' => 'required|integer',
        ]);

        foreach ($data['order'] as $item) {
            HeroLayer::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Layers reordered']);
    }

    // ── Layer Images CRUD ──

    public function imageStore(Request $request, HeroLayer $layer): JsonResponse
    {
        $data = $request->validate([
            'mobile_src'  => 'required|string',
            'desktop_src' => 'required|string',
            'alt'         => 'nullable|string|max:255',
        ]);

        if ($layer->type === 'image' && $layer->images()->count() >= 1) {
            return response()->json(['message' => 'Layer di tipo image accetta una sola immagine'], 422);
        }

        $data['order'] = $layer->images()->count();

        $image = $layer->images()->create($data);

        return response()->json($image, 201);
    }

    public function imageUpdate(Request $request, HeroLayer $layer, HeroLayerImage $image): JsonResponse
    {
        if ($image->hero_layer_id !== $layer->id) {
            abort(403, 'Immagine non appartenente a questo layer');
        }

        $data = $request->validate([
            'mobile_src'  => 'sometimes|string',
            'desktop_src' => 'sometimes|string',
            'alt'         => 'nullable|string|max:255',
        ]);

        $image->update($data);

        return response()->json($image);
    }

    public function imageDestroy(HeroLayer $layer, HeroLayerImage $image): JsonResponse
    {
        if ($image->hero_layer_id !== $layer->id) {
            abort(403, 'Immagine non appartenente a questo layer');
        }

        $image->delete();

        $remaining = $layer->images()->orderBy('order')->get();
        foreach ($remaining as $idx => $img) {
            if ($img->order !== $idx) {
                $img->update(['order' => $idx]);
            }
        }

        return response()->json(null, 204);
    }

    public function imageReorder(Request $request, HeroLayer $layer): JsonResponse
    {
        $data = $request->validate([
            'order'         => 'required|array',
            'order.*.id'    => 'required|exists:hero_layer_images,id',
            'order.*.order' => 'required|integer',
        ]);

        foreach ($data['order'] as $item) {
            HeroLayerImage::where('id', $item['id'])
                ->where('hero_layer_id', $layer->id)
                ->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Images reordered']);
    }
}

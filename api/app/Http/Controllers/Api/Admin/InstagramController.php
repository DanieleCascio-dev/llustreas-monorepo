<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstagramPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstagramController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = InstagramPost::ordered()->get();

        return response()->json($posts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => 'required|string|url',
            'thumbnail' => 'nullable|string',
        ]);

        $data['media'] = [];

        $maxOrder = InstagramPost::max('order') ?? -1;
        $data['order'] = $maxOrder + 1;

        $post = InstagramPost::create($data);

        return response()->json($post, 201);
    }

    public function update(Request $request, InstagramPost $instagram): JsonResponse
    {
        $data = $request->validate([
            'url' => 'sometimes|string|url',
            'thumbnail' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        $instagram->update($data);

        return response()->json($instagram->fresh());
    }

    public function destroy(InstagramPost $instagram): JsonResponse
    {
        $instagram->delete();

        return response()->json(null, 204);
    }

    public function fetchThumbnail(InstagramPost $instagram): JsonResponse
    {
        return response()->json($instagram->fresh());
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:instagram_posts,id',
            'order.*.order' => 'required|integer',
        ]);

        foreach ($data['order'] as $item) {
            InstagramPost::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Instagram posts reordered']);
    }

}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstagramPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InstagramController extends Controller
{
    private function fetchAndUploadThumbnail(string $igUrl): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)\r\n",
                    'timeout' => 8,
                ],
            ]);
            $html = @file_get_contents($igUrl, false, $context);
            if (!$html) return null;

            $ogImage = null;
            if (preg_match('/<meta\s+(?:property|name)=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
                $ogImage = $m[1];
            } elseif (preg_match('/content=["\']([^"\']+)["\']\s+(?:property|name)=["\']og:image["\']/i', $html, $m)) {
                $ogImage = $m[1];
            }
            if (!$ogImage) return null;

            $ogImage = html_entity_decode($ogImage, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $cloud = config('services.cloudinary.cloud_name');
            $key = config('services.cloudinary.api_key');
            $secret = config('services.cloudinary.api_secret');
            $timestamp = time();
            $folder = 'illustreas/instagram';

            $params = ['folder' => $folder, 'timestamp' => $timestamp];
            ksort($params);
            $toSign = collect($params)->map(fn ($v, $k) => "{$k}={$v}")->implode('&');
            $signature = sha1($toSign . $secret);

            $response = Http::asMultipart()->post(
                "https://api.cloudinary.com/v1_1/{$cloud}/image/upload",
                [
                    ['name' => 'file', 'contents' => $ogImage],
                    ['name' => 'folder', 'contents' => $folder],
                    ['name' => 'timestamp', 'contents' => (string) $timestamp],
                    ['name' => 'api_key', 'contents' => $key],
                    ['name' => 'signature', 'contents' => $signature],
                ]
            );

            if ($response->ok()) {
                return $response->json('secure_url');
            }
        } catch (\Throwable $e) {
            // silently fail
        }
        return null;
    }

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

        if (empty($data['thumbnail'])) {
            $data['thumbnail'] = $this->fetchAndUploadThumbnail($data['url']);
        }

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

        return response()->json($instagram);
    }

    public function destroy(InstagramPost $instagram): JsonResponse
    {
        $instagram->delete();

        return response()->json(null, 204);
    }

    public function fetchThumbnail(InstagramPost $instagram): JsonResponse
    {
        $thumbnail = $this->fetchAndUploadThumbnail($instagram->url);

        if (!$thumbnail) {
            return response()->json(['message' => 'Impossibile recuperare la thumbnail da Instagram'], 422);
        }

        $instagram->update(['thumbnail' => $thumbnail]);

        return response()->json($instagram);
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

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CloudinaryController extends Controller
{
    private function credentials(): array
    {
        return [
            'cloud' => config('services.cloudinary.cloud_name'),
            'key' => config('services.cloudinary.api_key'),
            'secret' => config('services.cloudinary.api_secret'),
        ];
    }

    private function validateFolderPath(string $path): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_\/\-]+$/', $path);
    }

    public function folders(Request $request): JsonResponse
    {
        $path = $request->query('path', 'illustreas');

        if (!str_starts_with($path, 'illustreas') || !$this->validateFolderPath($path)) {
            return response()->json(['message' => 'Folder non consentita'], 403);
        }

        $creds = $this->credentials();

        $response = Http::withBasicAuth($creds['key'], $creds['secret'])
            ->get("https://api.cloudinary.com/v1_1/{$creds['cloud']}/folders/{$path}");

        if (!$response->ok()) {
            return response()->json(['folders' => []]);
        }

        return response()->json([
            'folders' => $response->json('folders', []),
        ]);
    }

    public function createFolder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => 'required|string|max:255',
        ]);

        $path = $data['path'];

        if (!str_starts_with($path, 'illustreas') || !$this->validateFolderPath($path)) {
            return response()->json(['message' => 'Folder non consentita'], 403);
        }

        $creds = $this->credentials();

        $response = Http::withBasicAuth($creds['key'], $creds['secret'])
            ->post("https://api.cloudinary.com/v1_1/{$creds['cloud']}/folders/{$path}");

        if (!$response->ok()) {
            return response()->json([
                'message' => 'Errore creazione cartella',
                'detail' => $response->body(),
            ], $response->status());
        }

        return response()->json($response->json());
    }

    public function deleteFolder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => 'required|string|max:255',
        ]);

        $path = $data['path'];
        $protected = ['illustreas', 'illustreas/gallery', 'illustreas/projects', 'illustreas/covers'];

        if (!str_starts_with($path, 'illustreas') || !$this->validateFolderPath($path)) {
            return response()->json(['message' => 'Folder non consentita'], 403);
        }

        if (in_array($path, $protected)) {
            return response()->json(['message' => 'Non puoi eliminare le cartelle principali'], 403);
        }

        $creds = $this->credentials();

        $response = Http::withBasicAuth($creds['key'], $creds['secret'])
            ->delete("https://api.cloudinary.com/v1_1/{$creds['cloud']}/folders/{$path}");

        if (!$response->ok()) {
            $detail = $response->json('error.message', $response->body());
            return response()->json([
                'message' => 'Errore eliminazione cartella. Assicurati che sia vuota.',
                'detail' => $detail,
            ], $response->status());
        }

        return response()->json(['message' => 'Cartella eliminata']);
    }

    public function sign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'folder' => 'required|string|max:255',
        ]);

        $folder = $data['folder'];

        if (!str_starts_with($folder, 'illustreas') || !$this->validateFolderPath($folder)) {
            return response()->json(['message' => 'Folder non consentita'], 403);
        }

        $creds = $this->credentials();
        $timestamp = time();

        $params = [
            'asset_folder' => $folder,
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];

        ksort($params);
        $toSign = collect($params)
            ->map(fn ($v, $k) => "{$k}={$v}")
            ->implode('&');

        $signature = sha1($toSign . $creds['secret']);

        return response()->json([
            'signature' => $signature,
            'timestamp' => $timestamp,
            'api_key' => $creds['key'],
            'cloud_name' => $creds['cloud'],
            'folder' => $folder,
            'asset_folder' => $folder,
        ]);
    }

    public function images(Request $request): JsonResponse
    {
        $folder = $request->query('folder', 'illustreas');
        $cursor = $request->query('cursor');

        if (!str_starts_with($folder, 'illustreas') || !$this->validateFolderPath($folder)) {
            return response()->json(['message' => 'Folder non consentita'], 403);
        }

        $creds = $this->credentials();

        $body = [
            'expression' => "folder=\"{$folder}\" OR (resource_type:image AND public_id:{$folder}/*)",
            'max_results' => 60,
            'sort_by' => [['created_at' => 'desc']],
        ];

        if ($cursor) {
            $body['next_cursor'] = $cursor;
        }

        $response = Http::withBasicAuth($creds['key'], $creds['secret'])
            ->post("https://api.cloudinary.com/v1_1/{$creds['cloud']}/resources/search", $body);

        if (!$response->ok()) {
            return response()->json(['message' => 'Errore Cloudinary', 'detail' => $response->body()], 502);
        }

        $expectedDepth = substr_count($folder, '/') + 1;

        $resources = collect($response->json('resources', []))
            ->filter(fn ($r) => substr_count($r['public_id'], '/') === $expectedDepth)
            ->values()
            ->map(fn ($r) => [
                'public_id' => $r['public_id'],
                'url' => $r['secure_url'],
                'format' => $r['format'] ?? '',
                'width' => $r['width'] ?? 0,
                'height' => $r['height'] ?? 0,
                'bytes' => $r['bytes'] ?? 0,
                'created_at' => $r['created_at'] ?? '',
            ]);

        return response()->json([
            'images' => $resources,
            'next_cursor' => $response->json('next_cursor'),
        ]);
    }
}

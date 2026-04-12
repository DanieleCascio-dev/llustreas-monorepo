<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(string $key): JsonResponse
    {
        $value = SiteSetting::getValue($key);

        return response()->json([
            'key'   => $key,
            'value' => $value,
        ]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value' => 'required',
        ]);

        $value = $request->input('value');

        if ($key === 'about_me' && is_array($value)) {
            $value = $this->sanitizeAboutMe($value);
        }

        SiteSetting::setValue($key, $value);

        return response()->json([
            'key'   => $key,
            'value' => $value,
        ]);
    }

    private function sanitizeAboutMe(array $data): array
    {
        $allowed = '<strong><b><em><i><br>';

        if (isset($data['title'])) {
            $data['title'] = strip_tags($data['title']);
        }

        if (isset($data['paragraphs']) && is_array($data['paragraphs'])) {
            $data['paragraphs'] = array_map(
                fn ($p) => is_string($p) ? strip_tags($p, $allowed) : '',
                $data['paragraphs']
            );
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = array_map(
                fn ($t) => is_string($t) ? strip_tags($t) : '',
                $data['tags']
            );
        }

        if (isset($data['image'])) {
            $data['image'] = filter_var($data['image'], FILTER_SANITIZE_URL);
        }

        return $data;
    }
}

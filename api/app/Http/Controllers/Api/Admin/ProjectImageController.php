<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\TextBlock;
use App\Models\TextBlockParagraph;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectImageController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $images = $project->images()
            ->with('textBlock.paragraphs')
            ->orderBy('order')
            ->get();

        return response()->json($images);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:image,text_image_block',
            'src' => 'nullable|string',
            'order' => 'integer',
        ]);

        $maxOrder = $project->images()->max('order') ?? -1;
        $data['order'] = $data['order'] ?? $maxOrder + 1;

        $image = $project->images()->create($data);

        if ($data['type'] === 'text_image_block') {
            $textBlockData = $request->validate([
                'text_block.subtitle' => 'nullable|string|max:255',
                'text_block.title' => 'required|string|max:255',
                'text_block.text_color' => 'nullable|string|max:20',
                'text_block.background_color' => 'nullable|string|max:20',
                'text_block.layout' => 'required|in:text-left,text-right',
                'text_block.image_position' => 'nullable|string|max:50',
                'text_block.paragraphs' => 'array',
                'text_block.paragraphs.*.title' => 'nullable|string|max:255',
                'text_block.paragraphs.*.text' => 'required|string',
                'text_block.paragraphs.*.text_html' => 'nullable|string',
            ]);

            $tb = $image->textBlock()->create($textBlockData['text_block'] ?? []);

            if (!empty($textBlockData['text_block']['paragraphs'])) {
                foreach ($textBlockData['text_block']['paragraphs'] as $i => $p) {
                    $p['title'] = $p['title'] ?? '';
                    $p['text_html'] = $p['text_html'] ?? null;
                    $tb->paragraphs()->create(array_merge($p, ['order' => $i]));
                }
            }
        }

        $image->load('textBlock.paragraphs');

        return response()->json($image, 201);
    }

    public function update(Request $request, Project $project, ProjectImage $image): JsonResponse
    {
        if ($image->project_id !== $project->id) {
            abort(403, 'Immagine non appartenente a questo progetto');
        }

        $data = $request->validate([
            'src' => 'nullable|string',
            'order' => 'sometimes|integer',
        ]);

        $image->update($data);

        if ($image->type === 'text_image_block' && $request->has('text_block')) {
            $textBlockData = $request->validate([
                'text_block.subtitle' => 'nullable|string|max:255',
                'text_block.title' => 'sometimes|string|max:255',
                'text_block.text_color' => 'nullable|string|max:20',
                'text_block.background_color' => 'nullable|string|max:20',
                'text_block.layout' => 'sometimes|in:text-left,text-right',
                'text_block.image_position' => 'nullable|string|max:50',
                'text_block.paragraphs' => 'sometimes|array',
                'text_block.paragraphs.*.title' => 'nullable|string|max:255',
                'text_block.paragraphs.*.text' => 'required|string',
                'text_block.paragraphs.*.text_html' => 'nullable|string',
            ]);

            $tb = $image->textBlock;
            if ($tb) {
                $tb->update($textBlockData['text_block']);

                if (isset($textBlockData['text_block']['paragraphs'])) {
                    $tb->paragraphs()->delete();
                    foreach ($textBlockData['text_block']['paragraphs'] as $i => $p) {
                        $p['title'] = $p['title'] ?? '';
                        $p['text_html'] = $p['text_html'] ?? null;
                        $tb->paragraphs()->create(array_merge($p, ['order' => $i]));
                    }
                }
            }
        }

        $image->load('textBlock.paragraphs');

        return response()->json($image);
    }

    public function destroy(Project $project, ProjectImage $image): JsonResponse
    {
        if ($image->project_id !== $project->id) {
            abort(403, 'Immagine non appartenente a questo progetto');
        }

        $image->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:project_images,id',
            'order.*.order' => 'required|integer',
        ]);

        foreach ($data['order'] as $item) {
            ProjectImage::where('id', $item['id'])
                ->where('project_id', $project->id)
                ->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Images reordered']);
    }
}

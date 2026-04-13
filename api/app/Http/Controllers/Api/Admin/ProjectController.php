<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index(): JsonResponse
    {
        $projects = Project::ordered()
            ->withCount('images')
            ->get();

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:projects',
            'hero_url' => 'nullable|string',
            'gif_url' => 'nullable|string',
            'layout' => 'required|in:grid,column',
            'order' => 'integer',
            'description' => 'nullable|string',
            'info' => 'nullable|string|max:255',
            'is_published' => 'boolean',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $project = Project::create($data);

        return response()->json($project, 201);
    }

    public function show(Project $project): JsonResponse
    {
        $project->load([
            'images' => fn ($q) => $q->orderBy('order'),
            'images.textBlock.paragraphs' => fn ($q) => $q->orderBy('order'),
        ]);

        return response()->json($project);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:projects,slug,' . $project->id,
            'hero_url' => 'nullable|string',
            'gif_url' => 'nullable|string',
            'layout' => 'sometimes|in:grid,column',
            'order' => 'sometimes|integer',
            'description' => 'nullable|string',
            'info' => 'nullable|string|max:255',
            'is_published' => 'sometimes|boolean',
        ]);

        $project->update($data);

        return response()->json($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(null, 204);
    }

    public function duplicate(Project $project): JsonResponse
    {
        $maxOrder = Project::max('order') ?? 0;

        $clone = $project->replicate(['slug']);
        $clone->title = $project->title . ' (copia)';
        $clone->slug = Str::slug($clone->title) . '-' . Str::random(5);
        $clone->is_published = false;
        $clone->order = $maxOrder + 1;
        $clone->save();

        $project->load(['images' => fn ($q) => $q->orderBy('order'), 'images.textBlock.paragraphs']);
        foreach ($project->images as $img) {
            $newImg = $img->replicate();
            $newImg->project_id = $clone->id;
            $newImg->save();

            if ($img->textBlock) {
                $newBlock = $img->textBlock->replicate();
                $newBlock->project_image_id = $newImg->id;
                $newBlock->save();

                foreach ($img->textBlock->paragraphs as $para) {
                    $newPara = $para->replicate();
                    $newPara->text_block_id = $newBlock->id;
                    $newPara->save();
                }
            }
        }

        return response()->json($clone->load('images'), 201);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:projects,id',
            'order.*.order' => 'required|integer',
        ]);

        foreach ($data['order'] as $item) {
            Project::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Projects reordered']);
    }
}

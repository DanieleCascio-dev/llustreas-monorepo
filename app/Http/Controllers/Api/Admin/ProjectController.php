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

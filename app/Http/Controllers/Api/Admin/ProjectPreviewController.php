<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectPreviewController extends Controller
{
    public function featured(): JsonResponse
    {
        $featured = Project::where('is_featured', true)
            ->orderBy('featured_order')
            ->select(['id', 'title', 'slug', 'hero_url', 'gif_url', 'info', 'is_featured', 'featured_order'])
            ->get();

        return response()->json($featured);
    }

    public function available(): JsonResponse
    {
        $all = Project::where('is_published', true)
            ->where('is_featured', false)
            ->orderBy('order')
            ->select(['id', 'title', 'slug', 'hero_url', 'gif_url', 'info'])
            ->get();

        return response()->json($all);
    }

    public function toggle(Request $request, Project $project): JsonResponse
    {
        if (!$project->is_featured) {
            $maxOrder = Project::where('is_featured', true)->max('featured_order') ?? -1;
            $project->update([
                'is_featured' => true,
                'featured_order' => $maxOrder + 1,
            ]);
        } else {
            $project->update([
                'is_featured' => false,
                'featured_order' => 0,
            ]);
        }

        return response()->json($project);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:projects,id',
            'order.*.featured_order' => 'required|integer',
        ]);

        foreach ($data['order'] as $item) {
            Project::where('id', $item['id'])->update([
                'featured_order' => $item['featured_order'],
            ]);
        }

        return response()->json(['message' => 'Featured projects reordered']);
    }
}

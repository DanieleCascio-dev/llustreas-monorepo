<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Models\HeroLayer;
use App\Models\InstagramPost;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    public function projects(): JsonResponse
    {
        $projects = Project::published()
            ->ordered()
            ->select(['id', 'title', 'slug', 'hero_url', 'gif_url', 'layout', 'order', 'description', 'info'])
            ->get();

        return response()->json($projects);
    }

    public function project(string $slug): JsonResponse
    {
        $project = Project::published()
            ->where('slug', $slug)
            ->with([
                'images' => fn ($q) => $q->orderBy('order'),
                'images.textBlock.paragraphs' => fn ($q) => $q->orderBy('order'),
            ])
            ->firstOrFail();

        return response()->json($this->formatProject($project));
    }

    public function gallery(): JsonResponse
    {
        $images = GalleryImage::where('is_preview', false)
            ->orderBy('column_index')
            ->orderBy('order')
            ->get();

        $columns = [];
        for ($i = 0; $i < 3; $i++) {
            $columns[$i] = $images->where('column_index', $i)->values()->map(fn ($img) => [
                'id' => $img->id,
                'src' => $img->src,
                'title' => $img->title,
            ]);
        }

        return response()->json($columns);
    }

    public function projectsPreview(): JsonResponse
    {
        $featured = Project::where('is_published', true)
            ->where('is_featured', true)
            ->orderBy('featured_order')
            ->select(['id', 'title', 'slug', 'gif_url', 'info'])
            ->get();

        return response()->json($featured);
    }

    public function galleryPreview(): JsonResponse
    {
        $images = GalleryImage::where('is_preview', true)
            ->orderBy('order')
            ->get()
            ->map(fn ($img) => [
                'id' => $img->id,
                'url' => $img->src,
                'title' => $img->title,
            ]);

        return response()->json($images);
    }

    public function hero(): JsonResponse
    {
        $layers = HeroLayer::ordered()->with('images')->get();

        return response()->json($layers);
    }

    public function instagram(): JsonResponse
    {
        $posts = InstagramPost::active()->ordered()->get(['id', 'url', 'thumbnail']);

        return response()->json($posts);
    }

    public function setting(string $key): JsonResponse
    {
        $value = SiteSetting::getValue($key);

        if ($value === null) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        return response()->json(['key' => $key, 'value' => $value]);
    }

    private function formatProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'title' => $project->title,
            'slug' => $project->slug,
            'hero' => $project->hero_url,
            'gif' => $project->gif_url,
            'layout' => $project->layout,
            'order' => $project->order,
            'description' => $project->description,
            'info' => $project->info,
            'images' => $project->images->map(function ($image) {
                if ($image->type === 'text_image_block' && $image->textBlock) {
                    return [
                        'id' => $image->id,
                        'type' => 'textImageBlock',
                        'layout' => $image->textBlock->layout,
                        'imagePosition' => $image->textBlock->image_position,
                        'image' => [
                            'id' => $image->id,
                            'src' => $image->src,
                        ],
                        'text' => [
                            'subtitle' => $image->textBlock->subtitle,
                            'title' => $image->textBlock->title,
                            'textColor' => $image->textBlock->text_color,
                            'backgroundColor' => $image->textBlock->background_color,
                            'paragraphs' => $image->textBlock->paragraphs->map(fn ($p) => [
                                'title' => $p->title,
                                'text' => $p->text,
                                'textHtml' => $p->text_html,
                            ]),
                        ],
                    ];
                }

                return [
                    'id' => $image->id,
                    'src' => $image->src,
                ];
            }),
        ];
    }
}

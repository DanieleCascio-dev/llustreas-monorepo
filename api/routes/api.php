<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\Admin\ProjectController;
use App\Http\Controllers\Api\Admin\ProjectImageController;
use App\Http\Controllers\Api\Admin\GalleryController;
use App\Http\Controllers\Api\Admin\GalleryPreviewController;
use App\Http\Controllers\Api\Admin\ProjectPreviewController;
use App\Http\Controllers\Api\Admin\CloudinaryController;
use App\Http\Controllers\Api\Admin\HeroController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\InstagramController;
use Illuminate\Support\Facades\Route;

// Public endpoints (frontoffice)
Route::prefix('public')->group(function () {
    Route::get('/projects', [PublicController::class, 'projects']);
    Route::get('/projects/{slug}', [PublicController::class, 'project']);
    Route::get('/gallery', [PublicController::class, 'gallery']);
    Route::get('/gallery-preview', [PublicController::class, 'galleryPreview']);
    Route::get('/projects-preview', [PublicController::class, 'projectsPreview']);
    Route::get('/settings/{key}', [PublicController::class, 'setting']);
    Route::get('/hero', [PublicController::class, 'hero']);
    Route::get('/instagram', [PublicController::class, 'instagram']);
});

// Auth
Route::middleware('web')->post('/login', [AuthController::class, 'login']);

// Admin endpoints (backoffice, protected)
Route::middleware(['web', 'auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::prefix('admin')->group(function () {
        // Projects
        Route::apiResource('projects', ProjectController::class);
        Route::post('/projects/{project}/duplicate', [ProjectController::class, 'duplicate']);
        Route::put('/projects-reorder', [ProjectController::class, 'reorder']);

        // Project images
        Route::get('/projects/{project}/images', [ProjectImageController::class, 'index']);
        Route::post('/projects/{project}/images', [ProjectImageController::class, 'store']);
        Route::put('/projects/{project}/images/{image}', [ProjectImageController::class, 'update']);
        Route::delete('/projects/{project}/images/{image}', [ProjectImageController::class, 'destroy']);
        Route::put('/projects/{project}/images-reorder', [ProjectImageController::class, 'reorder']);

        // Gallery
        Route::apiResource('gallery', GalleryController::class);
        Route::put('/gallery-reorder', [GalleryController::class, 'reorder']);

        // Projects Preview (featured)
        Route::get('/projects-preview', [ProjectPreviewController::class, 'featured']);
        Route::get('/projects-preview/available', [ProjectPreviewController::class, 'available']);
        Route::put('/projects-preview/{project}/toggle', [ProjectPreviewController::class, 'toggle']);
        Route::put('/projects-preview-reorder', [ProjectPreviewController::class, 'reorder']);

        // Cloudinary browser
        Route::get('/cloudinary/folders', [CloudinaryController::class, 'folders']);
        Route::post('/cloudinary/folders', [CloudinaryController::class, 'createFolder']);
        Route::delete('/cloudinary/folders', [CloudinaryController::class, 'deleteFolder']);
        Route::get('/cloudinary/images', [CloudinaryController::class, 'images']);
        Route::post('/cloudinary/sign', [CloudinaryController::class, 'sign']);

        // Site settings
        Route::get('/settings/{key}', [SettingController::class, 'show']);
        Route::put('/settings/{key}', [SettingController::class, 'update']);

        // Hero layers
        Route::get('/hero', [HeroController::class, 'index']);
        Route::post('/hero', [HeroController::class, 'store']);
        Route::put('/hero/{layer}', [HeroController::class, 'update']);
        Route::delete('/hero/{layer}', [HeroController::class, 'destroy']);
        Route::put('/hero-reorder', [HeroController::class, 'reorder']);
        Route::post('/hero/{layer}/images', [HeroController::class, 'imageStore']);
        Route::put('/hero/{layer}/images/{image}', [HeroController::class, 'imageUpdate']);
        Route::delete('/hero/{layer}/images/{image}', [HeroController::class, 'imageDestroy']);
        Route::put('/hero/{layer}/images-reorder', [HeroController::class, 'imageReorder']);

        // Instagram posts
        Route::get('/instagram', [InstagramController::class, 'index']);
        Route::post('/instagram', [InstagramController::class, 'store']);
        Route::put('/instagram/{instagram}', [InstagramController::class, 'update']);
        Route::delete('/instagram/{instagram}', [InstagramController::class, 'destroy']);
        Route::post('/instagram/{instagram}/fetch-thumbnail', [InstagramController::class, 'fetchThumbnail']);
        Route::put('/instagram-reorder', [InstagramController::class, 'reorder']);

        // Gallery Preview
        Route::get('/gallery-preview', [GalleryPreviewController::class, 'index']);
        Route::post('/gallery-preview', [GalleryPreviewController::class, 'store']);
        Route::put('/gallery-preview/{galleryPreview}', [GalleryPreviewController::class, 'update']);
        Route::delete('/gallery-preview/{galleryPreview}', [GalleryPreviewController::class, 'destroy']);
        Route::put('/gallery-preview-reorder', [GalleryPreviewController::class, 'reorder']);
    });
});

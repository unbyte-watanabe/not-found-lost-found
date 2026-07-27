<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AnalyzeImageService;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(
        private readonly ImageUploadService  $imageUploadService,
        private readonly AnalyzeImageService $analyzeImageService,
    ) {
    }

    /**
     * Upload an image file and return its public URL.
     */
    public function image(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $url = $this->imageUploadService->store($request->file('image'));

        return response()->json(['url' => $url], 201);
    }

    /**
     * Analyze an image by URL and return detected category, subCategory, features.
     */
    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'imageUrl' => ['required', 'string', 'url', 'max:500'],
        ]);

        $result = $this->analyzeImageService->analyze($validated['imageUrl']);

        return response()->json($result);
    }
}

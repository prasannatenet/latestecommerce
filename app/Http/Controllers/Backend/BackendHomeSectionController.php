<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BackendHomeSectionController extends Controller
{
    public function __construct(
        protected ImageOptimizationService $imageService
    ) {}

    public function index()
    {
        $sections = HomeSection::orderBy('sort_order')->orderByDesc('id')->get();

        return view('backend.home-sections.index', compact('sections'));
    }

    public function edit(HomeSection $homeSection)
    {
        $categories = \App\Models\Category::orderBy('position')->orderBy('name')->get();
        return view('backend.home-sections.edit', compact('homeSection', 'categories'));
    }

    public function update(Request $request, HomeSection $homeSection)
    {
        $data = $this->validateData($request, $homeSection);
        $oldPoster = $homeSection->poster_image_path;
        $oldLarge = $homeSection->large_image_path;

        if ($request->hasFile('video')) {
            $this->deleteOldFile($homeSection->video_path);
            $data['video_path'] = $request->file('video')->store('home-sections/videos', 'public');
        }

        if ($request->hasFile('poster_image')) {
            $data['poster_image_path'] = $this->imageService->optimizeAndStore(
                $request->file('poster_image'),
                'home-sections/posters',
                'banner',
                fieldKey: 'poster_image'
            );
        }

        if ($request->hasFile('large_image')) {
            $data['large_image_path'] = $this->imageService->optimizeAndStore(
                $request->file('large_image'),
                'home-sections/large-images',
                'banner',
                fieldKey: 'large_image'
            );
        }

        $data['is_active'] = $request->boolean('is_active', false);

        $homeSection->update($data);

        // Only after update succeeds, delete previous images if not reused
        if ($request->hasFile('poster_image') && ! empty($oldPoster) && $oldPoster !== ($data['poster_image_path'] ?? null)) {
            $this->imageService->deleteIfUnused($oldPoster, function ($path) use ($homeSection) {
                return HomeSection::where('poster_image_path', $path)->where('id', '!=', $homeSection->id)->exists();
            });
        }

        if ($request->hasFile('large_image') && ! empty($oldLarge) && $oldLarge !== ($data['large_image_path'] ?? null)) {
            $this->imageService->deleteIfUnused($oldLarge, function ($path) use ($homeSection) {
                return HomeSection::where('large_image_path', $path)->where('id', '!=', $homeSection->id)->exists();
            });
        }

        return redirect()->route('admin.home-sections.index')
            ->with('success', 'Home section updated successfully.');
    }

    public function destroy(HomeSection $homeSection)
    {
        if (in_array($homeSection->section_key, ['featured', 'large-image'])) {
            return redirect()->route('admin.home-sections.index')
                ->with('error', 'The default home sections cannot be deleted. You can deactivate them instead.');
        }

        $this->deleteOldFile($homeSection->video_path);
        $oldPoster = $homeSection->poster_image_path;
        $oldLarge = $homeSection->large_image_path;

        $homeSection->delete();

        if (! empty($oldPoster)) {
            $this->imageService->deleteIfUnused($oldPoster, function ($path) {
                return HomeSection::where('poster_image_path', $path)->exists();
            });
        }

        if (! empty($oldLarge)) {
            $this->imageService->deleteIfUnused($oldLarge, function ($path) {
                return HomeSection::where('large_image_path', $path)->exists();
            });
        }

        return redirect()->route('admin.home-sections.index')
            ->with('success', 'Home section deleted successfully.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'required|integer|exists:home_sections,id',
        ]);

        $orderedIds = array_values(array_unique($data['ordered_ids']));

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                HomeSection::whereKey($id)->update(['sort_order' => $index]);
            }
        });

        return response()->json(['message' => 'Home section order updated successfully.']);
    }

    /**
     * Delete an old stored file if it exists.
     */
    private function deleteOldFile(?string $path): void
    {
        if (! empty($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Validate the request data for updating a home section.
     */
    private function validateData(Request $request, HomeSection $homeSection): array
    {
        $rules = [
            'title' => 'nullable|string|max:255',
            'category_name' => 'nullable|string|max:255',
            'subtext' => 'nullable|string|max:500',
            'cta_text' => 'nullable|string|max:100',
            'cta_link' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ];

        if ($homeSection->section_key === 'featured') {
            $rules['video'] = 'nullable|file|mimes:mp4|max:51200';
            $rules['poster_image'] = 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120';
        }

        if ($homeSection->section_key === 'large-image') {
            $rules['large_image'] = 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120';
        }

        return $request->validate($rules);
    }
}
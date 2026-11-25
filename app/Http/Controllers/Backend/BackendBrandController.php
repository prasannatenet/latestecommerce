<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;

class BackendBrandController extends Controller
{
    public function __construct(
        protected ImageOptimizationService $imageService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::withCount('products')->latest()->get();
        return view('backend.brand.index', compact('brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.brand.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->imageService->optimizeAndStore(
                $request->file('logo'),
                'brands',
                'brand',
                fieldKey: 'logo'
            );
        }

        Brand::create($validated);

        return redirect()->route('admin.brands.index')
            ->with('success', 'Brand created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        return view('backend.brand.show', compact('brand'));
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Brand $brand)
    {
        return view('backend.brand.edit', compact('brand'));
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $oldLogo = $brand->logo;

        // Handle new logo
        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->imageService->optimizeAndStore(
                $request->file('logo'),
                'brands',
                'brand',
                fieldKey: 'logo'
            );
        }

        $brand->update($validated);

        // Only after update succeeds, delete previous logo if no longer referenced
        if ($request->hasFile('logo') && ! empty($oldLogo) && $oldLogo !== ($validated['logo'] ?? null)) {
            $this->imageService->deleteIfUnused($oldLogo, function ($path) use ($brand) {
                return Brand::where('logo', $path)->where('id', '!=', $brand->id)->exists();
            });
        }

        return redirect()->route('admin.brands.index')
            ->with('success', 'Brand updated successfully.');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        $oldLogo = $brand->logo;

        $brand->delete();

        if (! empty($oldLogo)) {
            $this->imageService->deleteIfUnused($oldLogo, function ($path) {
                return Brand::where('logo', $path)->exists();
            });
        }

        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted successfully.');
    }
}

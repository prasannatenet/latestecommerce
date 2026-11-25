<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackendCategoryController extends Controller
{
    public function __construct(
        protected ImageOptimizationService $imageService
    ) {}

    public function index()
    {
        // Show only root categories with nested children
        $categories = Category::whereNull('parent_id')
            ->with('childrenRecursive')
            ->withCount('products')
            ->orderBy('position')
            ->get();

        $allCategories = Category::withCount('products')->get();

        return view('backend.categories.index', compact('categories', 'allCategories'));
    }

    public function create()
    {
        $categories = Category::whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('position')
            ->get();

        return view('backend.categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'position'    => 'nullable|integer',
            'parent_id'   => 'nullable|exists:categories,id',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:20480',
        ]);

        $data = $request->only('name', 'description', 'position', 'parent_id');

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->optimizeAndStore(
                $request->file('image'),
                'categories',
                'category',
                fieldKey: 'image'
            );
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(Category $category)
    {
        $category->load('parent', 'children.products', 'products');
        return view('backend.categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        $categories = Category::whereNull('parent_id')
            ->with('childrenRecursive')
            ->where('id', '!=', $category->id)
            ->orderBy('position')
            ->get();

        return view('backend.categories.edit', compact('category', 'categories'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'position'    => 'nullable|integer',
            'parent_id'   => 'nullable|exists:categories,id',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:20480',
        ]);

        $data = $request->only('name', 'description', 'position', 'parent_id');
        $oldImage = $category->image;

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->optimizeAndStore(
                $request->file('image'),
                'categories',
                'category',
                fieldKey: 'image'
            );
        }

        $category->update($data);

        // Only after the database update succeeds, delete the previous image if no longer used
        if ($request->hasFile('image') && ! empty($oldImage) && $oldImage !== ($data['image'] ?? null)) {
            $this->imageService->deleteIfUnused($oldImage, function ($path) use ($category) {
                return Category::where('image', $path)->where('id', '!=', $category->id)->exists();
            });
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $oldImage = $category->image;

        $category->delete();

        // Safely delete image after record deletion if no other record references it
        if (! empty($oldImage)) {
            $this->imageService->deleteIfUnused($oldImage, function ($path) {
                return Category::where('image', $path)->exists();
            });
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}

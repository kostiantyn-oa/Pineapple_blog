<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::with('parentCategory')->get();
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function show($id)
    {
        $category = BlogCategory::with('parentCategory')->find($id);

        return $category
            ? response()->json(['success' => true, 'data' => $category])
            : response()->json(['success' => false, 'message' => 'Категорію не знайдено'], 404);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:blog_categories,id',
            'description' => 'nullable|string|max:1000',
        ]);

        $category = BlogCategory::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'parent_id' => $request->parent_id ?: BlogCategory::ROOT,
            'description' => $request->description,
        ]);

        $category->load('parentCategory');

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Категорію успішно створено'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = BlogCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категорію не знайдено'
            ], 404);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:blog_categories,id',
                Rule::notIn([$id])
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        $category->update([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'parent_id' => $request->parent_id ?: BlogCategory::ROOT,
            'description' => $request->description,
        ]);

        $category->load('parentCategory');

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Категорію успішно оновлено'
        ]);
    }

    public function destroy($id)
    {
        $category = BlogCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категорію не знайдено'
            ], 404);
        }

        if ($category->isRoot()) {
            return response()->json([
                'success' => false,
                'message' => 'Неможливо видалити кореневу категорію'
            ], 422);
        }

        $childCategories = BlogCategory::where('parent_id', $id)->count();
        if ($childCategories > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Неможливо видалити категорію, яка має дочірні категорії'
            ], 422);
        }

        $postsCount = $category->posts()->count();
        if ($postsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Неможливо видалити категорію, в якій є пости'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Категорію успішно видалено'
        ]);
    }

    public function getParentCategories()
    {
        $categories = BlogCategory::select('id', 'title', 'parent_id')
            ->orderBy('title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}

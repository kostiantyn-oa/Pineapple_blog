<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index()
    {
        $posts = BlogPost::with(['user', 'category'])->get();
        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    public function show($id)
    {
        $post = BlogPost::with(['user', 'category'])->find($id);
        return $post
            ? response()->json(['success' => true, 'data' => $post])
            : response()->json(['success' => false, 'message' => 'Пост не знайдено'], 404);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:blog_categories,id',
            'excerpt' => 'nullable|string|max:500',
            'content_raw' => 'required|string',
            'is_published' => 'boolean',
        ]);

        $post = BlogPost::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'category_id' => $request->category_id,
            'excerpt' => $request->excerpt,
            'content_raw' => $request->content_raw,
            'is_published' => $request->is_published ?? false,
            'published_at' => $request->is_published ? now() : null,
            'user_id' => BlogPost::UNKNOWN_USER,
        ]);

        $post->load(['user', 'category']);

        return response()->json([
            'success' => true,
            'data' => $post,
            'message' => 'Пост успішно створено'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $post = BlogPost::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Пост не знайдено'
            ], 404);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:blog_categories,id',
            'excerpt' => 'nullable|string|max:500',
            'content_raw' => 'required|string',
            'is_published' => 'boolean',
        ]);

        $post->update([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'category_id' => $request->category_id,
            'excerpt' => $request->excerpt,
            'content_raw' => $request->content_raw,
            'is_published' => $request->is_published ?? false,
            'published_at' => $request->is_published ? ($post->published_at ?? now()) : null,
        ]);

        $post->load(['user', 'category']);

        return response()->json([
            'success' => true,
            'data' => $post,
            'message' => 'Пост успішно оновлено'
        ]);
    }

    public function destroy($id)
    {
        $post = BlogPost::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Пост не знайдено'
            ], 404);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Пост успішно видалено'
        ]);
    }
}

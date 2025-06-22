<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;

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

    public function getPost($id)
    {
        $post = BlogPost::with(['user', 'category'])->find($id);

        return $post
            ? response()->json(['success' => true, 'data' => $post])
            : response()->json(['success' => false, 'message' => 'Пост не знайдено'], 404);
    }
}

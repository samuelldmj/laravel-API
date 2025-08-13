<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Str;
use function Symfony\Component\Clock\now;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //validating request
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'content' => 'required',
            'user_id' => 'required|numeric',
            'category_id' => 'required|numeric',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        //if validator fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        //check the authenticated user
        if (auth()->user()->id != $request->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to create a post for this user.',
            ], 403);
        }

        //check if category exists
        if (!Category::where('id', $request->category_id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category does not exist.',
            ], 404);
        }


        //upload thumbnail
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $thumbnailName = time() . '.' . $thumbnail->getClientOriginalExtension();
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        //create post
        $post['title'] = $request->title;
        $post['content'] = $request->content;
        $post['excerpt'] = $request->excerpt;
        $post['slug'] = Str::slug($request->title);
        $post['user_id'] = $request->user_id;
        $post['category_id'] = $request->category_id;
        $post['thumbnail'] = $thumbnailPath ?? null;
        if (Auth::user()->role === 'admin') {
            $post['status'] = 'published';
            $post['published_at'] = Carbon::now();
        }


        try {
            //create post
            $post = Post::create($post);
        } catch (QueryException $e) {
            Log::error('Post creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while creating the post.'
            ], 500);
        }

        //return  successful response
        return response()->json([
            'status' => 'success',
            'message' => 'Post successfully created',
            'data' => new PostResource($post)
        ], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

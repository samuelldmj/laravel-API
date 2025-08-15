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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Post::with('category', 'user');

        // Filtering by category slug
        if ($request->has('category_slug')) {
            $category = Category::where('slug', $request->category_slug)->first();

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found.'
                ], 404);
            }

            $query->where('category_id', $category->id);
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        // Validate sort and direction
        $validSorts = ['title', 'created_at', 'updated_at'];
        $validDirections = ['asc', 'desc'];

        if (in_array($sort, $validSorts) && in_array($direction, $validDirections)) {
            $query->orderBy($sort, $direction);
        } else {
            // Default to sorting by creation date if invalid parameters are provided
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $posts = $query->paginate(10);

        return PostResource::collection($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'excerpt' => 'nullable|string',
        ]);

        // Authorization check
        // The user_id should be inferred from the authenticated user, not provided in the request
        $user = Auth::user();

        // Handle thumbnail upload
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        try {
            // Generating a base slug from the title
            $baseSlug = Str::slug($request->title);
            $slug = $baseSlug;
            $counter = 1;

            // Checking if the slug already exists and append a number if it does
            while (Post::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $post = new Post([
                'title' => $request->title,
                'content' => $request->content,
                'excerpt' => $request->excerpt,
                'slug' => $slug,
                'user_id' => $user->id,
                'category_id' => $request->category_id,
                'thumbnail' => $thumbnailPath,
            ]);

            if ($user->hasRole('admin')) {
                $post->status = 'published';
                $post->published_at = Carbon::now();
            }

            $post->save();

        } catch (QueryException $e) {
            // Log the error for debugging purposes
            Log::error('Post creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while creating the post.'
            ], 500);
        }

        return (new PostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        // The Post model is automatically resolved by Route-Model Binding
        return new PostResource($post->load('category', 'user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        // Authorization check
        if (Auth::user()->id !== $post->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update this post.',
            ], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'excerpt' => 'nullable|string',
        ]);

        // Handle thumbnail upload/removal
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail if it exists
            if ($post->thumbnail) {
                Storage::disk('public')->delete($post->thumbnail);
            }
            $post->thumbnail = $request->file('thumbnail')->store('thumbnails', 'public');
        } elseif ($request->input('thumbnail') === null) {
            // Handle case where thumbnail is explicitly removed
            if ($post->thumbnail) {
                Storage::disk('public')->delete($post->thumbnail);
            }
            $post->thumbnail = null;
        }

        $post->update([
            'title' => $request->input('title', $post->title),
            'content' => $request->input('content', $post->content),
            'excerpt' => $request->input('excerpt', $post->excerpt),
            'slug' => $request->has('title') ? Str::slug($request->title) : $post->slug,
            'category_id' => $request->input('category_id', $post->category_id),
            'thumbnail' => $post->thumbnail, // Ensure the new path is saved
        ]);

        return new PostResource($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        // Authorization check
        if (Auth::user()->id !== $post->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete this post.',
            ], 403);
        }

        // Delete thumbnail from storage
        if ($post->thumbnail) {
            Storage::disk('public')->delete($post->thumbnail);
        }

        $post->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Post deleted successfully',
        ], 200);
    }
}
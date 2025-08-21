<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\Seo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostController extends Controller
{

    use AuthorizesRequests;
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
            'meta_title' => 'required',
            'meta_description' => 'required',
            'meta_keywords' => 'required'

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

            DB::beginTransaction();

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
            }

            if ($user->hasRole('admin') || $user->hasRole('author')) {
                $post->published_at = Carbon::now();
            }

            $post->save();


            //seo creation
            $seo = [
                'post_id' => $post->id,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
            ];

            Seo::create($seo);

            DB::commit();
            $post = $post->fresh('seo');

        } catch (QueryException $e) {
            DB::rollBack();
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
        // 1. AUTHORIZATION
        $this->authorize('update', $post);

        // Handle method override for multipart/form-data
        if ($request->has('_method')) {
            $request->setMethod($request->input('_method'));
        }

        // 2. VALIDATION
        $rules = [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'excerpt' => 'nullable|string',
            'meta_title' => 'sometimes',
            'meta_description' => 'sometimes',
            'meta_keywords' => 'sometimes'
        ];

        // Add category_id validation if present
        if ($request->has('category_id')) {
            $categoryId = $request->input('category_id');

            // Convert string to integer if it's a numeric string
            if (is_string($categoryId) && is_numeric($categoryId)) {
                $request->merge(['category_id' => (int) $categoryId]);
            }

            $rules['category_id'] = 'required|integer|exists:categories,id';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        // Handle slug update only if a new title is provided
        if ($request->has('title')) {
            // Generate a unique slug
            $baseSlug = Str::slug($request->title);
            $slug = $baseSlug;
            $counter = 1;

            // Check if the slug already exists (excluding current post)
            while (Post::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $validatedData['slug'] = $slug;
        }

        // Handle thumbnail logic
        if ($request->hasFile('thumbnail')) {
            // If a new thumbnail is uploaded, delete the old one first
            if ($post->thumbnail) {
                Storage::disk('public')->delete($post->thumbnail);
            }
            $validatedData['thumbnail'] = $request->file('thumbnail')->store('thumbnails', 'public');
        } elseif ($request->has('thumbnail') && $request->input('thumbnail') === null) {
            // If the 'thumbnail' key exists in the request and its value is null
            if ($post->thumbnail) {
                Storage::disk('public')->delete($post->thumbnail);
                $validatedData['thumbnail'] = null;
            }
        }

        // Handle post status changes based on user role
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            $validatedData['status'] = 'published';
            $validatedData['published_at'] = Carbon::now();
        }

        DB::beginTransaction();


        // 3. PERSIST THE CHANGES
        $post->update($validatedData);

        //seo update
        $seo = Seo::where('post_id', $post->id)->first();
        $seo->meta_title = $request->meta_title;
        $seo->meta_description = $request->meta_description;
        $seo->meta_keywords = $request->meta_keywords;

        $seo->save();

        DB::commit();
        $post = $post->fresh('seo');


        // 4. RETURN A RESOURCE
        return new PostResource($post->refresh()->load('category', 'user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        // Authorization check
        $user = Auth::user();
        if ($user->id !== $post->user_id && !$user->hasRole('admin')) {
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



    public function deleteThumbnail(Post $post)
    {
        // Authorize the action using a policy
        // This is a good practice to ensure only authorized users can delete a thumbnail.
        $this->authorize('update', $post); // Assuming thumbnail deletion is part of updating a post

        // Check if the post has a thumbnail to delete
        if ($post->thumbnail) {
            // Delete the file from storage
            Storage::disk('public')->delete($post->thumbnail);

            // Clear the thumbnail path from the database
            $post->thumbnail = null;
            $post->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Thumbnail deleted successfully.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No thumbnail found to delete.'
        ], 404);
    }


}
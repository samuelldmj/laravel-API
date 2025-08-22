<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LikeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function react(Request $request)
    {
        // 1. Validate the request data
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer|exists:posts,id',
            'status' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();
        $userId = Auth::id();

        // 2. Find an existing reaction from the user on this post
        $reaction = Like::where('user_id', $userId)
            ->where('post_id', $validated['post_id'])
            ->first();

        // 3. Handle the reaction logic
        if ($reaction) {
            if ($reaction->status === $validated['status']) {
                // Reaction already exists with the same status, so remove it.
                $reaction->delete();
                return response()->json(['status' => 'success', 'message' => 'Reaction removed'], 200);
            } else {
                // Reaction exists with a different status, so update it.
                $reaction->status = $validated['status'];
                $reaction->save();
                return response()->json(['status' => 'success', 'message' => 'Reaction updated'], 200);
            }
        } else {
            // No existing reaction, so create a new one.
            Like::create([
                'user_id' => $userId,
                'post_id' => $validated['post_id'],
                'status' => $validated['status']
            ]);
            return response()->json(['status' => 'success', 'message' => 'Reaction added'], 201);
        }
    }




    //alternatively

    //     public function react(Request $request)
//     {
//         // Validate request data using Laravel's fluent validation syntax
//         $validated = $request->validate([
//             'post_id' => ['required', 'integer', 'exists:posts,id'],
//             'status' => ['required', 'integer']
//         ]);
// 
//         $userId = Auth::id();
// 
//         // Find existing reaction or create a new one
//         $reaction = Like::firstOrNew([
//             'user_id' => $userId,
//             'post_id' => $validated['post_id']
//         ]);
// 
//         // Handle reaction logic
//         if ($reaction->exists && $reaction->status === $validated['status']) {
//             // Reaction exists with same status, so delete it (un-react)
//             $reaction->delete();
//             return response()->json(['status' => 'success', 'message' => 'Reaction removed'], 200);
//         } else {
//             // Update status or create new reaction
//             $reaction->status = $validated['status'];
//             $reaction->save();
// 
//             $message = $reaction->wasRecentlyCreated ? 'Reaction added' : 'Reaction updated';
//             $statusCode = $reaction->wasRecentlyCreated ? 201 : 200;
// 
//             return response()->json(['status' => 'success', 'message' => $message], $statusCode);
//         }
//     }




    public function reactions(Request $request, Post $post)
    {
        // Count the number of likes for the post
        $likeCount = Like::where('post_id', $post->id)
            ->where('status', 1)
            ->count();

        // Count the number of dislikes for the post
        $dislikeCount = Like::where('post_id', $post->id)
            ->where('status', 0)
            ->count();

        return response()->json([
            'status' => 'success',
            'likes' => $likeCount,
            'dislikes' => $dislikeCount,
            'post_id' => $post->id,
            'instance' => $post
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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

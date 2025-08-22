<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $comments = Comment::with(['user', 'post'])->paginate(15);

        // Return the paginated collection using the API Resource
        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = validator()->make($request->all(), [
            'content' => 'required|string',
            'post_id' => 'required|exists:posts,id',
        ]);

        // Handle validation failure
        if ($validator->fails()) {
            return response()->json([
                'status' => 'fails',
                'message' => $validator->errors()
            ], 400);
        }

        // Get the validated data and add the user ID
        $validatedData = $validator->validated();
        $validatedData['user_id'] = Auth::id();


        //create a comment
        Comment::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Comment created successfully'
        ], 201);

    }


    public function changeStatus(Request $request, Comment $comment)
    {
        // Validate the 'status' input. The 'comment_id' is handled by Route Model Binding.
        $validator = validator($request->all(), [
            'status' => 'required|string',
        ]);

        // Handle validation failure
        if ($validator->fails()) {
            return response()->json([
                'status' => 'fails',
                'message' => $validator->errors()
            ], 400);
        }

        // Get the validated data
        $validatedData = $validator->validated();

        // Update the status of the comment found by Route Model Binding
        $comment->update([
            'status' => $validatedData['status'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status changed successfully'
        ]);
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

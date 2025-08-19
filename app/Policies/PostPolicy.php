<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        // A user can update a post if they are the owner OR if they are an admin.
        return $user->id === $post->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        // A user can delete a post if they are the owner OR if they are an admin.
        return $user->id === $post->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create a post.
     * Assuming all authenticated users can create a post, but you could add a role check here.
     */
    public function create(User $user): bool
    {
        // Example: Only authenticated users with the 'author' or 'admin' role can create posts.
        return $user->hasRole('admin') || $user->hasRole('author');
    }
}
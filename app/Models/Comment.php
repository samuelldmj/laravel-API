<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['parent_id', 'user_id', 'post_id', 'status', 'content'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}

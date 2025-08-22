<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'thumbnail',
        'published_at',
        'status'
    ];


    /**
     * Get the category that owns the post.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }


    /**
     * Get the user that wrote the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function seo()
    {
        return $this->hasOne(Seo::class);
    }


    public function comment()
    {
        return $this->hasMany(Comment::class);
    }
}

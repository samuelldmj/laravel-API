<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{

    public $timestamps = true;

    const UPDATED_AT = null;
    protected $fillable = ['post_id', 'user_id', 'status'];
}






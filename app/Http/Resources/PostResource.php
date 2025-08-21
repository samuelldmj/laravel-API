<?php

namespace App\Http\Resources;

use App\Models\Seo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'thumbnail' => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'seo' => $this->whenLoaded('seo')
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
            // 'category' => $this->whenLoaded('category'),
            // 'user' => $this->whenLoaded('user'),
        ];
    }
}

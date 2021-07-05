<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'user' => $this->author,
            'category' => $this->category,
            'tags' => $this->tags,
            'comments' => new CommentCollectionResource($this->comments),
            'thumbnail' => new AssetResource($this->thumbnail),
            'created_at' => $this->created_at,
        ];
    }
}

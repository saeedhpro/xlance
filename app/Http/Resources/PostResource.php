<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
            'caption' => $this->caption,
            'lat' => $this->lat,
            'long' => $this->long,
            'liked' => $this->liked(),
            'bookmarked' => $this->marked(),
            'saved' => $this->savedPost(),
            'user' => new SimpleUserResource($this->user),
            'media' => new AssetResource($this->media),
            'comments' => new CommentCollectionResource($this->comments),
            'comments_count' => $this->comments()->count(),
            'likes' => new SimpleUserCollectionResource($this->likers),
            'likes_count' => $this->likers()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "article_type" => $this->article_type,
            "featured_image" => isset($this->featured_image) ? asset('content/articles/featured_image'). '/' .$this->featured_image : NULL,
            "article_type" => $this->article_type,
            "school_year" => $this->school_year,
            "title" => $this->title,
            "content" => $this->content,
            "link_to_article" => $this->link_to_article,
            "slug" => $this->slug,
            "posting_date" => $this->posting_date,
        ];
    }
}

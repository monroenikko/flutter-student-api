<?php

namespace App\Http\Resources;

use App\Http\Resources\ArticleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleListsResource extends JsonResource
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
            'data' => $this->map(function ($item) {
                return new ArticleResource($item);
            }),
            'pagination' => [
                'total' => (int) $this->total(),
                'count' => (int) is_null($this->lastItem()) ? 0 : $this->lastItem(),
                'per_page' => (int)$this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => (int) $this->lastPage(),
                'previous_page_url' => is_null($this->previousPageUrl()) ? '0' : $this->previousPageUrl(),
                'last_page' => $this->lastPage(),
                'first_page_url' => $this->onFirstPage(),
                'next_page_url' => $this->nextPageUrl(),
                'from' => $this->lastItem(),
                'to' =>(int)$this->perPage(),
            ]
        ];
    }
}

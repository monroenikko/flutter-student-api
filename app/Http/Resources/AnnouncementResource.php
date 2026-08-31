<?php

namespace App\Http\Resources;

use App\Models\Announcement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AnnouncementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $userStatus = (int) ($this->user_status ?? $this->resource['user_status'] ?? 1);
        $publishedAt = $this->published_at ? Carbon::parse($this->published_at) : null;
        $content = $this->content ?? '';

        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'slug'            => $this->slug,
            'content'         => $content,
            'content_preview' => Str::limit(strip_tags($content), 180),
            'audience'        => (int) $this->audience,
            'audience_label'  => Announcement::AUDIENCE[$this->audience] ?? 'All Students',
            'status'          => $userStatus,
            'status_label'    => Announcement::RECIPIENT_STATUS[$userStatus] ?? 'Unread',
            'status_color'    => Announcement::RECIPIENT_STATUS_DESIGN[$userStatus] ?? 'badge-danger',
            'published_at'    => $publishedAt ? $publishedAt->format('M d, Y h:i A') : '-',
            'published_at_iso'=> $publishedAt?->toIso8601String(),
            'is_read'         => $userStatus === 2,
            'is_archived'     => $userStatus === 3,
        ];
    }
}

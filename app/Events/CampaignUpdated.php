<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Campaign $campaign) {}

    public function broadcastOn(): Channel
    {
        return new Channel('campaigns');
    }

    public function broadcastAs(): string
    {
        return 'campaign.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'                  => $this->campaign->id,
            'title'               => $this->campaign->title,
            'slug'                => $this->campaign->slug,
            'description'         => $this->campaign->description,
            'image'               => $this->campaign->image,
            'category'            => $this->campaign->category,
            'target_amount'       => $this->campaign->target_amount,
            'collected_amount'    => $this->campaign->collected_amount,
            'donor_count'         => $this->campaign->donor_count,
            'progress_percentage' => $this->campaign->progress_percentage,
            'days_left'           => $this->campaign->days_left,
            'status'              => $this->campaign->status,
            'is_featured'         => $this->campaign->is_featured,
        ];
    }
}

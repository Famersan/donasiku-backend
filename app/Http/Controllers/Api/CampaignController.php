<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\CampaignUpdated;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with('user:id,name,avatar')
            ->where('status', 'active');

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->featured) {
            $query->where('is_featured', true);
        }

        $campaigns = $query
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate(12);

        return response()->json([
            'campaigns' => $campaigns->items(),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'last_page'    => $campaigns->lastPage(),
                'total'        => $campaigns->total(),
            ],
        ]);
    }

    public function show(string $slug)
    {
        $campaign = Campaign::with([
            'user:id,name,avatar',
            'donations' => fn($q) => $q->where('status', 'success')
                ->latest('paid_at')->limit(10)
        ])->where('slug', $slug)->firstOrFail();

        return response()->json(['campaign' => $this->formatCampaign($campaign)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'content'       => 'nullable|string',
            'image'         => 'nullable|url',
            'category'      => 'required|string',
            'target_amount' => 'required|numeric|min:10000',
            'deadline'      => 'nullable|date|after:today',
            'is_featured'   => 'boolean',
        ]);
	
	

        $campaign = Campaign::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);
	broadcast(new CampaignUpdated($campaign))->toOthers();

        return response()->json(['campaign' => $campaign], 201);
    }

    public function update(Request $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);
        $data = $request->validate([
            'title'         => 'sometimes|string|max:255',
            'description'   => 'sometimes|string',
            'content'       => 'nullable|string',
            'image'         => 'nullable|url',
            'category'      => 'sometimes|string',
            'target_amount' => 'sometimes|numeric|min:10000',
            'deadline'      => 'nullable|date',
            'status'        => 'sometimes|in:draft,active,completed,closed',
            'is_featured'   => 'sometimes|boolean',
        ]);


        $campaign->update($data);
	broadcast(new CampaignUpdated($campaign))->toOthers();
        return response()->json(['campaign' => $campaign]);
    }

    public function destroy(int $id)
    {
        Campaign::findOrFail($id)->delete();
        return response()->json(['message' => 'Campaign dihapus.']);
    }

    private function formatCampaign(Campaign $c): array
    {
        return [
            'id'                   => $c->id,
            'title'                => $c->title,
            'slug'                 => $c->slug,
            'description'          => $c->description,
            'content'              => $c->content,
            'image'                => $c->image,
            'category'             => $c->category,
            'target_amount'        => $c->target_amount,
            'collected_amount'     => $c->collected_amount,
            'donor_count'          => $c->donor_count,
            'progress_percentage'  => $c->progress_percentage,
            'days_left'            => $c->days_left,
            'deadline'             => $c->deadline?->format('Y-m-d'),
            'status'               => $c->status,
            'is_featured'          => $c->is_featured,
            'organizer'            => $c->user,
            'recent_donations'     => $c->donations?->map(fn($d) => [
                'name'    => $d->display_name,
                'amount'  => $d->amount,
                'message' => $d->message,
                'date'    => $d->paid_at?->diffForHumans(),
            ]),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        // Top donors (all time or per campaign)
        $query = Donation::select(
                'donor_name',
                'is_anonymous',
                'user_id',
                DB::raw('SUM(amount) as total_donated'),
                DB::raw('COUNT(*) as donation_count')
            )
            ->where('status', 'success');

        if ($request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        }

        $topDonors = $query
            ->groupBy('donor_name', 'is_anonymous', 'user_id')
            ->orderByDesc('total_donated')
            ->limit(20)
            ->get()
            ->map(fn($d) => [
                'name'           => $d->is_anonymous ? 'Donatur Anonim' : $d->donor_name,
                'total_donated'  => $d->total_donated,
                'donation_count' => $d->donation_count,
                'avatar'         => $d->user?->avatar,
            ]);

        // Recent donations
        $recentDonations = Donation::with('campaign:id,title,slug')
            ->where('status', 'success')
            ->latest('paid_at')
            ->limit(10)
            ->get()
            ->map(fn($d) => [
                'name'      => $d->display_name,
                'amount'    => $d->amount,
                'campaign'  => $d->campaign?->title,
                'date'      => $d->paid_at?->diffForHumans(),
            ]);

        // Stats
        $stats = [
            'total_donated'   => Donation::where('status', 'success')->sum('amount'),
            'total_donations' => Donation::where('status', 'success')->count(),
            'total_donors'    => Donation::where('status', 'success')->distinct('donor_email')->count(),
        ];

        return response()->json([
            'top_donors'       => $topDonors,
            'recent_donations' => $recentDonations,
            'stats'            => $stats,
        ]);
    }
}

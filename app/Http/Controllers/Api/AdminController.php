<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function donations(Request $request)
    {
        $query = Donation::with('campaign:id,title,slug')
            ->latest();

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $donations = $query->paginate(50);

        return response()->json([
            'donations' => $donations->items(),
            'meta' => [
                'total'        => $donations->total(),
                'current_page' => $donations->currentPage(),
                'last_page'    => $donations->lastPage(),
            ],
        ]);
    }

    public function users()
    {
        $users = User::withCount('donations')
            ->withSum(['donations' => fn($q) => $q->where('status', 'success')], 'amount')
            ->latest()
            ->get()
            ->map(fn($u) => [
                'id'            => $u->id,
                'name'          => $u->name,
                'email'         => $u->email,
                'role'          => $u->role,
                'avatar'        => $u->avatar,
                'total_donated' => $u->donations_sum_amount ?? 0,
                'donations_count' => $u->donations_count,
                'created_at'    => $u->created_at,
            ]);

        return response()->json(['users' => $users]);
    }

    public function updateUserRole(Request $request, int $id)
    {
        $data = $request->validate([
            'role' => 'required|in:user,admin',
        ]);

        $user = User::findOrFail($id);

        // Prevent self-demotion
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak bisa mengubah role sendiri.'], 403);
        }

        $user->update(['role' => $data['role']]);

        return response()->json(['message' => 'Role berhasil diubah.', 'user' => $user]);
    }
}

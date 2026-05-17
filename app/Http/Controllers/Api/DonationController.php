<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class DonationController extends Controller
{
    public function __construct()
    {
        Config::$serverKey    = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;
    }

    /**
     * Create a donation & get Midtrans Snap Token
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'campaign_id'  => 'required|exists:campaigns,id',
            'amount'       => 'required|numeric|min:10000',
            'donor_name'   => 'required|string|max:255',
            'donor_email'  => 'required|email',
            'donor_phone'  => 'nullable|string|max:20',
            'message'      => 'nullable|string|max:500',
            'is_anonymous' => 'boolean',
        ]);

        $campaign = Campaign::where('id', $data['campaign_id'])
            ->where('status', 'active')
            ->firstOrFail();

        $orderId = Donation::generateOrderId();

        // Build Midtrans Snap payload
        $snapPayload = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $data['amount'],
            ],
            'customer_details' => [
                'first_name' => $data['donor_name'],
                'email'      => $data['donor_email'],
                'phone'      => $data['donor_phone'] ?? '',
            ],
            'item_details' => [[
                'id'       => 'DONATION-' . $campaign->id,
                'price'    => (int) $data['amount'],
                'quantity' => 1,
                'name'     => 'Donasi: ' . substr($campaign->title, 0, 50),
            ]],
            'callbacks' => [
                'finish'  => config('app.frontend_url') . '/donation/finish?order_id=' . $orderId,
                'unfinish'=> config('app.frontend_url') . '/donation/unfinish',
                'error'   => config('app.frontend_url') . '/donation/error',
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($snapPayload);
            $paymentUrl = Config::$isProduction
                ? 'https://app.midtrans.com/snap/v2/vtweb/' . $snapToken
                : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $snapToken;
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghubungi Midtrans: ' . $e->getMessage()], 500);
        }

        $donation = Donation::create([
            'user_id'      => $request->user()->id,
            'campaign_id'  => $campaign->id,
            'order_id'     => $orderId,
            'amount'       => $data['amount'],
            'donor_name'   => $data['donor_name'],
            'donor_email'  => $data['donor_email'],
            'donor_phone'  => $data['donor_phone'] ?? null,
            'message'      => $data['message'] ?? null,
            'is_anonymous' => $data['is_anonymous'] ?? false,
            'snap_token'   => $snapToken,
            'payment_url'  => $paymentUrl,
        ]);

        return response()->json([
            'message'     => 'Donasi berhasil dibuat. Lanjutkan pembayaran.',
            'order_id'    => $orderId,
            'snap_token'  => $snapToken,
            'payment_url' => $paymentUrl,
            'amount'      => $donation->amount,
        ], 201);
    }

    /**
     * Midtrans webhook notification
     */
    public function notification(Request $request)
    {
        try {
            $notif     = new Notification();
            $orderId   = $notif->order_id;
            $status    = $notif->transaction_status;
            $fraudStatus = $notif->fraud_status;
            $paymentType = $notif->payment_type;
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid notification'], 400);
        }

        $donation = Donation::where('order_id', $orderId)->first();
        if (!$donation) {
            return response()->json(['message' => 'Donation not found'], 404);
        }

        $newStatus = match(true) {
            in_array($status, ['capture', 'settlement']) && $fraudStatus !== 'deny' => 'success',
            $status === 'pending'   => 'pending',
            $status === 'expire'    => 'expired',
            default                 => 'failed',
        };

        DB::transaction(function () use ($donation, $newStatus, $paymentType, $notif) {
            $wasSuccess = $donation->status === 'success';
            $donation->update([
                'status'             => $newStatus,
                'payment_type'       => $paymentType,
                'midtrans_response'  => (array) $notif,
                'paid_at'            => $newStatus === 'success' ? now() : null,
            ]);

            // Update campaign collected amount if newly successful
            if ($newStatus === 'success' && !$wasSuccess) {
                $donation->campaign->increment('collected_amount', $donation->amount);
                $donation->campaign->increment('donor_count');
            }
        });

        return response()->json(['message' => 'OK']);
    }

    /**
     * Donation history for authenticated user
     */
    public function history(Request $request)
    {
        $donations = Donation::with('campaign:id,title,slug,image')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'donations' => $donations->items(),
            'meta' => [
                'current_page' => $donations->currentPage(),
                'last_page'    => $donations->lastPage(),
                'total'        => $donations->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $donation = Donation::with('campaign:id,title,slug,image')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(['donation' => $donation]);
    }
}

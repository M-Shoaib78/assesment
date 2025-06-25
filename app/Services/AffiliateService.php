<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     *
     * @throws AffiliateCreateException
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // Check if email is already used by any user (merchant or affiliate)
        $userExists = User::where('email', $email)->exists();
        if ($userExists) {
            throw new AffiliateCreateException('Email already used.');
        }

        try {
            // Create the User for the affiliate
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(16)), // random secure password
                'type' => User::TYPE_AFFILIATE,
            ]);

            // Generate unique discount code for affiliate using ApiService
            $discountCode = $this->apiService->createDiscountCode($merchant)['code'];

            // Create the affiliate linked to that user and merchant
            $affiliate = Affiliate::create([
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
                'commission_rate' => $commissionRate,
                'discount_code' => $discountCode,
            ]);

            // Send notification email (optional)
            if (view()->exists('emails.affiliate_created')) {
                Mail::to($email)->send(new AffiliateCreated($affiliate));
            }

            return $affiliate;
        } catch (QueryException $e) {
            // Catch any DB exceptions like unique constraint violation and throw as friendly exception
            throw new AffiliateCreateException('Failed to create affiliate: ' . $e->getMessage());
        }
    }

    public function orderStats(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $merchant = $request->user()->merchant;

        $orders = $merchant->orders()
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $count = $orders->count();
        $revenue = $orders->sum('subtotal');
        $commissions_owed = $orders->whereNotNull('affiliate_id')->sum('commission_owed');

        return response()->json([
            'count' => $count,
            'revenue' => $revenue,
            'commissions_owed' => $commissions_owed,
        ]);
    }
}

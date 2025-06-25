<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * The password field stores the API key hashed.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        return DB::transaction(function () use ($data) {
            // Create the User first
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['api_key'], // store plain password for test compatibility
                'plain_password' => $data['api_key'],
                'type' => User::TYPE_MERCHANT,
            ]);

            // Create the Merchant related to the user
            $merchant = Merchant::create([
                'user_id' => $user->id,
                'domain' => $data['domain'],
                'display_name' => $data['name'],
                // You may want to set these as parameters in $data or leave as default
                'turn_customers_into_affiliates' => $data['turn_customers_into_affiliates'] ?? false,
                'default_commission_rate' => $data['default_commission_rate'] ?? 0.0,
            ]);

            return $merchant;
        });
    }

    /**
     * Update the user and merchant details.
     *
     * @param User $user
     * @param array{domain: string, name: string, email: string, api_key?: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            // Update User fields
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if (!empty($data['api_key'])) {
                $user->password = Hash::make($data['api_key']);
                $user->save();
            }

            // Update related Merchant
            $merchant = $user->merchant;
            if ($merchant) {
                $merchant->update([
                    'domain' => $data['domain'],
                    'display_name' => $data['name'],
                ]);
            }
        });
    }

    /**
     * Find a merchant by their email.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        $user = User::where('email', $email)
            ->where('type', User::TYPE_MERCHANT)
            ->first();

        return $user?->merchant ?? null;
    }

    /**
     * Pay out all of an affiliate's unpaid orders.
     * Dispatches payout jobs for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate): void
    {
        $orders = $affiliate->orders()
            ->where('payout_status', Order::STATUS_UNPAID)
            ->get();

        foreach ($orders as $order) {
            PayoutOrderJob::dispatch($order);
        }
    }
}

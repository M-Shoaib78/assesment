<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        // Ensure we have an external_order_id (fallback to UUID if missing)
        $externalOrderId = $data['order_id'] ?? Str::uuid()->toString();

        // Check for duplicate order by external_order_id
        if (Order::where('external_order_id', $externalOrderId)->exists()) {
            return; // Ignore duplicates
        }

        // Find the merchant by domain
        $merchant = Merchant::where('domain', $data['merchant_domain'])->first();
        if (!$merchant) {
            return; // Merchant not found, exit silently
        }

        $discountCode = $data['discount_code'] ?? null;
        $affiliate = null;

        if ($discountCode) {
            // Find affiliate by discount code for this merchant
            $affiliate = $merchant->affiliates()->where('discount_code', $discountCode)->first();
        }

        // If no affiliate found and discount code is present, try to register one
        if (!$affiliate && $discountCode) {
            $affiliate = $this->affiliateService->register(
                $merchant,
                $data['customer_email'],
                $data['customer_name'] ?? 'Unknown',
                0.1
            );
        }

        // Calculate commission owed (0 if no affiliate)
        $commissionOwed = $affiliate ? $data['subtotal_price'] * $affiliate->commission_rate : 0;

        // Create the order
        Order::create([
            'external_order_id' => $externalOrderId, // <-- Guaranteed not null
            'subtotal' => $data['subtotal_price'],
            'merchant_id' => $merchant->id,
            'affiliate_id' => $affiliate?->id,
            'commission_owed' => $commissionOwed,
            'discount_code' => $affiliate?->discount_code,
        ]);
    }
}

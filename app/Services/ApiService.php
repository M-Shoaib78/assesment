<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * You don't need to do anything here. This is just to help
 */
class ApiService
{
    /**
     * Create a new discount code for an affiliate
     *
     * @param Merchant $merchant
     *
     * @return array{id: int, code: string}
     */
    public function createDiscountCode(Merchant $merchant): array
    {
        return [
            'id' => rand(0, 100000),
            'code' => Str::uuid()
        ];
    }

    /**
     * Send a payout to an email
     *
     * @param  string $email
     * @param  float $amount
     * @return void
     * @throws RuntimeException
     */
    public function sendPayout(string $email, float $amount)
    {
        // Implement actual payout logic here.
        // Throw RuntimeException on failure if needed.
    }

    /**
     * Pay an affiliate a given amount.
     *
     * @param string $email
     * @param float $amount
     * @return void
     * @throws RuntimeException on failure
     */
    public function payAffiliate(string $email, float $amount): void
    {
        $this->sendPayout($email, $amount);
    }
}

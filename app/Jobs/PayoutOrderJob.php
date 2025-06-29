<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayoutOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Use the API service to send a payout of the correct amount.
     * Note: The order status must be paid if the payout is successful, or remain unpaid in the event of an exception.
     *
     * @return void
     */
    public function handle(ApiService $apiService)
    {
        DB::beginTransaction();

        try {
            // Call API to pay affiliate commission
            $apiService->sendPayout($this->order->affiliate->user->email, $this->order->commission_owed);

            // If successful, update payout status to paid
            $this->order->update(['payout_status' => Order::STATUS_PAID]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            // Optionally log or rethrow exception to fail the job and retry
            // Log::error('Payout failed for order '.$this->order->id.': '.$e->getMessage());

            throw $e; // Let the job fail and retry according to Laravel queue settings
        }
    }
}

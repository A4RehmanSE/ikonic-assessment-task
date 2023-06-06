<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {
    }

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

        // Check for duplicate orders based on order_id
        $existingOrder = Order::where('external_order_id', $data['order_id'])->first();
        if ($existingOrder) {
            // Ignore duplicate order
            return;
        }

        // Retrieve the merchant based on the domain
        $merchant = Merchant::where('domain', $data['merchant_domain'])->first();
        if (!$merchant) {
            // Merchant not found, handle the error or throw an exception
            return;
        }

        // Create or retrieve the affiliate
        $affiliate = Affiliate::firstOrCreate(['merchant_id' => $merchant->id], [
            'merchant_id' => $merchant->id,
            'commission_rate' => $merchant->default_commission_rate,
            'discount_code' => '',
        ]);

        // Create a new order
        $order = new Order();
        $order->subtotal = $data['subtotal_price'];
        $order->discount_code = $data['discount_code'];
        $order->merchant_id = $merchant->id;
        $order->affiliate_id = $affiliate->id;
        $order->payout_status = Order::STATUS_UNPAID;

        // Calculate commission based on the subtotal and commission rate
        $commission = $data['subtotal_price'] * $affiliate->commission_rate;

        // Update the commission_owed field in the order
        $order->commission_owed = $commission;
        $order->external_order_id = $data['order_id']; // Add external_order_id assignment

        // Save the order
        $order->save();

        // Update affiliate registration if necessary
        $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], 0.1);
    }
}

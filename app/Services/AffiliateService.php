<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // TODO: Complete this method
        // Check if the email already exists as a user
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            throw new AffiliateCreateException('Email is already in use.');
        }

        // Create a new user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt(Str::random(8)), // Generate a random password
            'type' => User::TYPE_AFFILIATE,
        ]);

        // Create the affiliate
        $affiliate = new Affiliate([
            'user_id' => $user->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $this->apiService->createDiscountCode($merchant)['code'], // Use the mocked discount code
        ]);

        // Associate the merchant and user with the affiliate
        $affiliate->merchant()->associate($merchant);

        // Save the affiliate and associated models
        try {
            // Start a database transaction
            DB::beginTransaction();

            // Save the affiliate
            if (!$affiliate->save()) {
                throw new AffiliateCreateException('Failed to create affiliate.');
            }

            // Commit the transaction
            DB::commit();

            // Send affiliate creation email
            Mail::to($email)->send(new AffiliateCreated($affiliate));

            return $affiliate;
        } catch (\Exception $e) {
            // Roll back the transaction if any exception occurs
            DB::rollBack();
            throw $e;
        }
    }
}

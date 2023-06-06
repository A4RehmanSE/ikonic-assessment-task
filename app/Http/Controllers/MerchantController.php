<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    protected MerchantService $merchantService;

    public function __construct(
        MerchantService $merchantService
    ) {
        $this->merchantService = $merchantService;
    }

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        // TODO: Complete this method
        $from = $request->input('from');
        $to = $request->input('to');

        // Retrieve the merchant based on the authenticated user
        $merchant = $this->merchantService->findMerchantByEmail($request->user()->email);
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 404);
        }

        $orderCount = $merchant->orders()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $commissionOwed = $merchant->orders()
            ->where('payout_status', Order::STATUS_UNPAID)
            ->whereHas('affiliate')
            ->whereBetween('created_at', [$from, $to])
            ->sum('commission_owed');

        $revenue = $merchant->orders()
            ->whereBetween('created_at', [$from, $to])
            ->sum('subtotal');

        return response()->json([
            'count' => $orderCount,
            'commissions_owed' => round($commissionOwed, 2),
            'revenue' => round($revenue, 2),
        ]);
    }
}

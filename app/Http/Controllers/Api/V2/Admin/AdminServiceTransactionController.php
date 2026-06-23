<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceTransaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminServiceTransactionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = ServiceTransaction::query()->with([
                'transaction',
                'transaction.country',
                'merchant',
                'partner',
                'service',
                'product',
            ]);

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('transaction_id', 'like', "%{$search}%")
                        ->orWhere('merchant_id', 'like', "%{$search}%")
                        ->orWhere('partner_id', 'like', "%{$search}%")
                        ->orWhere('service_id', 'like', "%{$search}%")
                        ->orWhere('product_id', 'like', "%{$search}%");
                })->orWhereHas('transaction', function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                        ->orWhere('rrn', 'like', "%{$search}%")
                        ->orWhere('auth_code', 'like', "%{$search}%");
                });
            }

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            if ($partnerId = $request->input('partner_id')) {
                $query->where('partner_id', $partnerId);
            }

            if ($serviceId = $request->input('service_id')) {
                $query->where('service_id', $serviceId);
            }

            if ($productId = $request->input('product_id')) {
                $query->where('product_id', $productId);
            }

            if ($dateFrom = $request->input('date_from', $request->input('start_date'))) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to', $request->input('end_date'))) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            $perPage = (int) $request->input('per_page', 25);
            $items = $query->latest()->paginate($perPage);
            $items->setCollection(
                $items->getCollection()->map(function (ServiceTransaction $item) {
                    $payload = $item->toArray();
                    $payload['merchant_name'] = $item->merchant_name;
                    $payload['partner_name'] = $item->partner_name;
                    $payload['service_name'] = $item->service_name;
                    $payload['product_name'] = $item->product_name;
                    $payload['country_name'] = $item->transaction?->country?->name
                        ?? $item->transaction?->country_name
                        ?? null;
                    return $payload;
                })
            );

            return $this->SuccessMessage($items);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to fetch service transactions: ' . $e->getMessage(), null, 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = ServiceTransaction::query()->with([
                'transaction',
                'transaction.paymentMethod',
                'merchant',
                'partner',
                'service',
                'product',
            ])->findOrFail($id);
            $payload = $item->toArray();
            $payload['merchant_name'] = $item->merchant_name;
            $payload['partner_name'] = $item->partner_name;
            $payload['service_name'] = $item->service_name;
            $payload['product_name'] = $item->product_name;
            return $this->SuccessMessage($payload);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to fetch service transaction: ' . $e->getMessage(), null, 500);
        }
    }
}


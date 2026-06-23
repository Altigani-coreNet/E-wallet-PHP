<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentByLink;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPaymentLinkController extends Controller
{
    use ApiResponse;

    /**
     * List payment links with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PaymentByLink::query()->with(['merchant', 'country']);

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('uuid', 'like', "%{$search}%")
                      ->orWhere('amount', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            }

            if ($customer = $request->input('customer')) {
                $query->where('customer_name', 'like', "%{$customer}%");
            }

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            if ($countryId = $request->input('country_id')) {
                $query->where('country_id', $countryId);
            }

            if ($fromDate = $request->input('from_date')) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if ($toDate = $request->input('to_date')) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $perPage = (int) $request->input('per_page', 15);
            $paymentLinks = $query->orderByDesc('created_at')->paginate($perPage);

            $paymentLinks->getCollection()->transform(function (PaymentByLink $link) {
                $data = $link->toArray();

                if (!empty($data['currency_object'])) {
                    $currencyObject = is_array($data['currency_object'])
                        ? $data['currency_object']
                        : json_decode($data['currency_object'], true);

                    $data['currency_symbol'] = $currencyObject['symbol'] ?? '';
                    $data['currency_name'] = $currencyObject['name'] ?? '';
                } else {
                    $data['currency_symbol'] = '';
                    $data['currency_name'] = '';
                }

                $data['merchant_name'] = optional($link->merchant)->name ?? optional($link->merchant)->business_name ?? '';
                $data['country_name'] = optional($link->country)->name ?? optional($link->country)->en_name ?? '';

                return $data;
            });

            return $this->SuccessMessage($paymentLinks);
        } catch (\Exception $exception) {
            Log::error('AdminPaymentLinkController@index error: ' . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to fetch payment links', null, 500);
        }
    }

    /**
     * Show payment link details.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $paymentLink = PaymentByLink::with(['merchant', 'country'])->findOrFail($id);
            $data = $paymentLink->toArray();

            if (!empty($data['currency_object'])) {
                $currencyObject = is_array($data['currency_object'])
                    ? $data['currency_object']
                    : json_decode($data['currency_object'], true);

                $data['currency_object'] = $currencyObject;
                $data['currency_symbol'] = $currencyObject['symbol'] ?? '';
                $data['currency_name'] = $currencyObject['name'] ?? '';
            } else {
                $data['currency_symbol'] = '';
                $data['currency_name'] = '';
            }

            $data['merchant_name'] = optional($paymentLink->merchant)->name ?? optional($paymentLink->merchant)->business_name ?? '';
            $data['country_name'] = optional($paymentLink->country)->name ?? optional($paymentLink->country)->en_name ?? '';

            return $this->SuccessMessage($data);
        } catch (\Exception $exception) {
            Log::error('AdminPaymentLinkController@show error: ' . $exception->getMessage(), [
                'id' => $id,
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to fetch payment link', null, 500);
        }
    }

    /**
     * Retrieve payment link statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = PaymentByLink::query();

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            if ($countryId = $request->input('country_id')) {
                $query->where('country_id', $countryId);
            }

            if ($fromDate = $request->input('from_date')) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if ($toDate = $request->input('to_date')) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $baseQuery = clone $query;

            $statistics = [
                'total' => (clone $baseQuery)->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'expired' => (clone $baseQuery)->where('status', 'expired')->count(),
            ];

            return $this->SuccessMessage($statistics);
        } catch (\Exception $exception) {
            Log::error('AdminPaymentLinkController@statistics error: ' . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to fetch payment link statistics', null, 500);
        }
    }

    /**
     * Export payment links as CSV.
     */
    public function export(Request $request)
    {
        try {
            $query = PaymentByLink::query()->with(['merchant', 'country']);

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('uuid', 'like', "%{$search}%")
                      ->orWhere('amount', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            }

            if ($customer = $request->input('customer')) {
                $query->where('customer_name', 'like', "%{$customer}%");
            }

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            if ($countryId = $request->input('country_id')) {
                $query->where('country_id', $countryId);
            }

            if ($fromDate = $request->input('from_date')) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if ($toDate = $request->input('to_date')) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $paymentLinks = $query->orderByDesc('created_at')->get();

            if ($paymentLinks->isEmpty()) {
                return $this->ErrorMessage('No payment links found for export', null, 404);
            }

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="admin_payment_links_export_' . date('Y-m-d_H-i-s') . '.csv"',
            ];

            $callback = function () use ($paymentLinks) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'UUID',
                    'Merchant',
                    'Country',
                    'Customer Name',
                    'Customer Email',
                    'Customer Phone',
                    'Amount',
                    'Currency',
                    'Status',
                    'Created At',
                    'Scheduled Date',
                    'Expired Date',
                ]);

                foreach ($paymentLinks as $link) {
                    $currencySymbol = '';
                    if (!empty($link->currency_object)) {
                        $currencyObject = is_array($link->currency_object)
                            ? $link->currency_object
                            : json_decode($link->currency_object, true);
                        $currencySymbol = $currencyObject['symbol'] ?? '';
                    }

                    fputcsv($file, [
                        $link->id,
                        $link->uuid,
                        optional($link->merchant)->name ?? optional($link->merchant)->business_name,
                        optional($link->country)->name ?? optional($link->country)->en_name,
                        $link->customer_name,
                        $link->customer_email,
                        $link->customer_phone,
                        $link->amount,
                        $link->currency_code . ($currencySymbol ? " ({$currencySymbol})" : ''),
                        $link->status,
                        optional($link->created_at)?->format('Y-m-d H:i:s'),
                        $link->scheduled_date,
                        $link->expired_date,
                    ]);
                }

                fclose($file);
            };

            return new StreamedResponse($callback, 200, $headers);
        } catch (\Exception $exception) {
            Log::error('AdminPaymentLinkController@export error: ' . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to export payment links', null, 500);
        }
    }
}


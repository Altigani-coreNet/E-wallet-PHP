<?php

namespace App\Repositories;

use App\Models\Currency;
use App\Models\Merchant;
use App\Models\PaymentByLink;
use App\Models\Transaction;
use App\Services\PaymentByLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentByLinkRepository implements PaymentByLinkService
{
    protected $model;

    public function __construct(PaymentByLink $model)
    {
        $this->model = $model;
    }

    /**
     * Fill currency_code / currency_object from currencies table when the client only sends currency_id (e.g. V3 POS API).
     */
    protected function hydrateCurrencyFieldsFromModel(array &$data): void
    {
        if (empty($data['currency_id'])) {
            return;
        }

        $currency = Currency::find($data['currency_id']);
        if (!$currency) {
            return;
        }

        $symbol = method_exists($currency, 'getTranslation')
            ? ($currency->getTranslation('symbol', app()->getLocale(), false)
                ?? $currency->getTranslation('symbol', 'en', false)
                ?? '')
            : (string) ($currency->symbol ?? '');

        $code = method_exists($currency, 'getTranslation')
            ? ($currency->getTranslation('currency_code', app()->getLocale(), false)
                ?? $currency->getTranslation('currency_code', 'en', false)
                ?? '')
            : (string) ($currency->currency_code ?? '');

        $snapshot = [
            'id' => $currency->id,
            'name' => $currency->name,
            'symbol' => $symbol,
            'currency_code' => $code,
            'code' => $code,
            'country' => $currency->country ?? null,
        ];

        if (empty($data['currency_code'])) {
            $data['currency_code'] = $code !== '' ? $code : null;
        }

        if (empty($data['currency_object'])) {
            $data['currency_object'] = json_encode($snapshot);
        }
    }

    public function index(Request $request)
    {
        return $this->model->all();
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['currency'] = $data['currency_code'] ?? 'USD';

        $this->hydrateCurrencyFieldsFromModel($data);
        
        if (isset($data['payment_method_types']) && is_array($data['payment_method_types'])) {
            $data['payment_method_types'] = json_encode($data['payment_method_types'], true);
        }

        $contry = (auth()->guard('external')->user()->merchant->country_id ?? '019a16d8-4c29-7272-9b66-2016f0d894b5');
        // $merchant = Merchant::select('country_id')->find($data['merchant_id']);
        $data['country_id'] = $contry;
        
        // Extract currency object and symbol from request (already validated in controller)
        $currencyObject = isset($data['currency_object']) ? 
            (is_string($data['currency_object']) ? $data['currency_object'] : json_encode($data['currency_object'])) : null;
        $data['currency_object'] = $currencyObject;

        // Create only a payment link record (no transaction / no Stripe session on create)
        // DB column is non-nullable, so keep an explicit marker value.
        $data['payment_sdk'] = 'link_only';

        // Set status
        if (!empty($data['scheduled_date'])) {
            $data['status'] = 'scheduled';
        } else {
        $data['status'] = 'pending';
        }
        
        // Check if expired_date is set and if it's in the past
        if (!empty($data['expired_date'])) {
            $expiredDate = \Carbon\Carbon::parse($data['expired_date']);
            if ($expiredDate->isPast()) {
                $data['status'] = 'expired';
            }
        }
        
        if (empty($data['uuid'])) {
            $data['uuid'] = (string) Str::uuid();
        }

        // Public checkout url served by frontend app
        $frontendBaseUrl = rtrim(env('APP_FRONTEND_URL', config('app.url')), '/');
        $data['link'] = "{$frontendBaseUrl}/payments?uuid=" . $data['uuid'];

        $paymentLink = $this->model->create($data);

        return $paymentLink;
    }

    public function show($id)
    {
        return $this->model->findOrFail($id);
    }

    public function findByUuid($uuid)
    {
        return $this->model->where('uuid', $uuid)->firstOrFail();
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $this->hydrateCurrencyFieldsFromModel($data);
        // Handle payment_method_types as array
        if (isset($data['payment_method_types']) && is_array($data['payment_method_types'])) {
            $data['payment_method_types'] = json_encode($data['payment_method_types'], true);
        }
        $link = $this->model->findOrFail($id);

        if (isset($data['currency_code'])) {
            $data['currency'] = $data['currency_code'];
        } else {
            $data['currency'] = $link->currency_code ?? 'USD';
        }

        // Keep existing public link shape on update
        $frontendBaseUrl = rtrim(env('APP_FRONTEND_URL', config('app.url')), '/');
        $effectiveUuid = $link->uuid ?: ($data['uuid'] ?? null);
        if ($effectiveUuid) {
            $data['link'] = "{$frontendBaseUrl}/payments?uuid={$effectiveUuid}";
        }
        if (empty($data['payment_sdk'])) {
            $data['payment_sdk'] = $link->payment_sdk ?: 'link_only';
        }
        // Set status
        if (!empty($data['scheduled_date'])) {
            $data['status'] = 'scheduled';
        } else {
            $data['status'] = 'pending';
        }
        
        // Check if expired_date is set and if it's in the past
        if (!empty($data['expired_date'])) {
            $expiredDate = \Carbon\Carbon::parse($data['expired_date']);
            if ($expiredDate->isPast()) {
                $data['status'] = 'expired';
            }
        }
        
        $link->update($data);
        return $link;
    }

    public function destroy($id)
    {
        $link = $this->model->findOrFail($id);
        return $link->delete();
    }

    public function cancel($id)
    {
        $link = $this->model->findOrFail($id);
        
        // Only allow canceling if status is pending or scheduled
        if (!in_array($link->status, ['pending', 'scheduled', 'active'])) {
            throw new \Exception('Payment link cannot be canceled. Current status: ' . $link->status);
        }
        
        // Update the payment link status to canceled
        $link->update(['status' => 'canceled']);
        
        // Update associated transaction if exists
        $transaction = Transaction::where('metadata->payment_link_id', $id)->first();
        if ($transaction && $transaction->status === 'pending') {
            $transaction->update([
                'status' => 'cancelled',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'canceled_at' => now()->toISOString(),
                    'cancel_reason' => 'Payment link canceled by merchant'
                ])
            ]);
        }
        
        return $link;
    }
}

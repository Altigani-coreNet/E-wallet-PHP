<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTransaction extends Model
{
    use HasFactory;

    protected $appends = [
        'merchant_name',
        'partner_name',
        'service_name',
        'product_name',
    ];

    protected $fillable = [
        'transaction_id',
        'merchant_id',
        'partner_id',
        'service_id',
        'product_id',
        'status',
        'service_url',
        'request_payload',
        'service_response',
        'error_message',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'service_response' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function getMerchantNameAttribute(): ?string
    {
        if ($this->relationLoaded('merchant') && $this->merchant) {
            return $this->merchant->business_name ?? $this->merchant->name;
        }
        return $this->merchant_id ? Merchant::query()->where('id', $this->merchant_id)->value('business_name') : null;
    }

    public function getPartnerNameAttribute(): ?string
    {
        if ($this->relationLoaded('partner') && $this->partner) {
            return $this->partner->name ?? $this->partner->business_name;
        }
        return $this->partner_id ? Partner::query()->where('id', $this->partner_id)->value('name') : null;
    }

    public function getServiceNameAttribute(): ?string
    {
        if ($this->relationLoaded('service') && $this->service) {
            $name = $this->service->service_name;
            if (is_array($name)) {
                return $name['en'] ?? $name['ar'] ?? null;
            }
        }
        if (! $this->service_id) {
            return null;
        }
        $serviceName = Service::query()->where('id', $this->service_id)->value('service_name');
        if (is_array($serviceName)) {
            return $serviceName['en'] ?? $serviceName['ar'] ?? null;
        }
        if (is_string($serviceName)) {
            $decoded = json_decode($serviceName, true);
            return is_array($decoded) ? ($decoded['en'] ?? $decoded['ar'] ?? null) : null;
        }
        return null;
    }

    public function getProductNameAttribute(): ?string
    {
        if ($this->relationLoaded('product') && $this->product) {
            $name = $this->product->name;
            if (is_array($name)) {
                return $name['en'] ?? $name['ar'] ?? null;
            }
        }
        if (! $this->product_id) {
            return null;
        }
        $productName = Product::query()->where('id', $this->product_id)->value('name');
        if (is_array($productName)) {
            return $productName['en'] ?? $productName['ar'] ?? null;
        }
        if (is_string($productName)) {
            $decoded = json_decode($productName, true);
            return is_array($decoded) ? ($decoded['en'] ?? $decoded['ar'] ?? null) : null;
        }
        return null;
    }

}


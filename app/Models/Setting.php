<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value_type',
        'value'
    ];

    // Contract settings keys
    const TERMS_EN = 'contract_terms_en';
    const TERMS_AR = 'contract_terms_ar';
    const TRANSACTION_FEE = 'contract_transaction_fee';
    const SETTLEMENT_PERIOD = 'contract_settlement_period';
    const CONTRACT_DURATION = 'contract_duration_months';
    const PAYMENT_METHODS = 'contract_payment_methods';
    const SUBSCRIPTION_PLANS = 'contract_subscription_plans';

    public static function getContractTerms($lang = 'en')
    {
        return self::where('key', $lang === 'en' ? self::TERMS_EN : self::TERMS_AR)
            ->value('value');
    }

    public static function getTransactionFee()
    {
        return self::where('key', self::TRANSACTION_FEE)->value('value') ?? 2.5;
    }

    public static function getSettlementPeriod()
    {
        return self::where('key', self::SETTLEMENT_PERIOD)->value('value') ?? 'T+1 Business Days';
    }

    public static function getContractDuration()
    {
        return (int)(self::where('key', self::CONTRACT_DURATION)->value('value') ?? 12);
    }

    public static function getPaymentMethods()
    {
        $methods = self::where('key', self::PAYMENT_METHODS)->value('value');
        return $methods ? json_decode($methods, true) : ['VISA', 'MasterCard', 'MADA'];
    }

    public static function getSubscriptionPlans()
    {
        $plans = self::where('key', self::SUBSCRIPTION_PLANS)->value('value');
        return $plans ? json_decode($plans, true) : ['Standard', 'Premium', 'Enterprise'];
    }

    public static function updateContractSettings($data)
    {
        $settings = [
            self::TERMS_EN => ['value' => $data['terms_en'] ?? '', 'type' => 'string'],
            self::TERMS_AR => ['value' => $data['terms_ar'] ?? '', 'type' => 'string'],
            self::TRANSACTION_FEE => ['value' => $data['transaction_fee'] ?? 2.5, 'type' => 'string'],
            self::SETTLEMENT_PERIOD => ['value' => $data['settlement_period'] ?? 'T+1 Business Days', 'type' => 'string'],
            self::CONTRACT_DURATION => ['value' => $data['contract_duration_months'] ?? 12, 'type' => 'string'],
            self::PAYMENT_METHODS => ['value' => json_encode($data['payment_methods'] ?? ['VISA', 'MasterCard', 'MADA']), 'type' => 'string'],
            self::SUBSCRIPTION_PLANS => ['value' => json_encode($data['subscription_plans'] ?? ['Standard', 'Premium', 'Enterprise']), 'type' => 'string'],
        ];

        foreach ($settings as $key => $setting) {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => $setting['value'], 'value_type' => $setting['type']]
            );
        }
    }
}
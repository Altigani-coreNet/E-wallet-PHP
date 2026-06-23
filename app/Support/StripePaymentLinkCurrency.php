<?php

namespace App\Support;

/**
 * Demo / gateway mapping: Sudan amounts are shown to customers and stored on invoices in local currency (SDG),
 * while Stripe PaymentIntents settle in USD (Stripe support / demo). Keep this list in sync with Payment app
 * `stripeSettlementCurrencyFromDisplay` when adding codes.
 */
final class StripePaymentLinkCurrency
{
    /** Display / ledger codes that use USD on Stripe only. */
    private const DEMO_STRIPE_USD_DISPLAY_CODES = ['SDG', 'SUD'];

    public static function stripeCurrencyFromDisplayCode(?string $displayCode): string
    {
        $code = strtoupper(trim((string) $displayCode));
        if ($code === '') {
            return 'usd';
        }
        if (in_array($code, self::DEMO_STRIPE_USD_DISPLAY_CODES, true)) {
            return 'usd';
        }

        return strtolower($code);
    }

    public static function isDemoSudanChargedAsUsdOnStripe(?string $displayCode): bool
    {
        $code = strtoupper(trim((string) $displayCode));

        return $code !== '' && in_array($code, self::DEMO_STRIPE_USD_DISPLAY_CODES, true);
    }
}

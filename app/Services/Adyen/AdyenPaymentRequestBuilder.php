<?php

namespace App\Services\Adyen;

use Adyen\Model\Checkout\Amount;
use Adyen\Model\Checkout\CheckoutPaymentMethod;
use Adyen\Model\Checkout\PaymentRequest;

/**
 * Builds {@see PaymentRequest} payloads for Checkout /payments.
 *
 * For card payments (type "scheme"), use encrypted card fields from Adyen
 * Drop-in/Components — not plain PAN. Test placeholders use the "test_" prefix.
 */
class AdyenPaymentRequestBuilder
{
    /**
     * @param  array<string, mixed>  $overrides  Keys: merchant_account, amount_value, currency,
     *                                           reference, return_url, channel,
     *                                           encrypted_card_number, encrypted_expiry_month,
     *                                           encrypted_expiry_year, encrypted_security_code
     */
    public function buildSchemePayment(array $overrides = []): PaymentRequest
    {
        $merchantAccount = (string) ($overrides['merchant_account'] ?? config('adyen.merchant_account'));
        $returnUrl = (string) ($overrides['return_url'] ?? config('adyen.default_return_url'));

        // dd($merchantAccount);
        $paymentMethod = new CheckoutPaymentMethod();
        $paymentMethod
            ->setType('scheme')
            ->setEncryptedCardNumber((string) ($overrides['encrypted_card_number'] ?? 'test_4111111111111111'))
            ->setEncryptedExpiryMonth((string) ($overrides['encrypted_expiry_month'] ?? 'test_03'))
            ->setEncryptedExpiryYear((string) ($overrides['encrypted_expiry_year'] ?? 'test_2030'))
            ->setEncryptedSecurityCode((string) ($overrides['encrypted_security_code'] ?? 'test_737'));

        $amount = new Amount();
        $amount
            ->setValue((int) ($overrides['amount_value'] ?? 1500))
            ->setCurrency((string) ($overrides['currency'] ?? config('adyen.default_currency')));

        $request = new PaymentRequest();
        $request
            ->setMerchantAccount($merchantAccount)
            ->setPaymentMethod($paymentMethod)
            ->setAmount($amount)
            ->setReference((string) ($overrides['reference'] ?? 'payment-test-'.uniqid()))
            ->setReturnUrl($returnUrl);

        if (! empty($overrides['channel'])) {
            $request->setChannel((string) $overrides['channel']);
        } else {
            $request->setChannel('Web');
        }

        return $request;
    }
}

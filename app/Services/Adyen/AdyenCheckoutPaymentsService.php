<?php

namespace App\Services\Adyen;

use Adyen\AdyenException;
use Adyen\Model\Checkout\PaymentRequest;
use Adyen\Model\Checkout\PaymentResponse;
use Adyen\Service\Checkout\PaymentsApi;

/**
 * Thin wrapper around Adyen Checkout {@see PaymentsApi::payments}.
 */
class AdyenCheckoutPaymentsService
{
    public function __construct(
        private readonly AdyenClientFactory $clientFactory
    ) {}

    /**
     * @throws AdyenException
     */
    public function payments(PaymentRequest $paymentRequest): PaymentResponse
    {
        $service = new PaymentsApi($this->clientFactory->create());

        return $service->payments($paymentRequest);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PartnerApiResponse
{
    public function __construct(
        public readonly bool $successful,
        public readonly int $statusCode,
        public readonly mixed $body,
        public readonly ?string $errorMessage = null,
    ) {
    }
}

class PartnerApiClient
{
    public function post(string $url, array $payload, int $timeoutSeconds = 30): PartnerApiResponse
    {
        if ($stub = $this->localDevStubResponse($url)) {
            return $stub;
        }

        try {
            $response = Http::timeout($timeoutSeconds)->acceptJson()->post($url, $payload);

            return new PartnerApiResponse(
                successful: $response->successful(),
                statusCode: $response->status(),
                body: $response->json() ?? $response->body(),
                errorMessage: $response->successful() ? null : 'Service endpoint returned non-success status.',
            );
        } catch (\Throwable $e) {
            return new PartnerApiResponse(
                successful: false,
                statusCode: 0,
                body: null,
                errorMessage: $e->getMessage(),
            );
        }
    }

    /**
     * Local E2E: accept bill-mock.test URLs without outbound HTTP (Cypress / Postman dev).
     */
    private function localDevStubResponse(string $url): ?PartnerApiResponse
    {
        if (! app()->environment('local', 'testing') && app()->isProduction()) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! in_array($host, ['bill-mock.test', 'localhost', '127.0.0.1'], true)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        if (str_contains($path, '/fail')) {
            return new PartnerApiResponse(
                successful: false,
                statusCode: 500,
                body: ['error' => 'partner down'],
                errorMessage: 'Partner API failed.',
            );
        }

        return new PartnerApiResponse(
            successful: true,
            statusCode: 200,
            body: ['success' => true, 'reference' => 'MOCK-E2E'],
        );
    }
}

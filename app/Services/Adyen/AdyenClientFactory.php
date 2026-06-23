<?php

namespace App\Services\Adyen;

use Adyen\Client;
use Adyen\Environment;

/**
 * Builds a configured Adyen API {@see Client} from Laravel config.
 */
class AdyenClientFactory
{
    public function create(): Client
    {
        $client = new Client();

        $apiKey = (string) config('adyen.api_key');
        // dd($apiKey);
        if ($apiKey !== '') {
            $client->setXApiKey($apiKey);
        }

        $environment = config('adyen.environment') === Environment::LIVE
            ? Environment::LIVE
            : Environment::TEST;

        $livePrefix = $environment === Environment::LIVE
            ? config('adyen.live_endpoint_url_prefix')
            : null;

        $client->setEnvironment($environment, $livePrefix !== '' ? $livePrefix : null);

        $client->setTimeout((int) config('adyen.timeout'));
        $client->setConnectionTimeout((int) config('adyen.connection_timeout'));

        return $client;
    }
}

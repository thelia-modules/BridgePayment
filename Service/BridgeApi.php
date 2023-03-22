<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class BridgeApi
{
    public function __construct(protected Configuration $configurationService)
    {
    }

    /**
     * @param mixed $data
     * @throws GuzzleException
     */
    public function apiCall(
        string $method,
        string $uri,
        string $data
    ): ResponseInterface
    {
        $httpClient = new Client();

        $this->configurationService->checkConfiguration();

        $clientId = BridgePayment::getConfigValue('prod_client_id');
        $clientSecret = BridgePayment::getConfigValue('prod_client_secret');

        if ('TEST' === BridgePayment::getConfigValue('run_mode', 'TEST')) {
            $clientId = BridgePayment::getConfigValue('client_id');
            $clientSecret = BridgePayment::getConfigValue('client_secret');
        }

        return $httpClient->request($method, $uri, [
            'headers' => [
                'Bridge-Version' => BridgePayment::BRIDGE_API_VERSION,
                'Content-Type' => 'application/json',
                'Client-Id' => $clientId,
                'Client-Secret' => $clientSecret
            ],
            'body' => $data
        ]);
    }
}
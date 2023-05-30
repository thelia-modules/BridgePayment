<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class BridgeApi
{
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
        $clientId = BridgePayment::getConfigValue('client_id');
        $clientSecret = BridgePayment::getConfigValue('client_secret');

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
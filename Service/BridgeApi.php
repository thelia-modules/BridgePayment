<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BridgeApi
{
    public function __construct(protected HttpClientInterface $httpClient)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function apiCall(
        string $method,
        string $request,
        $data
    ): ResponseInterface
    {
        $clientId = BridgePayment::getConfigValue('client_id');
        $clientSecret = BridgePayment::getConfigValue('client_secret');

        return $this->httpClient->request($method, $request, [
            'headers' => [
                'Client-Id' => $clientId,
                'Client-Secret' => $clientSecret,
                'Bridge-Version' => BridgePayment::BRIDGE_API_VERSION,
                'Content-Type' => 'application/json'
            ],
            'json' => $data
        ]);
    }
}
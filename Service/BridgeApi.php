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
        $data
    ): ResponseInterface
    {
//        BridgePayment::setConfigValue('client_id', "9013e9c76c8e4a588bec1deb9eea7cea");
//        BridgePayment::setConfigValue('client_secret', '43bZ6G15dPIEbiRKO1ISlzZfzfpofd8a4lFcUMd1UuLuzbKj0kKdOfeEK6jC4jNh');
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
            'json' => $data
        ]);
    }
}
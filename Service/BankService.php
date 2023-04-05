<?php

namespace BridgePayment\Service;

use BridgePayment\Request\BankRequest;
use BridgePayment\BridgePayment;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;
use Thelia\Core\Translation\Translator;

class BankService
{
    public function __construct(
        protected BridgeApi           $apiService,
        protected SerializerInterface $serializer
    )
    {
    }

    /**
     * @param string $countryCode
     */
    public function getBanks(string $countryCode): array
    {
        $response = $this->apiService->apiCall(
            'GET',
            BridgePayment::BRIDGE_API_URL . '/v2/banks?capabilities=single_payment&limit=500',
            [
                'country_code' => $countryCode
            ]
        );

        if ($response->getStatusCode() >= 400) {
            throw new Exception(
                Translator::getInstance()->trans('Banks not found.', [], BridgePayment::DOMAIN_NAME)
            );
        }

        $banksResponse = json_decode($response->getContent(), true);

        return $banksResponse['resources'] ?? [];
    }
}
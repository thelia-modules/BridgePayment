<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Thelia\Core\Translation\Translator;

class BankService
{
    /** @var BridgeApi  */
    protected $apiService;

    public function __construct( BridgeApi $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * @throws Exception|GuzzleException
     */
    public function getBanks(string $countryCode): array
    {
        $response = $this->apiService->apiCall(
            'GET',
            BridgePayment::BRIDGE_API_URL . '/v2/banks?countries=' . $countryCode . '&capabilities=single_payment&limit=500',
            ""
        );

        if ($response->getStatusCode() >= 400) {
            throw new Exception(
                Translator::getInstance()->trans('Banks not found.', [], BridgePayment::DOMAIN_NAME)
            );
        }

        $banksResponse = json_decode($response->getBody()->getContents(), true);

        return $banksResponse['resources'] ?? [];
    }
}
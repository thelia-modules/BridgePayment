<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use Doctrine\Common\Cache\FilesystemCache;
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
        $cacheDriver = new FilesystemCache(THELIA_CACHE_DIR . 'bridge');

        if (null !== $bankList = $cacheDriver->fetch('banks')) {
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

            if (empty($banksResponse['resources'])) {
                throw new Exception(
                    Translator::getInstance()->trans('No banks not found.', [], BridgePayment::DOMAIN_NAME)
                );
            }

            $bankList = serialize($banksResponse['resources']);

            $cacheDriver->save('banks', $bankList, 86400);
        }

        return unserialize($bankList);
    }
}
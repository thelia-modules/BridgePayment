<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use Doctrine\Common\Cache\FilesystemCache;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JsonException as JsonExceptionAlias;
use OpenApi\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Thelia\Core\Translation\Translator;
use GuzzleHttp\Client;


class BankService
{
    /** @var BridgeApi  */
    protected BridgeApi $apiService;

    public function __construct( BridgeApi $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * @param string $countryCode
     * @return mixed
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     */
    public function getBanks(string $countryCode): mixed
    {
        $cacheDriver = new FilesystemCache(THELIA_CACHE_DIR . 'bridge');

        if (null !== $bankList = $cacheDriver->fetch('banks')) {

            $response = $this->apiService->apiCall(
                'GET',
                BridgePayment::BRIDGE_API_URL . '/v2/banks?countries=' . $countryCode . '&capabilities=single_payment&limit=500'
            );

            if ($response->getStatusCode() >= 400) {
                throw new Exception(
                    Translator::getInstance()?->trans('Banks not found.', [], BridgePayment::DOMAIN_NAME)
                );
            }

            $banksResponse = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (empty($banksResponse['resources'])) {
                throw new Exception(
                    Translator::getInstance()?->trans('No banks not found.', [], BridgePayment::DOMAIN_NAME)
                );
            }

            $bankList = serialize($banksResponse['resources']);
            $cacheDriver->save('banks', $bankList, 86400);
        }

        return unserialize($bankList);
    }
}
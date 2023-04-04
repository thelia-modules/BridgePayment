<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use BridgePayment\Request\PaymentLinkRequest;
use Exception;
use Propel\Runtime\Exception\PropelException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Order;
use Thelia\Tools\URL;

class BridgeApiService
{
    const BRIDGE_API_VERSION = '2021-06-01';
    const BRIDGE_API_URL = 'https://api.bridgeapi.io';

    public function __construct(protected HttpClientInterface $httpClient)
    {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws PropelException
     */
    public function getPaymentLink(Order $order): array
    {
        $request = self::BRIDGE_API_URL . '/v2/payment-links';

        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();

        $data = [
            "user" => [
                "first_name" => $invoiceAddress->getFirstname(),
                "last_name" => $invoiceAddress->getLastname(),
                "external_reference" => $order->getCustomer()->getRef(),
            ],
            "client_reference" => $order->getCustomer()->getRef(),
            "callback_url" => URL::getInstance()->absoluteUrl("/order/placed/" . $order->getId()),
            "transactions" => [
                [
                    "amount" => round($order->getTotalAmount(), 2),
                    "currency" => $order->getCurrency()->getCode(),
                    "beneficiary" => [
                        'iban' => rtrim(BridgePayment::getConfigValue('iban'), ' '),
                        "company_name" => ConfigQuery::read('store_name')
                    ],
                    "label" => $order->getRef(),
                    "end_to_end_id" => $order->getRef()
                ]
            ]
        ];

        $response = $this->apiCall('POST', $request, $data);

        try {
            $content = json_decode($response->getContent(), true);
            return ['url' => $content['url']];
        } catch (Exception) {
            $error = json_decode($response->getContent(false), true);

            Tlog::getInstance()->error('Error Bridge API link creation : ' . $error['message']);

            return ['error' => 'Error Bridge API link creation : ' . $error['message']];
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getBanks($countryCode): array
    {
        $request = self::BRIDGE_API_URL . '/v2/banks?capabilities=single_payment&limit=500';

        $response = $this->apiCall('GET', $request, null);

        try {
            $content = json_decode($response->getContent(), true);

            $banks = isset($content['resources']) ? $this->getLocalBanks($content['resources'], $countryCode) : null;

            return ['banks' => $banks];

        } catch (Exception) {
            $error = json_decode($response->getContent(false), true);

            Tlog::getInstance()->error('Error unable to get banks : ' . $error['message']);

            return ['error' => 'Error unable to get banks : ' . $error['message']];
        }
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws PropelException
     */
    public function createPaymentRequest(Order $order, $bankId): array
    {
        $request = self::BRIDGE_API_URL . '/v2/payment-requests';

        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();

        $data = [
            "successful_callback_url" => URL::getInstance()->absoluteUrl("/order/placed/" . $order->getId()),
            "unsuccessful_callback_url" => URL::getInstance()->absoluteUrl("/order/failed/" . $order->getId() . "/error"),
            "transactions" => [
                [
                    "currency" => $order->getCurrency()->getCode(),
                    "label" => $order->getRef(),
                    "beneficiary" => [
                        'iban' => rtrim(BridgePayment::getConfigValue('iban'), ' '),
                        "company_name" => ConfigQuery::read('store_name')
                    ],
                    "amount" => round($order->getTotalAmount(), 2),
                    "client_reference" => $order->getCustomer()->getRef(),
                    "end_to_end_id" => $order->getRef()
                ]
            ],
            "user" => [
                "first_name" => $invoiceAddress->getFirstname(),
                "last_name" => $invoiceAddress->getLastname(),
                "external_reference" => $order->getCustomer()->getRef()
            ],
            "bank_id" => (int)$bankId
        ];

        $response = $this->apiCall('POST', $request, $data);

        try {
            $content = json_decode($response->getContent(), true);
            return ['url' => $content['consent_url']];
        } catch (Exception) {
            $error = json_decode($response->getContent(false), true);

            Tlog::getInstance()->error('Error Bridge API link creation : ' . $error['message']);

            return ['error' => 'Error Bridge API link creation : ' . $error['message']];
        }
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws PropelException
     */
    public function createRefund(Order $order): array
    {
        $request = self::BRIDGE_API_URL . '/v2/payment-requests';

        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();

        $iban = $this->getIban($order->getTransactionRef());

        if (array_key_exists('error', $iban)) {
            return $iban;
        }

        $storeBankId = BridgePayment::getConfigValue('bank_id');

        if (null === $storeBankId) {
            return ['error' => 'Please select a bank in BridgePayment configurations'];
        }

        $data = [
            "successful_callback_url" => URL::getInstance()->absoluteUrl("/admin/module/bridgepayment/refund/success/" . $order->getId()),
            "unsuccessful_callback_url" => URL::getInstance()->absoluteUrl("/admin/module/bridgepayment/refund/failed/" . $order->getId()),
            "transactions" => [
                [
                    "currency" => $order->getCurrency()->getCode(),
                    "label" => $order->getRef() . ' refund',
                    "amount" => round($order->getTotalAmount(), 2),
                    "beneficiary" => [
                        'iban' => $iban['iban'],
                        "name" => $invoiceAddress->getFirstname() . ' ' . $invoiceAddress->getLastname()
                    ],
                    "client_reference" => $order->getCustomer()->getRef(),
                    "end_to_end_id" => $order->getRef() . '_refund'
                ]
            ],
            "user" => [
                "company_name" => ConfigQuery::read('store_name'),
                "external_reference" => ConfigQuery::read('store_name') . '_refund'
            ],
            "bank_id" => (int)$storeBankId
        ];

        $response = $this->apiCall('POST', $request, $data);

        try {
            $content = json_decode($response->getContent(), true);
            return ['url' => $content['consent_url']];
        } catch (Exception) {
            $error = json_decode($response->getContent(false), true);
            $message = array_key_exists('message', $error) ? $error['message'] : $error[0]['message'];

            Tlog::getInstance()->error('Error Bridge API refund : ' . $message);

            return ['error' => 'Error Bridge API refund : ' . $message];
        }
    }

    protected function getLocalBanks($banks, $countryCode): array
    {
        $localBanks = [];
        foreach ($banks as $bank) {
            if ($bank['country_code'] === $countryCode) {
                $localBanks[] = $bank;
            }
        }
        return $localBanks;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function getIban($paymentRequestId): array
    {
        $request = self::BRIDGE_API_URL . '/v2/payment-requests/' . $paymentRequestId;

        $response = $this->apiCall('GET', $request, null);

        try {
            $content = json_decode($response->getContent(), true);

            $iban = $content['sender']['iban'];

            return ['iban' => $iban];

        } catch (Exception) {
            $error = json_decode($response->getContent(false), true);

            Tlog::getInstance()->error('Error unable to get payment request : ' . $error['message']);

            return ['error' => 'Error unable to get payment request : ' . $error['message']];
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function apiCall($method, $request, $data): ResponseInterface
    {
        $clientId = BridgePayment::getConfigValue('client_id');
        $clientSecret = BridgePayment::getConfigValue('client_secret');

        return $this->httpClient->request($method, $request, [
            'headers' => [
                'Client-Id' => $clientId,
                'Client-Secret' => $clientSecret,
                'Bridge-Version' => self::BRIDGE_API_VERSION,
                'Content-Type' => 'application/json'
            ],
            'json' => $data
        ]);
    }
}
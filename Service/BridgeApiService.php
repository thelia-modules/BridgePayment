<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Order;
use Thelia\Tools\URL;

class BridgeApiService
{
    const BRIDGE_API_VERSION = '2021-06-01';
    const BRIDGE_API_URL = 'https://api.bridgeapi.io';

    protected $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create();
    }

    public function getPaymentLink(Order $order)
    {
        $request = self::BRIDGE_API_URL.'/v2/payment-links';

        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();

        $data = [
            "user" => [
                "first_name" => $invoiceAddress->getFirstname(),
                "last_name" => $invoiceAddress->getLastname(),
                "external_reference" => $order->getCustomer()->getRef(),
            ],
            "client_reference" => $order->getCustomer()->getRef(),
            "callback_url" => URL::getInstance()->absoluteUrl("/order/placed/".$order->getId()),
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
        }catch (\Exception $exception) {
            $error = json_decode($response->getContent(false), true);
            $message = array_key_exists('message', $error) ? $error['message'] : $error[0]['message'];

            Tlog::getInstance()->error('Error Bridge API link creation : ' . $message);

            return ['error' => 'Error Bridge API link creation : ' . $message];
        }
    }

    public function getBanks($countryCode)
    {
        $request = self::BRIDGE_API_URL.'/v2/banks?capabilities=single_payment&limit=500';

        $response = $this->apiCall('GET', $request, null);

        try {
            $content = json_decode($response->getContent(), true);

            $banks = isset($content['resources']) ? $this->getLocalBanks($content['resources'], $countryCode) : null;

            return ['banks' => $banks];

        }catch (\Exception $exception) {
            $error = json_decode($response->getContent(false), true);
            $message = array_key_exists('message', $error) ? $error['message'] : $error[0]['message'];

            Tlog::getInstance()->error('Error unable to get banks : ' . $message);

            return ['error' => 'Error unable to get banks : ' . $message];
        }
    }


    public function createPaymentRequest(Order $order, $bankId)
    {
        $request = self::BRIDGE_API_URL.'/v2/payment-requests';

        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();

        $data = [
            "successful_callback_url" => URL::getInstance()->absoluteUrl("/order/placed/".$order->getId()),
            "unsuccessful_callback_url" => URL::getInstance()->absoluteUrl("/order/failed/".$order->getId()."/error"),
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
        }catch (\Exception $exception) {
            $error = json_decode($response->getContent(false), true);
            $message = array_key_exists('message', $error) ? $error['message'] : $error[0]['message'];

            Tlog::getInstance()->error('Error Bridge API link creation : ' . $message);

            return ['error' => 'Error Bridge API link creation : ' . $message];
        }
    }

    public function createRefund(Order $order)
    {
        $request = self::BRIDGE_API_URL.'/v2/payment-requests';

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
            "successful_callback_url" => URL::getInstance()->absoluteUrl("/admin/module/bridgepayment/refund/success/".$order->getId()),
            "unsuccessful_callback_url" => URL::getInstance()->absoluteUrl("/admin/module/bridgepayment/refund/failed/".$order->getId()),
            "transactions" => [
                [
                    "currency" => $order->getCurrency()->getCode(),
                    "label" => $order->getRef().' refund',
                    "amount" => round($order->getTotalAmount(), 2),
                    "beneficiary" => [
                        'iban' => $iban['iban'],
                        "name" => $invoiceAddress->getFirstname().' '.$invoiceAddress->getLastname()
                    ],
                    "client_reference" => $order->getCustomer()->getRef(),
                    "end_to_end_id" => $order->getRef().'_refund'
                ]
            ],
            "user" => [
                "company_name" => ConfigQuery::read('store_name'),
                "external_reference" => ConfigQuery::read('store_name').'_refund'
            ],
            "bank_id" => (int)$storeBankId
        ];

        $response = $this->apiCall('POST', $request, $data);

        try {
            $content = json_decode($response->getContent(), true);
            return ['url' => $content['consent_url']];
        }catch (\Exception $exception) {
            $error = json_decode($response->getContent(false), true);
            $message = array_key_exists('message', $error) ? $error['message'] : $error[0]['message'];

            Tlog::getInstance()->error('Error Bridge API refund : ' . $message);

            return ['error' => 'Error Bridge API refund : ' . $message];
        }
    }

    protected function getLocalBanks($banks, $countryCode)
    {
        $localBanks = [];
        foreach ($banks as $bank) {
            if ($bank['country_code'] === $countryCode) {
                $localBanks[] = $bank;
            }
        }
        return $localBanks;
    }

    protected function getIban($paymentRequestId)
    {
        $request = self::BRIDGE_API_URL.'/v2/payment-requests/'.$paymentRequestId;

        $response = $this->apiCall('GET', $request, null);

        try {
            $content = json_decode($response->getContent(), true);

            $iban = $content['sender']['iban'];

            return ['iban' => $iban];

        }catch (\Exception $exception) {
            $error = json_decode($response->getContent(false), true);
            $message = array_key_exists('message', $error) ? $error['message'] : $error[0]['message'];

            Tlog::getInstance()->error('Error unable to get payment request : ' . $message);

            return ['error' => 'Error unable to get payment request : ' . $message];
        }
    }

    protected function apiCall($method, $request, $data)
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
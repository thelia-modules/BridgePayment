<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Propel\Runtime\Exception\PropelException;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Order;
use Thelia\Tools\URL;

class BridgePaymentInitiation
{
    /** @var BridgeApi  */
    protected $apiService;

    public function __construct( BridgeApi $apiService )
    {
        $this->apiService = $apiService;
    }

    /**
     * @throws PropelException| Exception| GuzzleException
     */
    public function createPaymentRequest(Order $order, $bankId): string
    {
        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();

        $data = [
            "successful_callback_url" => URL::getInstance()->absoluteUrl("/order/placed/" . $order->getId()),
            "unsuccessful_callback_url" => URL::getInstance()->absoluteUrl("/order/failed/" . $order->getId() . "/error"),
            "user" => [
                "first_name" => $invoiceAddress->getFirstname(),
                "last_name" => $invoiceAddress->getLastname(),
                "external_reference" => $order->getCustomer()->getRef()
            ],
            "bank_id" => (int)$bankId,
            "client_reference" => $order->getCustomer()->getRef(),
            "transactions" => [
                "currency" => $order->getCurrency()->getCode(),
                "label" => $order->getRef(),
                "amount" => round($order->getTotalAmount(), 2),
                "client_reference" => $order->getCustomer()->getRef(),
                "end_to_end_id" => $order->getRef()
            ]
        ];

        $response = $this->apiService->apiCall(
            'POST',
            BridgePayment::BRIDGE_API_URL . '/v2/payment-requests',
            $data
        );

        if ($response->getStatusCode() >= 400) {
            throw new Exception(
                Translator::getInstance()->trans('Bank not found.', [], BridgePayment::DOMAIN_NAME)
            );
        }

        $paymentInitiationResponse = json_decode($response->getBody()->getContents(), true);

        return $paymentInitiationResponse['consent_url'];
    }
}
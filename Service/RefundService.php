<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Propel\Runtime\Exception\PropelException;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Order;
use Thelia\Tools\URL;

class RefundService
{
    /** @var BridgeApi */
    public $bridgeApiService;
    public function __construct(BridgeApi $bridgeApiService){
        $this->bridgeApiService = $bridgeApiService;
    }

    /**
     * @throws PropelException
     * @throws GuzzleException
     */
    public function createRefund(Order $order): array
    {
        $uri = BridgePayment::BRIDGE_API_URL . '/v2/banks';

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

        $response = $this->bridgeApiService->apiCall('POST', $uri, $data);

        try {
            $content = json_decode($response->getBody()->getContents(), true);
            return ['url' => $content['consent_url']];
        } catch (Exception $ex) {
            $error = json_decode($response->getReasonPhrase(), true);
            $message = array_key_exists('message', $error) ? $error['message'] : $error[0]['message'];

            Tlog::getInstance()->error('Error Bridge API refund : ' . $message);

            return ['error' => 'Error Bridge API refund : ' . $message];
        }
    }

    /**
     * @throws GuzzleException
     */
    protected function getIban($paymentRequestId): array
    {
        $uri = BridgePayment::BRIDGE_API_URL . '/v2/payment-requests/' . $paymentRequestId;

        $response = $this->bridgeApiService->apiCall('GET', $uri, null);

        try {
            $content = json_decode($response->getBody()->getContents(), true);

            $iban = $content['sender']['iban'];

            return ['iban' => $iban];

        } catch (Exception $ex) {

            $error = json_decode($response->getReasonPhrase(), true);

            Tlog::getInstance()->error('Error unable to get payment request : ' . $error['message']);

            return ['error' => 'Error unable to get payment request : ' . $error['message']];
        }
    }

}
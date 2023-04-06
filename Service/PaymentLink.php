<?php

namespace BridgePayment\Service;

use BridgePayment\Model\Notification\Notification;
use BridgePayment\Model\Notification\NotificationContent;
use Exception;
use BridgePayment\Exception\BridgePaymentLinkException;
use BridgePayment\Model\BridgePaymentLink;
use BridgePayment\Model\BridgePaymentLinkQuery;
use BridgePayment\Response\PaymentLinkErrorResponse;
use BridgePayment\BridgePayment;
use BridgePayment\Request\PaymentLinkRequest;
use BridgePayment\Response\PaymentLinkResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Propel\Runtime\Exception\PropelException;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Order;

class PaymentLink
{
    const PAYMENT_LINK_STATUS = [
        'VALID' => [
            'color' => '#bae313',
        ],
        'EXPIRED' => [
            'color' => '#e31313'
        ],
        'REVOKED' => [
            'color' => '#e3138b'
        ],
        'COMPLETED' => [
            'color' => '#13e319'
        ]
    ];

    public function __construct(protected BridgeApi $apiService, protected SerializerInterface $serializer)
    {
    }

    /**
     * @throws Exception
     */
    public function createPaymentLink(Order $order)
    {
        $response = $this->apiService->apiCall(
            'POST',
            BridgePayment::BRIDGE_API_URL . '/v2/payment-links',
            (new PaymentLinkRequest())->hydrate($order)
        );

        if ($response->getStatusCode() >= 400) {
            throw new BridgePaymentLinkException($this->serializer->deserialize(
                $response->getContent(false),
                PaymentLinkErrorResponse::class,
                'json'
            ));
        }

        $paymentLinkResponse = $this->serializer->deserialize(
            $response->getContent(),
            PaymentLinkResponse::class,
            'json'
        );

        (new BridgePaymentLink())
            ->setUuid($paymentLinkResponse->id)
            ->setStatus('VALID')
            ->setLink($paymentLinkResponse->url)
            ->setOrderId($order->getId())
            ->save();

        return $paymentLinkResponse->url;
    }

    /**
     * @throws PropelException
     */
    public function paymentLinkUpdate(NotificationContent $notification): void
    {
        $paymentLink = BridgePaymentLinkQuery::create()
            ->useOrderQuery()
            ->useCustomerQuery()
            ->filterByRef($notification->clientReference)
            ->endUse()
            ->endUse()
            ->filterByUuid($notification->paymentLinkId)
            ->findOne();

        if (!$paymentLink) {
            throw new Exception(sprintf('Payment link not found on this customer : %s', $notification->clientReference));
        }

        if ($paymentLink->getStatus() === 'VALID') {
            $paymentLink->setStatus($notification->status)->save();
        }
    }

    /**
     * @throws Exception
     */
    public function revokeLink(string $paymentLinkUuid): PaymentLinkResponse
    {
        $response = $this->apiService->apiCall(
            'POST',
            BridgePayment::BRIDGE_API_URL . "/v2/payment-links/$paymentLinkUuid/revoke",
            []
        );

        if ($response->getStatusCode() >= 400) {
            throw new Exception(
                Translator::getInstance()->trans("Can't revoke link.", [], BridgePayment::DOMAIN_NAME)
            );
        }

        $paymentLinkResponse = $this->serializer->deserialize(
            $response->getContent(),
            PaymentLinkResponse::class,
            'json'
        );

        return $paymentLinkResponse;
    }

    /**
     * @throws Exception
     */
    public function refreshLink(string $paymentLinkUuid)
    {
        $response = $this->apiService->apiCall(
            'GET',
            BridgePayment::BRIDGE_API_URL . "/v2/payment-links/$paymentLinkUuid",
            []
        );

        if ($response->getStatusCode() >= 400) {
            throw new Exception(
                Translator::getInstance()->trans("Can't revoke link.", [], BridgePayment::DOMAIN_NAME)
            );
        }

        return $this->serializer->deserialize(
            $response->getContent(),
            PaymentLinkResponse::class,
            'json'
        );
    }
}
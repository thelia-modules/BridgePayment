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
use Thelia\Model\Order;

class PaymentLink
{
    const PAYMENT_LINK_STATUS = [
        [
            'code' => 'VALID',
            'label' => 'Valid'
        ],
        [
            'code' => 'EXPIRED',
            'label' => 'Expired'
        ],
        [
            'code' => 'REVOKED',
            'label' => 'Revoked'
        ],
        [
            'code' => 'Completed',
            'label' => 'COMPLETED'
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

        $paymentLink->setStatus($notification->status)->save();
    }
}
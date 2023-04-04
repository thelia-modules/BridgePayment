<?php

namespace BridgePayment\Service;

use BridgePayment\Exception\BridgePaymentLinkException;
use BridgePayment\Model\BridgePaymentLink;
use BridgePayment\Response\PaymentLinkErrorResponse;
use Thelia\Model\Order;
use BridgePayment\BridgePayment;
use BridgePayment\Request\PaymentLinkRequest;
use BridgePayment\Response\PaymentLinkResponse;
use Symfony\Component\Serializer\SerializerInterface;

class PaymentLink
{
    public function __construct(protected BridgeApi $apiService, protected SerializerInterface $serializer)
    {
    }

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
            ->setUuid($paymentLinkResponse->uuid)
            ->setLink($paymentLinkResponse->url)
            ->setCustomerId($order->getCustomerId())
            ->setOrderId($order->getId())
            ->save();
        return $paymentLinkResponse->url;
    }
}
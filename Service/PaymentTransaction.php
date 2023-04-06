<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use BridgePayment\Model\BridgePaymentTransactionQuery;
use Exception;
use BridgePayment\Model\BridgePaymentTransaction;
use BridgePayment\Model\Notification\NotificationContent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Order;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;

class PaymentTransaction
{
    const PAYMENT_TRANSACTION_STATUS = [
        'CREA' => '#bae313',
        'ACTC' => '#bae313',
        'PDNG' => '#138ce3',
        'ACSC' => '#13e319',
        'RJCT' => '#e3138b'
    ];

    public function __construct(
        protected BridgeApi                $apiService,
        protected SerializerInterface      $serializer,
        protected EventDispatcherInterface $dispatcher
    )
    {
    }

    public function getPaymentTransactionRequest(string $paymentRequestId): void
    {
        $response = $this->apiService->apiCall(
            'POST',
            BridgePayment::BRIDGE_API_URL . "/v2/payment-requests/$paymentRequestId",
            []
        );

        if ($response->getStatusCode() >= 400) {
            throw new Exception(
                Translator::getInstance()->trans("Can't revoke link.", [], BridgePayment::DOMAIN_NAME)
            );
        }
    }

    /**
     * @throws Exception
     */
    public function savePaymentTransaction(NotificationContent $notification, int $timestamp): void
    {
        $order = OrderQuery::create()
            ->filterByRef($notification->endToEndId)
            ->findOne();

        if (!$order) {
            throw new Exception('Order not found.');
        }

        $bridgePaymentTransaction = BridgePaymentTransactionQuery::create()
            ->filterByOrderId($order->getId())
            ->filterByUuid($notification->paymentTransactionId)
            ->findOne();

        $timestamp = substr($timestamp, 0, 10);
        $timestamp =  new \DateTime("@$timestamp");

        if ($bridgePaymentTransaction) {
            if (in_array($bridgePaymentTransaction->getStatus(), ['ACSC', 'RJCT'])) {
                return;
            }

            if ($bridgePaymentTransaction->getTimestamp() > $timestamp) {
                return;
            }
        } else {
            $bridgePaymentTransaction = new BridgePaymentTransaction();
        }


        $bridgePaymentTransaction
            ->setUuid($notification->paymentTransactionId)
            ->setStatusReason($notification->statusReason)
            ->setOrderId($order->getId())
            ->setTimestamp($timestamp)
            ->setPaymentLinkId($notification->paymentLinkId)
            ->setPaymentRequestId($notification->paymentRequestId);

        if ($notification->status) {
            $bridgePaymentTransaction->setStatus($notification->status);
        }

        if ($notification->paymentLinkId) {
            $bridgePaymentTransaction->setPaymentLinkId($notification->paymentLinkId);
        }

        $bridgePaymentTransaction->save();

        if ($notification->status) {
            $this->updateOrderStatus($notification->status, $order);
        }
    }

    protected function updateOrderStatus(string $status, Order $order, string $transactionRef = null): void
    {
        $orderStatusCode = match ($status) {
            "CREA", "ACTC" => 'payment_created',
            "PDNG" => "payment_pending",
            "RJCT" => "payment_rejected",
            "ACSC" => "paid",
            default => 'payment_created'
        };

        $orderStatus = OrderStatusQuery::create()
            ->filterByCode($orderStatusCode)
            ->findOne();

        if (null !== $orderStatus) {
            $event = (new OrderEvent($order))->setStatus($orderStatus->getId());
            $this->dispatcher->dispatch(
                $event,
                TheliaEvents::ORDER_UPDATE_STATUS
            );

            if ($event->getOrder()->isPaid()) {
                $event->getOrder()->setTransactionRef($transactionRef)->save();
            }
        }
    }
}
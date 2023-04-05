<?php

namespace BridgePayment\Service;

use Exception;
use BridgePayment\Model\BridgePaymentTransaction;
use BridgePayment\Model\Notification\NotificationContent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Order;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;

class PaymentTransaction
{
    const PAYMENT_TRANSACTION_STATUS = [
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

    public function __construct(
        protected BridgeApi                $apiService,
        protected SerializerInterface      $serializer,
        protected EventDispatcherInterface $dispatcher
    )
    {
    }

    /**
     * @throws Exception
     */
    public function savePaymentTransaction(NotificationContent $notification): void
    {
        $order = OrderQuery::create()
            ->filterByRef($notification->endToEndId)
            ->findOne();

        if (!$order) {
            throw new Exception('Order not found.');
        }

        $bridgePaymentTransaction = new BridgePaymentTransaction();

        $status = $notification->status ?? 'CREA';

        $bridgePaymentTransaction->setStatus($status)
            ->setUuid($notification->paymentTransactionId)
            ->setStatusReason($notification->statusReason)
            ->setOrderId($order->getId())
            ->setPaymentLinkId($notification->paymentLinkId)
            ->setPaymentRequestId($notification->paymentRequestId);

        if ($notification->paymentLinkId) {
            $bridgePaymentTransaction->setPaymentLinkId($notification->paymentLinkId);
        }

        $bridgePaymentTransaction->save();

        $this->updateOrderStatus($notification->status, $order);
    }

    protected function updateOrderStatus(string $status, Order $order): void
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
            $this->dispatcher->dispatch(
                (new OrderEvent($order))->setStatus($orderStatus->getId()),
                TheliaEvents::ORDER_UPDATE_STATUS
            );
        }
    }
}
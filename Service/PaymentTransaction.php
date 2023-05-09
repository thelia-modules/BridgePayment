<?php

namespace BridgePayment\Service;

use BridgePayment\Exception\BridgePaymentException;
use BridgePayment\Model\Api\Transaction;
use BridgePayment\Request\PaymentRequest;
use BridgePayment\Response\PaymentErrorResponse;
use BridgePayment\Response\PaymentResponse;
use DateTime;
use Exception;
use BridgePayment\BridgePayment;
use BridgePayment\Model\BridgePaymentTransactionQuery;
use BridgePayment\Model\BridgePaymentTransaction;
use BridgePayment\Model\Notification\NotificationContent;
use GuzzleHttp\Exception\GuzzleException;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Order;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;

class PaymentTransaction
{
    public const PAYMENT_TRANSACTION_STATUS = [
        'CREA' => '#bae313',
        'ACTC' => '#bae313',
        'PDNG' => '#138ce3',
        'ACSC' => '#13e319',
        'RJCT' => '#e3138b'
    ];
    /** @var BridgeApi */
    protected $apiService;
    /** @var Serializer */
    public $serializer;
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(
        BridgeApi                $apiService,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->dispatcher = $dispatcher;
        $this->serializer = new Serializer([new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter())], [new JsonEncoder()]);
        $this->apiService = $apiService;
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function refreshTransaction(string $paymentRequestId): PaymentResponse
    {
        $response = $this->apiService->apiCall(
            'GET',
            BridgePayment::BRIDGE_API_URL . "/v2/payment-requests/$paymentRequestId",
            ''
        );

        if ($response->getStatusCode() >= 400) {
            throw new BridgePaymentException(
                (PaymentErrorResponse::class)($this->serializer->deserialize(
                    $response->getBody()->getContents(),
                    PaymentErrorResponse::class,
                    'json'
                ))
            );
        }

        return $this->serializer->deserialize(
            $response->getBody()->getContents(),
            PaymentResponse::class,
            'json'
        );


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
        $timestamp =  new DateTime("@$timestamp");

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

    /**
     * @throws PropelException
     */
    protected function updateOrderStatus(string $status, Order $order, string $transactionRef = null): void
    {
        switch ($status) {
            case "CREA":
            case "ACTC":
                $orderStatusCode = 'payment_created';
                break;
            case "PDNG":
                $orderStatusCode = "payment_pending";
                break;
            case "RJCT":
                $orderStatusCode = "payment_rejected";
                break;
            case "ACSC":
                $orderStatusCode = "paid";
                break;
            default :
                $orderStatusCode = 'payment_created';
        }

        $orderStatus = OrderStatusQuery::create()
            ->filterByCode($orderStatusCode)
            ->findOne();

        if (null !== $orderStatus) {
            $event = (new OrderEvent($order))->setStatus($orderStatus->getId());
            $this->dispatcher->dispatch( TheliaEvents::ORDER_UPDATE_STATUS, $event);

            if ($event->getOrder()->isPaid()) {
                $event->getOrder()->setTransactionRef($transactionRef)->save();
            }
        }
    }
}
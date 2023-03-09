<?php

namespace BridgePayment\Controller;

use BridgePayment\BridgePayment;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Order;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;

/**
 * @Route("/bridge/notification", name="bridgepayment_notification")
 */
class WebHookController extends BaseFrontController
{
    /**
     * @Route("", name="", methods="POST")
     */
    public function notification(Request $request, EventDispatcherInterface $dispatcher)
    {
        $webhookSecret = BridgePayment::getConfigValue('hook_secret');
        if (null !== $webhookSecret) {
            $this->checkSignature($request, $webhookSecret);
        }

        $requestContent = json_decode($request->getContent(), true);

        $content = $requestContent['content'];
        $orderRef = array_key_exists('end_to_end_id', $content) ? $content['end_to_end_id'] : null;
        $paymentRequestId = array_key_exists('payment_request_id',$content) ? $content['payment_request_id'] : null;
        $status = array_key_exists('status',$content) ? $content['status'] : null;

        $order = OrderQuery::create()->filterByRef($orderRef)->findOne();

        if (null !== $order) {
            $order->setTransactionRef($paymentRequestId)->save();

            if (null !== $status) {
                $this->updateOrderStatus($status, $order, $dispatcher);
            }
        }

        $response = new Response(json_encode(['message' => 'success']), 200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    protected function updateOrderStatus($status, Order $order, EventDispatcherInterface $dispatcher)
    {
        $orderStatusCode = match ($status) {
            "ACSC" => "paid",
            "PDNG" => "payment_pending",
            "RJCT" => "payment_rejected",
            default => null,
        };

        $orderStatus = OrderStatusQuery::create()
            ->filterByCode($orderStatusCode)
            ->findOne();

        if (null !== $orderStatus){
            $event = new OrderEvent($order);
            $event->setStatus($orderStatus->getId());
            $dispatcher->dispatch($event, TheliaEvents::ORDER_UPDATE_STATUS);
        }
    }

    protected function checkSignature(Request $request, $webhookSecret)
    {
        $signatures = $request->headers->get('bridgeapi-signature');

        if ($signatures === null) {
            throw new \Exception('No signatures found');
        }

        $signatures = explode(',', $signatures);
        $hookSignature = hash_hmac('SHA256', (string)$request->getContent(), $webhookSecret);

        $valid = false;
        foreach ($signatures as $signature) {
            $signature = substr($signature, 3);

            if (strtoupper($hookSignature) === $signature){
                $valid = true;
                break;
            }
        }

        if ($valid === false){
            throw new \Exception('No valid signature found');
        }
    }
}
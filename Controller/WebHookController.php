<?php

namespace BridgePayment\Controller;

use BridgePayment\BridgePayment;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Order;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;

class WebHookController extends BaseFrontController
{
    public function notification()
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $dispatcher = $this->container->get('event_dispatcher');
        $request = $this->getRequest();
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
        switch ($status) {
            case "ACSC":
                $orderStatusCode = 'paid';
                break;
            case "PDNG":
                $orderStatusCode = 'payment_pending';
                break;
            case "RJCT":
                $orderStatusCode = 'payment_rejected';
                break;
            default :
                $orderStatusCode = null;
        }

        $orderStatus = OrderStatusQuery::create()
            ->filterByCode($orderStatusCode)
            ->findOne();

        if (null !== $orderStatus){
            $event = new OrderEvent($order);
            $event->setStatus($orderStatus->getId());
            $dispatcher->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
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
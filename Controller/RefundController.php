<?php

namespace BridgePayment\Controller;

use BridgePayment\Service\BridgeApiService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Tools\URL;

class RefundController extends BaseAdminController
{
    public function refund()
    {
        /** @var BridgeApiService $bridgeApiService */
        $bridgeApiService = $this->container->get('bridgepayment.api.service');
        $request = $this->getRequest();
        $orderId = $request->get('order_id');

        try {
            $order = OrderQuery::create()->findPk($orderId);

            if (null === $order) {
                throw new \Exception('Invalid order');
            }

            $response = $bridgeApiService->createRefund($order);

            if (array_key_exists('error', $response)){
                throw new \Exception($response['error']);
            }

            return $this->generateRedirect($response['url']);

        }catch (\Exception $exception) {
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/order/update/$orderId", [
                'update_status_error_message' => $exception->getMessage()
            ]));
        }
    }

    public function refundSuccess($orderId)
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->container->get('event_dispatcher');
        $order = OrderQuery::create()->findPk($orderId);

        $orderStatus = OrderStatusQuery::create()
            ->filterByCode('refunded')
            ->findOne();

        try {
            $event = new OrderEvent($order);
            $event->setStatus($orderStatus->getId());
            $eventDispatcher->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
        }catch (\Exception $exception) {
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/order/update/$orderId", [
                'update_status_error_message' => $exception->getMessage()
            ]));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/order/update/$orderId"));
    }

    public function refundFailure($orderId)
    {
        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/order/update/$orderId", [
            'update_status_error_message' => Translator::getInstance()->trans('Refund failure')
        ]));
    }
}
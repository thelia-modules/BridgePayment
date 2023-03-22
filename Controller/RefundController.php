<?php

namespace BridgePayment\Controller;

use BridgePayment\Service\BridgeApiService;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Admin\BaseAdminController;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Tools\URL;

/**
 * @Route("/admin/module/bridgepayment/refund", name="bridgepayment_refund")
 */
class RefundController extends BaseAdminController
{
    /**
     * @Route("", name="", methods="POST")
     */
    public function refund(Request $request, BridgeApiService $bridgeApiService): RedirectResponse|Response
    {
        $orderId = $request->get('order_id');

        try {
            $order = OrderQuery::create()->findPk($orderId);

            if (null === $order) {
                throw new Exception('Invalid order');
            }

            $response = $bridgeApiService->createRefund($order);

            if (array_key_exists('error', $response)) {
                throw new Exception($response['error']);
            }

            return $this->generateRedirect($response['url']);

        } catch (Exception $exception) {
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/order/update/$orderId", [
                'update_status_error_message' => $exception->getMessage()
            ]));
        }
    }

    /**
     * @Route("/success/{orderId}", name="_success", methods="GET")
     */
    public function refundSuccess($orderId, EventDispatcherInterface $eventDispatcher): RedirectResponse|Response
    {
        $order = OrderQuery::create()->findPk($orderId);

        $orderStatus = OrderStatusQuery::create()
            ->filterByCode('refunded')
            ->findOne();

        try {
            $event = new OrderEvent($order);
            $event->setStatus($orderStatus->getId());
            $eventDispatcher->dispatch($event, TheliaEvents::ORDER_UPDATE_STATUS);
        } catch (Exception $exception) {
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/order/update/$orderId", [
                'update_status_error_message' => $exception->getMessage()
            ]));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/order/update/$orderId"));
    }

    /**
     * @Route("/failed/{orderId}", name="_failed", methods="GET")
     */
    public function refundFailure($orderId): RedirectResponse|Response
    {
        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/order/update/$orderId", [
            'update_status_error_message' => Translator::getInstance()->trans('Refund failure')
        ]));
    }
}
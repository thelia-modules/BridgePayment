<?php

namespace BridgePayment\Controller\Back;

use BridgePayment\Service\BridgeApi;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Tools\URL;
use Thelia\Core\HttpFoundation\Request;

/**
 * route : "/admin/module/bridgepayment/refund"
 * name : "bridgepayment_refund")
 */
class RefundController extends BaseAdminController
{
    /**
     * route : ""
     * name : ""
     * methods : "POST")
     * @throws GuzzleException
     */
    public function refund(Request $request): Response
    {
        /** @var BridgeApi $bridgeApiService */
        $bridgeApiService = $this->getContainer()->get('bridgepayment.api.service');

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
     * route : "/success/{orderId}"
     * name : "_success"
     * methods : "GET")
     * @return RedirectResponse|Response
     */
    public function refundSuccess(int $orderId, EventDispatcherInterface $eventDispatcher): Response
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
     * route : "/failed/{orderId}"
     * name : "_failed"
     * methods : "GET")
     * @return RedirectResponse|Response
     */
    public function refundFailure($orderId): Response
    {
        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/order/update/$orderId", [
            'update_status_error_message' => Translator::getInstance()->trans('Refund failure')
        ]));
    }
}
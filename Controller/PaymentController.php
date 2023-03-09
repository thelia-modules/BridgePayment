<?php

namespace BridgePayment\Controller;


use BridgePayment\Service\BridgeApiService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Model\OrderQuery;
use Thelia\Tools\URL;


class PaymentController extends BaseFrontController
{
    public function createPayment()
    {
        /** @var BridgeApiService $bridgeApiService */
        $bridgeApiService = $this->container->get('bridgepayment.api.service');
        $request = $this->getRequest();
        $orderId = $request->get('order_id');
        $bankId = $request->get('bank');

        $order = OrderQuery::create()->findPk($orderId);

        $response = $bridgeApiService->createPaymentRequest($order, $bankId);

        if (array_key_exists('error', $response)){
            $orderId = $order->getId();
            $message = $response['error'];
            return new RedirectResponse(URL::getInstance()->absoluteUrl("/order/failed/$orderId/$message"));
        }

        return new RedirectResponse($response['url']);
    }

}
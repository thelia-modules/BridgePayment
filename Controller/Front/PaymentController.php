<?php

namespace BridgePayment\Controller\Front;

use BridgePayment\Service\BridgePaymentInitiation;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Model\Base\OrderQuery;
use Thelia\Tools\URL;

/**
 * route : "/bridge/create-payment"
 * name : "bridgepayment_create_payment")
 * @method static getConfigValue(string $string, false $false)
 */
class PaymentController extends BaseFrontController
{
    public function createPayment($order_id, $bank_id): RedirectResponse
    {
        try {
            /** @var BridgePaymentInitiation $paymentInitiationService */
            $paymentInitiationService = $this->getContainer()->get('bridgepayment.bridge.payment.initiation');

            if(empty($order_id)) {
                $order_id = $this->getRequest()->get('order_id');
            }
            $order = OrderQuery::create()->findPk($order_id);

            if(empty($bank_id)) {
                $bank_id = $this->getRequest()->get('bank_id');
            }

            return new RedirectResponse($paymentInitiationService->createPaymentRequest($order, $bank_id));

        } catch (Exception|GuzzleException $ex) {
            $message = $ex->getMessage();
            return new RedirectResponse(URL::getInstance()->absoluteUrl("/order/failed/$order_id/$message"));
        }
    }
}
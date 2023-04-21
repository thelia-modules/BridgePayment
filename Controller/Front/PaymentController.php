<?php

namespace BridgePayment\Controller\Front;

use BridgePayment\BridgePayment;
use BridgePayment\Service\BridgePaymentInitiation;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderQuery;
use Thelia\Tools\URL;

/**
 * route : "/bridge/create-payment"
 * name : "bridgepayment_create_payment")
 */
class PaymentController extends BaseFrontController
{
    /**
     * route : ""
     * name : ""
     * methods : "POST")
     */
    public function createPayment(): RedirectResponse
    {
        try {
            /** @var Request $request */
            $request = $this->getRequest();

            /** @var BridgePaymentInitiation $bridgePaymentInitiationService */
            $bridgePaymentInitiationService = $this->getContainer()->get('bridgepayment.bridge.payment.initiation');

            $orderId = $request->get('order_id');
            $bankId = $request->get('bank_id');

            if (!$orderId || !$bankId) {
                throw new Exception(
                    Translator::getInstance()->trans('Payment request error.',
                        [],
                        BridgePayment::DOMAIN_NAME)
                );
            }

            $order = OrderQuery::create()->findPk($orderId);

            if (!$order) {
                throw new Exception(
                    Translator::getInstance()->trans('Payment request error.',
                        [],
                        BridgePayment::DOMAIN_NAME)
                );
            }

            if ($order->getBridgePaymentTransactions()->getData()) {
                throw new Exception(
                    Translator::getInstance()->trans('Payment request closed.',
                        [],
                        BridgePayment::DOMAIN_NAME)
                );
            }

            return new RedirectResponse($bridgePaymentInitiationService->createPaymentRequest($order, $bankId));

        } catch (Exception|GuzzleException $ex) {
            $message = $ex->getMessage();
            return new RedirectResponse(URL::getInstance()->absoluteUrl("/order/failed/$orderId/$message"));
        }
    }
}
<?php

namespace BridgePayment\Controller\Front;

use BridgePayment\BridgePayment;
use BridgePayment\Service\BridgePaymentInitiation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderQuery;
use Thelia\Tools\URL;

/**
 * @Route("/bridge/create-payment", name="bridgepayment_create_payment")
 */
class PaymentController extends BaseFrontController
{
    /**
     * @Route("", name="", methods="POST")
     */
    public function createPayment(
        Request                 $request,
        BridgePaymentInitiation $bridgePaymentInitiationService
    ): RedirectResponse
    {
        try {
            $orderId = $request->get('order_id');
            $bankId = $request->get('bank_id');

            if (!$orderId || !$bankId) {
                throw new \Exception(
                    Translator::getInstance()->trans('Payment request error.',
                        [],
                        BridgePayment::DOMAIN_NAME)
                );
            }

            $order = OrderQuery::create()->findPk($orderId);

            if (!$order) {
                throw new \Exception(
                    Translator::getInstance()->trans('Payment request error.',
                        [],
                        BridgePayment::DOMAIN_NAME)
                );
            }

            if ($order->getBridgePaymentTransactions()->getData() ?? null) {
                throw new \Exception(
                    Translator::getInstance()->trans('Payment request closed.',
                        [],
                        BridgePayment::DOMAIN_NAME)
                );
            }

            return new RedirectResponse($bridgePaymentInitiationService->createPaymentRequest($order, $bankId));

        } catch (\Exception $ex) {
            $message = $ex->getMessage();
            return new RedirectResponse(URL::getInstance()->absoluteUrl("/order/failed/$orderId/$message"));
        }
    }
}
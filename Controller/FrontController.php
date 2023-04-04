<?php

namespace BridgePayment\Controller;

use BridgePayment\BridgePayment;
use BridgePayment\Model\BridgePaymentLinkQuery;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\SecurityContext;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\Translation\Translator;
use Thelia\Exception\TheliaProcessException;
use Thelia\Log\Tlog;
use Thelia\Model\OrderQuery;
use Front\Front;
use Thelia\Tools\URL;

/**
 * @Route("/bridge/order", name="bridgepayment_order")
 */
class FrontController extends BaseFrontController
{
    /**
     * @Route("/cancel/{orderId}", name="bridgepayment_order_cancel", methods="GET")
     */
    public function orderFailed(
        Request         $request,
        SecurityContext $securityContext,
        ParserContext   $parserContext,
        int             $orderId
    ): Response | RedirectResponse
    {
        if (!$cancelOrder = OrderQuery::create()->findPk($orderId)) {
            Tlog::getInstance()->warning("Failed order ID '$orderId' not found.");

            throw new TheliaProcessException(
                Translator::getInstance()->trans(
                    'Received failed order id',
                    [],
                    Front::MESSAGE_DOMAIN
                ),
                TheliaProcessException::PLACED_ORDER_ID_BAD_CURRENT_CUSTOMER,
                $cancelOrder
            );
        }

        $customer = $securityContext->getCustomerUser();

        if (null === $customer || $cancelOrder->getCustomerId() !== $customer->getId()) {
            throw new TheliaProcessException(
                Translator::getInstance()->trans(
                    'Received failed order id does not belong to the current customer',
                    [],
                    Front::MESSAGE_DOMAIN
                ),
                TheliaProcessException::PLACED_ORDER_ID_BAD_CURRENT_CUSTOMER,
                $cancelOrder
            );
        }

        try {
            $paymentLinkId = $request->get('payment_link_id');
            $customerRef = $request->get('client_reference');

            if (!$paymentLinkId || !$customerRef) {
                throw new Exception(
                    Translator::getInstance()->trans(
                        "We're sorry, a problem occurred and your payment was not successful.",
                        [],
                        BridgePayment::DOMAIN_NAME
                    )
                );
            }

            $paymentLink = BridgePaymentLinkQuery::create()
                ->useCustomerQuery()
                    ->filterByRef($customerRef)
                ->endUse()
                ->filterByUuid($paymentLinkId)
                ->filterByStatus('VALID')
                ->findOne();

            if (!$paymentLink) {
                throw new Exception(
                    Translator::getInstance()->trans(
                        "We're sorry, revoked or expired payment link.",
                        [],
                        BridgePayment::DOMAIN_NAME
                    )
                );
            }

            $parserContext->set('payment_link_url', $paymentLink->getLink());

            return $this->render('order-cancel');

        } catch (Exception $ex) {
            Tlog::getInstance()->addError($ex->getMessage());

            return new RedirectResponse(
                URL::getInstance()->absoluteUrl(
                    sprintf("/order/failed/%d/%s", $orderId, 'Error'),
                    [
                        'error_message' => $ex->getMessage()
                    ]
                )
            );
        }
    }
}
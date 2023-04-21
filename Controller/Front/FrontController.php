<?php

namespace BridgePayment\Controller\Front;

use BridgePayment\BridgePayment;
use BridgePayment\Model\BridgePaymentLinkQuery;
use BridgePayment\Model\BridgePaymentTransactionQuery;
use Exception;
use Front\Front;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Translation\Translator;
use Thelia\Exception\TheliaProcessException;
use Thelia\Log\Tlog;
use Thelia\Model\OrderQuery;
use Thelia\Tools\URL;

/**
 * route : "/bridge"
 * name : "bridgepayment_order")
 */
class FrontController extends BaseFrontController
{
    /**
     * route : "/payment/{orderId}"
     * name : "bridgepayment_order_cancel"
     * methods : "GET")
     * @return Response|RedirectResponse
     */
    public function paymentCallback( int $orderId ): Response
    {
        try {
            /** @var Request $request */
            $request = $this->getRequest();

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

            $customer = $this->getSecurityContext()->getCustomerUser();

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

            $paymentLinkId = $request->get('payment_link_id');
            $paymentRequestId = $request->get('payment_request_id');
            $customerRef = $request->get('client_reference');
            $status = $request->get('status');

            if ($status === 'error') {
                throw new Exception(
                    Translator::getInstance()->trans(
                        "We're sorry, a problem occurred and your payment was not successful.",
                        [],
                        BridgePayment::DOMAIN_NAME
                    )
                );
            }

            if ($status === 'abort' && $customerRef && $paymentLinkId) {
                $paymentLink = BridgePaymentLinkQuery::create()
                    ->useOrderQuery()
                    ->useCustomerQuery()
                    ->filterByRef($customerRef)
                    ->endUse()
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

                $this->getParserContext()->set('payment_link_url', $paymentLink->getLink());

                return $this->render('callback');
            }

            if ($status !== 'success') {
                throw new Exception(
                    Translator::getInstance()->trans(
                        "We're sorry, a problem occurred and your payment was not successful.",
                        [],
                        BridgePayment::DOMAIN_NAME
                    )
                );
            }

            $paymentLink = BridgePaymentTransactionQuery::create()
                ->filterByPaymentLinkId($paymentLinkId)
                ->filterByPaymentRequestId($paymentRequestId)
                ->findOne();

            if (!$paymentLink) {
                throw new Exception(
                    Translator::getInstance()->trans(
                        "We're sorry, a problem occurred and your payment was not successful.",
                        [],
                        BridgePayment::DOMAIN_NAME
                    )
                );
            }

            return new RedirectResponse(
                URL::getInstance()->absoluteUrl(sprintf("/order/placed/%d", $orderId))
            );

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

    /**
     * route : "/bank/search/{orderId}"
     * name : "bridgepayment_bank_search_order_cancel"
     * methods : "GET")
     */
    public function searchBank(int $orderId) : Response
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $search = $request->get('search');

        $order = OrderQuery::create()->findPk($orderId);

        if (!$order) {
            return $this->pageNotFound();
        }

        return $this->render("bank-template", ['orderId' => $orderId, "search" => $search]);
    }

}
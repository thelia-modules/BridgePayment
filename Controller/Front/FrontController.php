<?php

namespace BridgePayment\Controller\Front;

use BridgePayment\BridgePayment;
use BridgePayment\Model\BridgePaymentLinkQuery;
use BridgePayment\Service\PaymentLink;
use DateTime;
use Exception;
use Front\Front;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\SecurityContext;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\Translation\Translator;
use Thelia\Exception\TheliaProcessException;
use Thelia\Log\Tlog;
use Thelia\Model\OrderQuery;
use Thelia\Tools\URL;

#[Route('/bridge', name: "bridgepayment_order")]
class FrontController extends BaseFrontController
{
    #[Route('/payment/{orderId}',
        name: "bridgepayment_order_cancel",
        requirements: [
            'orderId' => Requirement::POSITIVE_INT,
            'payment_link_id' => Requirement::UUID
        ],
        methods: "GET"
    )]
    public function paymentCallback(
        PaymentLink     $paymentLinkService,
        SecurityContext $securityContext,
        ParserContext   $parserContext,
        Request         $request,
        int             $orderId
    ): Response|RedirectResponse
    {
        $paymentLinkId = $request->get('payment_link_id');
        $status = (string) $request->get('status');

        try {
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

            $paymentLink = BridgePaymentLinkQuery::create()
                ->filterByUuid($paymentLinkId)
                ->filterByStatus('VALID')
                ->findOne();

            if (null !== $paymentLink) {
                $paymentLinkResponse = $paymentLinkService->refreshLink($paymentLinkId);
                $expireAt = new DateTime($paymentLinkResponse->expiredAt);
                $paymentLink->setStatus($paymentLinkResponse->status)
                    ->setExpiredAt($expireAt)
                    ->save();
            }

            if ($status === 'error') {
                throw new Exception(
                    Translator::getInstance()->trans(
                        "We're sorry, a problem occurred and your payment was not successful.",
                        [],
                        BridgePayment::DOMAIN_NAME
                    )
                );
            }

            if ($status === 'abort') {
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

        } catch (Exception|GuzzleException $ex) {
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
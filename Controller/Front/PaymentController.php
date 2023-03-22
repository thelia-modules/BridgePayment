<?php

namespace BridgePayment\Controller\Front;

use BridgePayment\BridgePayment;
use BridgePayment\Service\BridgePaymentInitiation;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Log\Tlog;
use Thelia\Model\Base\OrderQuery;
use Thelia\Tools\URL;

/**
 * @method static getConfigValue(string $string, false $false)
 */
class PaymentController extends BaseFrontController
{
    #[Route('/bridge/create-payment/{orderId}/{bankId}',
        name: "bridgepayment_create_payment",
        requirements: [
            'orderId' => Requirement::POSITIVE_INT,
            'bankId' => Requirement::POSITIVE_INT
        ],
        methods: "GET"
    )]
    public function createPayment(
        BridgePaymentInitiation $paymentInitiationService,
        int                     $orderId,
        int                     $bankId
    ): RedirectResponse
    {
        try {
            $order = OrderQuery::create()->findPk($orderId);

            return new RedirectResponse($paymentInitiationService->createPaymentRequest($order, $bankId));

        } catch (Exception|GuzzleException $ex) {
            Tlog::getInstance()->addError("Error on bridge payment creation: " . $ex->getMessage());
            $message = "Error on bridge payment creation";
            return new RedirectResponse(URL::getInstance()->absoluteUrl("/order/failed/$orderId/$message"));
        }
    }

    #[Route('/bridge/bank/select/{bankId}',
        name: "bridgepayment_set_bank_session",
        requirements: [
            'bankId' => Requirement::POSITIVE_INT
        ],
        methods: "GET"
    )]
    public function setSessionBankId(Session $session, int $bankId)
    {
        $session->set(BridgePayment::SELECTED_BANK_ID, $bankId);
        return new JsonResponse();
    }
}
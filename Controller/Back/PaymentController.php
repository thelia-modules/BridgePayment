<?php

namespace BridgePayment\Controller\Back;

use BridgePayment\Model\BridgePaymentTransactionQuery;
use BridgePayment\Service\PaymentTransaction;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Tools\URL;

#[Route('/admin/module/BridgePayment/payment', name: "bridgepayment_payment")]
class PaymentController extends BaseAdminController
{
    #[Route('/refresh/{paymentRequestId}',
        name: "_refresh",
        methods: "GET"
    )]
    public function refreshTransaction(
        PaymentTransaction $paymentTransactionService,
        string             $paymentRequestId
    ): Response|JsonResponse|RedirectResponse
    {
        try {
            $paymentTransaction = BridgePaymentTransactionQuery::create()
                ->filterByPaymentRequestId($paymentRequestId)
                ->findOne();

            if (!$paymentTransaction) {
                return $this->pageNotFound();
            }

            $paymentResponse = $paymentTransactionService->refreshTransaction($paymentRequestId);

            if (isset($paymentResponse->statusReason)) {
                $paymentTransaction->setStatusReason($paymentResponse->statusReason);
            }

            $paymentTransaction
                ->setUuid($paymentResponse->transactions[0]['id'])
                ->setStatus($paymentResponse->status)
                ->setAmount($paymentResponse->transactions[0]['amount'])
                ->save();

            return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/order/update/' . $paymentTransaction->getOrderId()));

        } catch (Exception|GuzzleException $ex) {
            return new JsonResponse(["error" => $ex->getMessage()], 400);
        }
    }
}
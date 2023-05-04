<?php

namespace BridgePayment\Controller\Back;

use BridgePayment\Model\BridgePaymentTransactionQuery;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Tools\URL;

/**
 * route : "/admin/module/BridgePayment/payment"
 * name : "bridgepayment_payment")
 */
class PaymentController extends BaseAdminController
{
    /**
     * route : "/refresh/{paymentRequestId}"
     * name : "_refresh", methods="GET")
     * @return Response|JsonResponse|RedirectResponse
     */
    public function refreshTransaction(string $paymentRequestId)
    {
        try {
            if(empty($paymentRequestId)){
                $paymentRequestId = $this->getRequest()->get('paymentRequestId');
            }

            $paymentTransactionService = $this->getContainer()->get('bridgepayment.payment.transaction.service');

            $paymentTransaction = BridgePaymentTransactionQuery::create()
                ->filterByPaymentRequestId($paymentRequestId)
                ->findOne();

            if (!$paymentTransaction) {
                return $this->pageNotFound();
            }

            $paymentResponse = $paymentTransactionService->refreshTransaction($paymentRequestId);

            if(isset($paymentResponse->statusReason)){
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
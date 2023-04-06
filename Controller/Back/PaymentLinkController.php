<?php

namespace BridgePayment\Controller\Back;

use BridgePayment\Model\BridgePaymentLinkQuery;
use BridgePayment\Service\PaymentLink;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Tools\URL;

/**
 * @Route("/admin/module/BridgePayment/paymentlink", name="bridgepayment_paymentlink")
 */
class PaymentLinkController extends BaseAdminController
{
    /**
     * @Route("/revoke/{paymentLinkUuid}", name="_view", methods="GET")
     */
    public function revokeLink(PaymentLink $paymentLinkservice, string $paymentLinkUuid): JsonResponse
    {
        try {
            $paymentLinkservice->revokeLink($paymentLinkUuid);
            return new JsonResponse([]);
        } catch (Exception $ex) {
            return new JsonResponse(["error" => $ex->getMessage()], 400);
        }
    }

    /**
     * @Route("/refresh/{paymentLinkUuid}", name="_view", methods="GET")
     */
    public function refreshLink(PaymentLink $paymentLinkservice, string $paymentLinkUuid)
    {
        try {
            $paymentLink = BridgePaymentLinkQuery::create()
                ->filterByUuid($paymentLinkUuid)
                ->findOne();

            if (!$paymentLink) {
                return $this->pageNotFound();
            }

            $paymentLinkResponse = $paymentLinkservice->refreshLink($paymentLinkUuid);

            $expireAt = new \DateTime($paymentLinkResponse->expired_at);

            $paymentLink->setStatus($paymentLinkResponse->status)
                ->setExpiredAt($expireAt)
                ->save();

            return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/order/update/' . $paymentLink->getOrderId()));
        } catch (Exception $ex) {
            return new JsonResponse(["error" => $ex->getMessage()], 400);
        }
    }
}
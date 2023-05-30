<?php

namespace BridgePayment\Controller\Back;

use BridgePayment\Model\BridgePaymentLinkQuery;
use BridgePayment\Service\PaymentLink;
use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Tools\URL;

/**
 * route : "/admin/module/BridgePayment/paymentlink"
 * name : "bridgepayment_paymentlink")
 */
class PaymentLinkController extends BaseAdminController
{
    /**
     * route : "/revoke/{paymentLinkUuid}"
     * name : "_revoke", methods="GET")
     */
    public function revokeLink(string $paymentLinkUuid)
    {
        try {
            $paymentLinkservice = $this->getContainer()->get('bridgepayment.payment.link.service');

            if ($paymentLinkservice->revokeLink($paymentLinkUuid)) {
                $this->refreshLink($paymentLinkUuid);
            }

            $paymentLink = BridgePaymentLinkQuery::create()
                ->filterByUuid($paymentLinkUuid)
                ->findOne();

            return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/order/update/' . $paymentLink->getOrderId()));
        } catch (Exception|GuzzleException $ex) {
            return new JsonResponse(["error" => $ex->getMessage()], 400);
        }
    }

    /**
     * route : "/refresh/{paymentLinkUuid}"
     * name : "_refresh", methods="GET")
     * @return JsonResponse|RedirectResponse
     */
    public function refreshLink(string $paymentLinkUuid)
    {
        try {
            /** @var PaymentLink $paymentLinkservice */
            $paymentLinkservice = $this->getContainer()->get('bridgepayment.payment.link.service');

            $paymentLink = BridgePaymentLinkQuery::create()
                ->filterByUuid($paymentLinkUuid)
                ->findOne();

            if (!$paymentLink) {
                throw new Exception("Page not found");
            }

            $paymentLinkResponse = $paymentLinkservice->refreshLink($paymentLinkUuid);

            $expireAt = new DateTime($paymentLinkResponse->expiredAt);

            $paymentLink
                ->setStatus($paymentLinkResponse->status)
                ->setExpiredAt($expireAt)
                ->save();

            return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/order/update/' . $paymentLink->getOrderId()));
        } catch (Exception|GuzzleException $ex) {
            return new JsonResponse(["error" => $ex->getMessage()], 400);
        }
    }
}
<?php

namespace BridgePayment\Controller\Back;

use BridgePayment\Model\BridgePaymentLinkQuery;
use BridgePayment\Service\PaymentLink;
use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Tools\URL;

#[Route('/admin/module/BridgePayment/paymentlink', name: "bridgepayment_paymentlink")]
class PaymentLinkController extends BaseAdminController
{
    #[Route('/revoke/{paymentLinkUuid}',
        name: "_revoke",
        requirements: ['paymentLinkUuid' => Requirement::UUID],
        methods: "GET"
    )]
    public function revokeLink(
        PaymentLink $paymentLinkService,
        string $paymentLinkUuid
    ): RedirectResponse|JsonResponse
    {
        try {
            if ($paymentLinkService->revokeLink($paymentLinkUuid)) {
                $this->refreshLink($paymentLinkService, $paymentLinkUuid);
            }

            $paymentLink = BridgePaymentLinkQuery::create()
                ->filterByUuid($paymentLinkUuid)
                ->findOne();

            return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/order/update/' . $paymentLink->getOrderId()));

        } catch (Exception|GuzzleException $ex) {
            return new JsonResponse(["error" => $ex->getMessage()], 400);
        }
    }

    #[Route('/refresh/{paymentLinkUuid}',
        name: "_refresh",
        requirements: ['paymentLinkUuid' => Requirement::UUID],
        methods: "GET"
    )]
    public function refreshLink(PaymentLink $paymentLinkService, string $paymentLinkUuid): JsonResponse|RedirectResponse
    {
        try {
            $paymentLink = BridgePaymentLinkQuery::create()
                ->filterByUuid($paymentLinkUuid)
                ->findOne();

            if (!$paymentLink) {
                throw new Exception("Page not found");
            }

            $paymentLinkResponse = $paymentLinkService->refreshLink($paymentLinkUuid);

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
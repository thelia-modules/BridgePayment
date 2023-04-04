<?php

namespace BridgePayment\Controller\Front;

use BridgePayment\BridgePayment;
use BridgePayment\Model\Notification\Notification;
use BridgePayment\Service\PaymentLink;
use BridgePayment\Service\PaymentTransaction;
use BridgePayment\Service\WebHook;
use Exception;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;

/**
 * @Route("/bridge/notification", name="bridgepayment_notification")
 */
class WebHookController extends BaseFrontController
{
    /**
     * @Route("", name="", methods="POST")
     */
    public function notification(
        Request             $request,
        SerializerInterface $serializer,
        PaymentLink         $paymentLinkService,
        PaymentTransaction  $paymentTransaction
    ): Response
    {
        try {
            if (!$webhookSecret = BridgePayment::getConfigValue('hook_secret')) {
                Tlog::getInstance()->addError('Bridge payment configuration missing.');
                throw new Exception(
                    Translator::getInstance()->trans('Bridge payment error.', [], BridgePayment::DOMAIN_NAME)
                );
            }

            $this->checkSignature($request, $webhookSecret);

            /** @var Notification $notification */
            $notification = $serializer->deserialize(
                $request->getContent(),
                Notification::class,
                'json'
            );

            switch ($notification->type) {
                case "payment.link.updated":
                    $paymentLinkService->paymentLinkUpdate($notification->content);
                    break;
                case "payment.transaction.updated":
                case "payment.transaction.created":
                    $paymentTransaction->savePaymentTransaction($notification->content);
                    break;
                default :
                    break;
            }

            return new Response('OK');
        } catch (Exception $ex) {
            Tlog::getInstance()->addError($ex->getMessage());
        }

        return new Response('KO', 400);
    }

    /**
     * @throws Exception
     */
    private function checkSignature(Request $request, string $webhookSecret): void
    {
        //TODO : refacto this.
        $signatures = $request->headers->get('bridgeapi-signature');

        if ($signatures === null) {
            throw new Exception('No signatures found');
        }

        $signatures = explode(',', $signatures);
        $hookSignature = hash_hmac('SHA256', (string)$request->getContent(), $webhookSecret);

        $valid = false;

        foreach ($signatures as $signature) {
            $signature = substr($signature, 3);

            if (strtoupper($hookSignature) === $signature) {
                $valid = true;
                break;
            }
        }

        if ($valid === false) {
            throw new Exception('No valid signature found');
        }
    }
}
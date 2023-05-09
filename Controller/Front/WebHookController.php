<?php

namespace BridgePayment\Controller\Front;

use BridgePayment\BridgePayment;
use BridgePayment\Model\Notification\Notification;
use BridgePayment\Model\Notification\NotificationContent;
use BridgePayment\Service\PaymentLink;
use BridgePayment\Service\PaymentTransaction;
use Exception;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;

/**
 * route : "/bridge/notification"
 * name : "bridgepayment_notification")
 */
class WebHookController extends BaseFrontController
{
    /**
     * route : ""
     * name : ""
     * methods : "POST")
     */
    public function notification(): Response
    {
        try {

            $request = $this->getRequest();
            $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
            $encoder = new JsonEncoder();
            $serializer = new Serializer([$normalizer], [$encoder]);

            /** @var PaymentLink $paymentLinkService */
            $paymentLinkService = $this->getContainer()->get('bridgepayment.payment.link.service');

            /** @var PaymentTransaction $paymentTransaction */
            $paymentTransaction = $this->getContainer()->get('bridgepayment.payment.transaction.service');

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

            $notificationContent = $serializer->deserialize(
                json_encode($notification->content),
                NotificationContent::class,
                'json'
            );

            switch ($notification->type) {
                case "payment.link.updated":
                    $paymentLinkService->paymentLinkUpdate($notificationContent);
                    break;
                case "payment.transaction.updated":
                case "payment.transaction.created":
                    $paymentTransaction->savePaymentTransaction($notificationContent, $notification->timestamp);
                    break;
                default :
                    break;
            }

            return new Response('OK', 200);
        } catch (Exception $ex) {
            Tlog::getInstance()->addError($ex->getMessage());
            return new Response($ex->getMessage(), 400);
        }


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
<?php

namespace BridgePayment\Controller\Front;

use BridgePayment\BridgePayment;
use BridgePayment\Model\Notification\Notification;
use BridgePayment\Model\Notification\NotificationContent;
use BridgePayment\Service\PaymentLink;
use BridgePayment\Service\PaymentTransaction;
use Exception;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;

class WebHookController extends BaseFrontController
{
    #[Route('/bridge/notification',
        name: "bridgepayment_notification",
        methods: "POST"
    )]
    public function notification(
        Request            $request,
        PaymentLink        $paymentLinkService,
        PaymentTransaction $paymentTransaction
    ): Response
    {
        try {
            $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
            $encoder = new JsonEncoder();
            $serializer = new Serializer([$normalizer], [$encoder]);

            $webhookSecret = BridgePayment::getConfigValue('prod_hook_secret');
            if ('TEST' === BridgePayment::getConfigValue('run_mode', 'TEST')) {
                $webhookSecret = BridgePayment::getConfigValue('hook_secret');
            }

            if (!$webhookSecret) {
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
            return new Response("KO", 400);
        }
    }

    /**
     * @throws Exception
     */
    private function checkSignature(Request $request, string $webhookSecret): void
    {
        $signatures = $request->headers->get('bridgeapi-signature');

        if ($signatures === null) {
            throw new Exception('No valid signature found');
        }

        $signatures = explode(',', $signatures);
        $hookSignature = hash_hmac('SHA256', (string)$request->getContent(), $webhookSecret);

        foreach ($signatures as $signature) {
            $signature = substr($signature, 3);

            if (strtoupper($hookSignature) === $signature) {
                return;
            }
        }

        throw new Exception('No valid signature found');
    }
}
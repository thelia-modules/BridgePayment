<?php

namespace BridgePayment\EventListeners;

use BridgePayment\Service\PaymentLink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;

class BridgeOrderStatusEventListener implements EventSubscriberInterface
{
    public function __construct(private PaymentLink $paymentLinkService,)
    {
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function updateLink(OrderEvent $event): void
    {
        if ($event->getOrder()->isCancelled()) {
            $links = $event->getOrder()->getBridgePaymentLinks();
            foreach ($links as $link) {
                $this->paymentLinkService->revokeLink($link->getUuid());
                $paymentLinkResponse = $this->paymentLinkService->refreshLink($link->getUuid());

                $link->setStatus($paymentLinkResponse->status)->save();
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::ORDER_UPDATE_STATUS => ['updateLink', 100]
        ];
    }
}
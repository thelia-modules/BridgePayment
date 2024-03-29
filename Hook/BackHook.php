<?php

namespace BridgePayment\Hook;

use BridgePayment\BridgePayment;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class BackHook extends BaseHook
{
    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $event->add($this->render("bridge-module-configuration.html"));
    }

    public function onPaymentModuleBottom(HookRenderEvent $event): void
    {
        $arguments = $event->getArguments();
        if (BridgePayment::getModuleId() === $arguments['module_id'] ?? null) {
            $event->add($this->render("payment-module-bottom.html"));
        }
    }
}
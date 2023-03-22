<?php

namespace BridgePayment\Hook;

use BridgePayment\BridgePayment;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class BackHook extends BaseHook
{
    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                'type' => 'back',
                'method' => 'onModuleConfiguration'
            ],
            'order-edit.payment-module-bottom' => [
                'type' => 'back',
                'method' => 'onPaymentModuleBottom'
            ]
        ];
    }

    public function onModuleConfiguration(HookRenderEvent $event)
    {
        $event->add($this->render("module-configuration.html"));
    }

    public function onPaymentModuleBottom(HookRenderEvent $event)
    {
        $arguments = $event->getArguments();
        if (BridgePayment::getModuleId() == $arguments['module_id'] ?? null) {
            $event->add($this->render("payment-module-bottom.html"));
        }
    }
}
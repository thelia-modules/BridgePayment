<?php

namespace BridgePayment\EventListeners;

use BridgePayment\BridgePayment;
use BridgePayment\Service\Configuration;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'module.config' => [
                'onModuleConfig', 128
            ],
        ];
    }

    public function onModuleConfig(GenericEvent $event): void
    {
        $subject = $event->getSubject();

        if ($subject !== "HealthStatus") {
            throw new \RuntimeException('Event subject does not match expected value');
        }

        $moduleConfig = [];
        $moduleConfig['module'] = BridgePayment::getModuleCode();
        $configsCompleted = true;

        $configModule = new Configuration();
        try {
            $configModule->checkConfiguration();
        } catch (\Exception $e) {
            $configsCompleted = false;
        }

        $moduleConfig['completed'] = $configsCompleted;

        $event->setArgument('bridge_payment.module.config', $moduleConfig);


    }
}

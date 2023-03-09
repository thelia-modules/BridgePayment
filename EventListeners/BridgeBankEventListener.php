<?php

namespace BridgePayment\EventListeners;

use BridgePayment\Event\BridgeBankEvent;
use BridgePayment\Service\BridgeApiService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BridgeBankEventListener implements EventSubscriberInterface
{
    protected $bridgeApiService;

    public function __construct(BridgeApiService $bridgeApiService)
    {
        $this->bridgeApiService = $bridgeApiService;
    }

    public function getBanks(BridgeBankEvent $bridgeBankEvent)
    {
        $result = $this->bridgeApiService->getBanks($bridgeBankEvent->getCountry()->getIsoalpha2());

        if (array_key_exists('error', $result)) {
            $bridgeBankEvent->setError($result['error']);
        }

        if (array_key_exists('banks', $result)) {
            $bridgeBankEvent->setBanks($result['banks']);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            BridgeBankEvent::GET_BANKS_EVENT => ['getBanks', 128]
        ];
    }

}
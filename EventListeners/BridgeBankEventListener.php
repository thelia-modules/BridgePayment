<?php

namespace BridgePayment\EventListeners;

use BridgePayment\Event\BridgeBankEvent;
use BridgePayment\Service\BankService;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BridgeBankEventListener implements EventSubscriberInterface
{
    /** @var BridgeBankEvent */
    protected $bankService;

    public function __construct(
        BankService $bankService
    )
    {
        $this->bankService = $bankService;
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function getBanks(BridgeBankEvent $bridgeBankEvent) : void
    {
        if (!$bridgeBankEvent->getCountry()) {
            return;
        }

        $result = $this->bankService->getBanks($bridgeBankEvent->getCountry()->getIsoalpha2());

        if (array_key_exists('error', $result)) {
            $bridgeBankEvent->setError($result['error']);
        }

        if ($result && count($result) >= 1) {
            $bridgeBankEvent->setBanks($result);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BridgeBankEvent::GET_BANKS_EVENT => ['getBanks', 128]
        ];
    }
}
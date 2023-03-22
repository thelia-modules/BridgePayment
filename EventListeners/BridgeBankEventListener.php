<?php

namespace BridgePayment\EventListeners;

use BridgePayment\Event\BridgeBankEvent;
use BridgePayment\Service\BridgeApiService;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class BridgeBankEventListener implements EventSubscriberInterface
{
    public function __construct(
        protected BridgeApiService $bridgeApiService
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[T]
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

    #[ArrayShape([BridgeBankEvent::GET_BANKS_EVENT => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [
            BridgeBankEvent::GET_BANKS_EVENT => ['getBanks', 128]
        ];
    }
}
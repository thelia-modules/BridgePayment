<?php

namespace BridgePayment\Hook;

use BridgePayment\BridgePayment;
use BridgePayment\Form\BridgePaymentConfiguration;
use BridgePayment\Model\BridgePaymentLink;
use BridgePayment\Model\BridgePaymentLinkQuery;
use BridgePayment\Model\BridgePaymentTransaction;
use BridgePayment\Model\BridgePaymentTransactionQuery;
use BridgePayment\Service\PaymentLink;
use BridgePayment\Service\PaymentTransaction;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\ParserResolver;
use Thelia\Core\Translation\Translator;

class BackHook extends BaseHook
{
    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfiguration'],
            ],
            'order-edit.payment-module-bottom' => [
                ['type' => 'back', 'method' => 'onPaymentModuleBottom'],
            ],
        ];
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $form = $this->formFactory->createForm(BridgePaymentConfiguration::getName());
        $form->createView();

        $event->add($this->render('bridge-module-configuration.html.twig', [
            'form' => $form->getView(),
        ]));
    }

    public function onPaymentModuleBottom(HookRenderEvent $event): void
    {
        $arguments = $event->getArguments();
        if (BridgePayment::getModuleId() !== ($arguments['module_id'] ?? null)) {
            return;
        }

        $orderId = (int) ($arguments['order_id'] ?? 0);

        $paymentLinks = BridgePaymentLinkQuery::create()
            ->useOrderQuery()
            ->filterById($orderId)
            ->endUse()
            ->find();

        $paymentLinksData = [];
        $linkTransactionsMap = [];

        /** @var BridgePaymentLink $paymentLink */
        foreach ($paymentLinks as $paymentLink) {
            $rawStatus = $paymentLink->getStatus();
            $statusColor = PaymentLink::PAYMENT_LINK_STATUS[$rawStatus]['color'] ?? '#888888';
            $status = Translator::getInstance()->trans($rawStatus ?? '', [], BridgePayment::DOMAIN_NAME);

            $paymentLinksData[] = [
                'id'         => $paymentLink->getId(),
                'uuid'       => $paymentLink->getUuid(),
                'link'       => (!in_array($rawStatus, ['COMPLETED', 'EXPIRED'], true)) ? $paymentLink->getLink() : null,
                'amount'     => $paymentLink->getAmount(),
                'expiredAt'  => $paymentLink->getExpiredAt()?->format('d/m/Y H:i:s'),
                'updatedAt'  => $paymentLink->getUpdatedAt()?->format('d/m/Y H:i:s'),
                'status'     => $status,
                'statusColor' => $statusColor,
                'rawStatus'  => $rawStatus,
            ];

            $linkTransactions = BridgePaymentTransactionQuery::create()
                ->filterByPaymentLinkId($paymentLink->getUuid())
                ->find();

            $linkTransactionsMap[$paymentLink->getUuid()] = $this->buildTransactionData($linkTransactions);
        }

        // If no payment links, fetch standalone transactions
        $transactionsData = [];
        if (empty($paymentLinksData)) {
            $transactions = BridgePaymentTransactionQuery::create()
                ->useOrderQuery()
                ->filterById($orderId)
                ->endUse()
                ->find();

            $transactionsData = $this->buildTransactionData($transactions);
        }

        $event->add($this->render('payment-module-bottom.html.twig', [
            'payment_links'        => $paymentLinksData,
            'link_transactions_map' => $linkTransactionsMap,
            'transactions'         => $transactionsData,
        ]));
    }

    /**
     * @param iterable<BridgePaymentTransaction> $transactions
     * @return array<int, array<string, mixed>>
     */
    private function buildTransactionData(iterable $transactions): array
    {
        $data = [];

        foreach ($transactions as $transaction) {
            $rawStatus = $transaction->getStatus();
            $statusColor = PaymentTransaction::PAYMENT_TRANSACTION_STATUS[$rawStatus] ?? '#888888';
            $status = Translator::getInstance()->trans($rawStatus ?? '', [], BridgePayment::DOMAIN_NAME);

            $rawStatusReason = $transaction->getStatusReason();
            $statusReason = $rawStatusReason
                ? Translator::getInstance()->trans($rawStatusReason, [], BridgePayment::DOMAIN_NAME)
                : '';

            $data[] = [
                'uuid'             => $transaction->getUuid(),
                'amount'           => $transaction->getAmount(),
                'orderTotal'       => null,
                'createdAt'        => $transaction->getCreatedAt()?->format('d/m/Y H:i:s'),
                'status'           => $status,
                'statusColor'      => $statusColor,
                'statusReason'     => $statusReason,
                'paymentRequestId' => $transaction->getPaymentRequestId(),
            ];
        }

        return $data;
    }
}

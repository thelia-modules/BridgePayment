<?php

namespace BridgePayment\Loop;

use BridgePayment\BridgePayment;
use BridgePayment\Model\BridgePaymentTransaction;
use BridgePayment\Model\BridgePaymentTransactionQuery;
use BridgePayment\Service\PaymentTransaction;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Translation\Translator;

class PaymentTransactionLoop extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('order_id'),
            Argument::createAlphaNumStringTypeArgument('payment_link_id'),
        );
    }

    public function buildModelCriteria()
    {
        $query = BridgePaymentTransactionQuery::create()
            ->useOrderQuery()
            ->filterById($this->getOrderId())
            ->endUse();

        if ($linkId = $this->getPaymentLinkId()) {
            $query->filterByPaymentLinkId($linkId);
        }

        return $query;
    }

    public function parseResults(LoopResult $loopResult)
    {
        /** @var BridgePaymentTransaction $paymentTransaction */
        foreach ($loopResult->getResultDataCollection() as $paymentTransaction) {

            $loopResultRow = new LoopResultRow($paymentTransaction);

            $statusColor = PaymentTransaction::PAYMENT_TRANSACTION_STATUS[$paymentTransaction->getStatus()];

            $loopResultRow
                ->set('PAYMENT_TRANSACTION_ID', $paymentTransaction->getId())
                ->set('PAYMENT_TRANSACTION_UUID', $paymentTransaction->getUuid())
                ->set('PAYMENT_TRANSACTION_ORDER_ID', $paymentTransaction->getOrderId())
                ->set('PAYMENT_TRANSACTION_STATUS_COLOR', $statusColor)
                ->set('PAYMENT_TRANSACTION_AMOUNT', $paymentTransaction->getAmount())
                ->set('PAYMENT_TRANSACTION_PAYMENT_LINK_ID', $paymentTransaction->getPaymentLinkId())
                ->set('PAYMENT_TRANSACTION_PAYMENT_REQUEST_ID', $paymentTransaction->getPaymentRequestId())
                ->set('PAYMENT_TRANSACTION_CREATED_AT', $paymentTransaction->getCreatedAt()->format('d/m/Y H:i:s'))
                ->set('PAYMENT_TRANSACTION_UPDATE_AT', $paymentTransaction->getUpdatedAt()->format('d/m/Y H:i:s'));

            if ($tatusReason = $paymentTransaction->getStatusReason()) {
                $loopResultRow->set('PAYMENT_TRANSACTION_STATUS_REASON', Translator::getInstance()->trans($tatusReason, [], BridgePayment::DOMAIN_NAME));
            }

            if ($status = $paymentTransaction->getStatus()) {
                $loopResultRow->set('PAYMENT_TRANSACTION_STATUS', Translator::getInstance()->trans($status, [], BridgePayment::DOMAIN_NAME));
            }

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
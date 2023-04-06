<?php

namespace BridgePayment\Loop;

use BridgePayment\BridgePayment;
use BridgePayment\Model\Base\BridgePaymentLinkQuery;
use BridgePayment\Model\BridgePaymentLink;
use BridgePayment\Model\Map\BridgePaymentLinkTableMap;
use BridgePayment\Service\PaymentLink;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Translation\Translator;

class PaymentLinkLoop extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('order_id'),
        );
    }

    public function buildModelCriteria()
    {
        $query = BridgePaymentLinkQuery::create()
            ->useOrderQuery()
            ->filterById($this->getOrderId())
            ->endUse();

        return $query;
    }

    public function parseResults(LoopResult $loopResult)
    {
        /** @var BridgePaymentLink $paymentLink */
        foreach ($loopResult->getResultDataCollection() as $paymentLink) {

            $loopResultRow = new LoopResultRow($paymentLink);

            $statusColor = PaymentLink::PAYMENT_LINK_STATUS[$paymentLink->getStatus()]['color'];

            $loopResultRow
                ->set('PAYMENT_LINK_ID', $paymentLink->getId())
                ->set('PAYMENT_LINK_UUID', $paymentLink->getUuid())
                ->set('PAYMENT_LINK_EXPIRED_AT', $paymentLink->getExpiredAt()?->format('d/m/Y H:m:s'))
                ->set('PAYMENT_LINK_CREATED_AT', $paymentLink->getCreatedAt()?->format('d/m/Y H:m:s'))
                ->set('PAYMENT_LINK_UPDATED_AT', $paymentLink->getUpdatedAt()?->format('d/m/Y H:m:s'))
                ->set('PAYMENT_LINK_STATUS_COLOR', $statusColor);

            if (!in_array($paymentLink->getStatus(), ['COMPLETED', 'EXPIRED'])) {
                $loopResultRow->set('PAYMENT_LINK_LINK', $paymentLink->getLink());
            }

            if ($status = $paymentLink->getStatus()) {
                $loopResultRow->set('PAYMENT_LINK_STATUS', Translator::getInstance()->trans($status, [], BridgePayment::DOMAIN_NAME));
            }

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
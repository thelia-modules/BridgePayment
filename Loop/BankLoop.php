<?php

namespace BridgePayment\Loop;

use BridgePayment\Service\BankService;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Base\OrderQuery;

class BankLoop extends BaseLoop implements ArraySearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('order_id'),
            Argument::createAlphaNumStringTypeArgument('search')
        );
    }

    public function buildArray()
    {
        $order = OrderQuery::create()->findPk($this->getOrderId());

        if (!$order) {
            return [];
        }

        /** @var BankService $bankService */
        $bankService = $this->container->get('bridgepayment.bank.service');

        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();

        return $bankService->getBanks($invoiceAddress->getCountry()->getIsoalpha2());
    }

    public function parseResults(LoopResult $loopResult)
    {
        $search = $this->getSearch();
        foreach ($loopResult->getResultDataCollection() as $bank) {
            if ($search && false === strpos(strtolower($bank['name']), strtolower($search))) {
                continue;
            }

            $loopResultRow = new LoopResultRow($bank);

            $loopResultRow
                ->set('BANK_ID', $bank['id'])
                ->set('BANK_NAME', $bank['name'])
                ->set('BANK_LOGO', $bank['logo_url']);

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
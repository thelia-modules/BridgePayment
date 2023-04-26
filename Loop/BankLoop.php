<?php

namespace BridgePayment\Loop;

use BridgePayment\Service\BankService;
use GuzzleHttp\Exception\GuzzleException;
use Propel\Runtime\Exception\PropelException;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Base\OrderQuery;

/**
 * @method getOrderId()
 * @method getSearch()
 */
class BankLoop extends BaseLoop implements ArraySearchLoopInterface
{
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('order_id'),
            Argument::createAlphaNumStringTypeArgument('search')
        );
    }

    /**
     * @throws GuzzleException
     * @throws PropelException
     */
    public function buildArray(): array
    {
        $order = OrderQuery::create()->findPk($this->getOrderId());

        if (!$order) {
            return [];
        }

        /** @var BankService $bankService */
        $bankService = $this->container->get('bridgepayment.bank.service');

        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();

        $allBanksInCountry = $bankService->getBanks($invoiceAddress->getCountry()->getIsoalpha2());
        $banks = [];
        foreach ($allBanksInCountry as $bank){
            if(null === $bank['parent_name']){
                $banks[$bank['name']][] = $bank;
            } else {
                $banks[$bank['parent_name']][] = $bank;
            }
        }
        return $banks;
    }

    public function parseResults(LoopResult $loopResult): LoopResult
    {
        $search = $this->getSearch();
        foreach ($loopResult->getResultDataCollection() as $bank) {
            if ($search && false === stripos($bank['name'], $search)) {
                continue;
            }

            $loopResultRow = new LoopResultRow($bank);

            $loopResultRow
                ->set('BANK_NAME', (count($bank) > 1) ? $bank[0]['parent_name'] : $bank[0]['name'] )
                ->set('BANK_ID', (count($bank) === 1) ? $bank[0]['id'] : null)
                ->set('BANK_LOGO', $bank[0]['logo_url']);

            $loopResult->addRow($loopResultRow);
        }
        die();
        return $loopResult;
    }
}
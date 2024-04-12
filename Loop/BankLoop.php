<?php

namespace BridgePayment\Loop;

use BridgePayment\Service\BankService;
use GuzzleHttp\Exception\GuzzleException;
use Propel\Runtime\Exception\PropelException;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Base\OrderQuery;
use Thelia\Model\Customer;

/**
 * @method getSearch()
 */
class BankLoop extends BaseLoop implements ArraySearchLoopInterface
{
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createAlphaNumStringTypeArgument('search')
        );
    }

    /**
     * @throws GuzzleException
     * @throws PropelException
     */
    public function buildArray(): array
    {
        /** @var Session $session */
        $session = $this->requestStack->getCurrentRequest()->getSession();

        /** @var Customer $sessionCustomer */
        if (!$sessionCustomer = $session?->getCustomerUser()) {
            return [];
        }

        $country = $sessionCustomer->getDefaultAddress()->getCountry();

        /** @var BankService $bankService */
        $bankService = $this->container->get('bridgepayment.bank.service');
        try {
            return $bankService->getBanks($country->getIsoalpha2());
        } catch (\Exception $ex) {
            return [];
        }

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
                ->set('BANK_ID', $bank['id'])
                ->set('BANK_NAME', $bank['name'])
                ->set('BANK_LOGO', $bank['logo_url'])
                ->set('BANK_PARENT', $bank['parent_name']??'');

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
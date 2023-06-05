<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use LogicException;
use Thelia\Core\Translation\Translator;

class Configuration
{
    public function checkConfiguration(): void
    {
        if (!$runMode = BridgePayment::getConfigValue("run_mode")) {
            throw new LogicException(Translator::getInstance()->trans("Configuration missing.", [], BridgePayment::DOMAIN_NAME));
        }

        if (
            !BridgePayment::getConfigValue(($runMode !== 'TEST' ? "prod_" : "") . "client_id")
            ||
            !BridgePayment::getConfigValue(($runMode !== 'TEST' ? "prod_" : "") . "client_secret")
            ||
            !BridgePayment::getConfigValue(($runMode !== 'TEST' ? "prod_" : "") . "hook_secret")
        ) {
            $errorMessage = ($runMode !== 'TEST' ? "Your Production Configuration is not complete." : "Your Sandbox Configuration is not complete.");

            throw new LogicException(Translator::getInstance()->trans($errorMessage, [], BridgePayment::DOMAIN_NAME));
        }
    }
}
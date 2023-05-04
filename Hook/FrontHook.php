<?php

namespace BridgePayment\Hook;

use BridgePayment\BridgePayment;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class FrontHook extends BaseHook
{
    public function injectCSS(HookRenderEvent $event)
    {
        $event->add($this->addCSS('assets/css/bankList.css'));
    }
}
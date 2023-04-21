<?php

namespace BridgePayment\Response;

class PaymentLinkResponse
{
    /** @var string  */
    public $id;
    /** @var string  */
    public $url;
    /** @var string  */
    public $status;
    /** @var string  */
    public $expiredAt;
    /** @var string  */
    public $expired_at;
}
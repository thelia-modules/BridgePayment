<?php

namespace BridgePayment\Response;

class PaymentLinkErrorResponse
{
    /** @var string $type*/
    public $type;
    /** @var string */
    public $message;
    /** @var string */
    public $documentationUrl;
    /** @var array */
    public $errors;
}
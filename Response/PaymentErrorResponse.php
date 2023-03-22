<?php

namespace BridgePayment\Response;

class PaymentErrorResponse
{
    /** @var string */
    public $code;
    /** @var string */
    public $property;
    /** @var string */
    public $message;
    /** @var array */
    public $errors;
}
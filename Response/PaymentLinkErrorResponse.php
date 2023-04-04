<?php

namespace BridgePayment\Response;

class PaymentLinkErrorResponse
{
    public string $type;
    public string $message;
    public string $documentationUrl;
    public array $errors;
}
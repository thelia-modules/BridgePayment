<?php

namespace BridgePayment\Exception;

use BridgePayment\Response\PaymentLinkErrorResponse;

class BridgePaymentLinkException extends \Exception
{
    protected PaymentLinkErrorResponse $paymentLinkErrorResponse;

    public function __construct(PaymentLinkErrorResponse $errorResponse)
    {
        $this->paymentLinkErrorResponse = $errorResponse;
        parent::__construct("Payment link can't be created.");
    }

    public function getFormatedErrorMessage(): string
    {
        if ($errors = $this->paymentLinkErrorResponse->errors) {
            $errorMessage = '';

            foreach ($errors as $error) {
                $errorMessage .= $error['message'] . ' ';
            }

            return sprintf("Payment link can't be created : %s", $errorMessage);
        }

        return sprintf("Payment link can't be created : %s", $this->paymentLinkErrorResponse->message);
    }
}
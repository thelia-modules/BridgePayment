<?php

namespace BridgePayment\Exception;

use BridgePayment\Response\PaymentErrorResponse;
use Exception;

class BridgePaymentException extends Exception
{
    /** @var PaymentErrorResponse */
    protected $paymentErrorResponse;

    public function __construct(PaymentErrorResponse $errorResponse)
    {
        $this->paymentErrorResponse = $errorResponse;
        parent::__construct("Payment can't be initiated.");
    }

    public function getFormatedErrorMessage(): string
    {
        if ($errors = $this->paymentErrorResponse->errors) {
            $errorMessage = '';

            foreach ($errors as $error) {
                $errorMessage .= $error['message'] . ' ';
            }

            return sprintf("Payment can't be initiated : %s", $errorMessage);
        }

        return sprintf("Payment can't be initiated : %s", $this->paymentErrorResponse->message);
    }
}
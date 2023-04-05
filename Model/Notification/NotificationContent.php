<?php

namespace BridgePayment\Model\Notification;

class NotificationContent
{
    public string $paymentTransactionId;
    public ?string $paymentLinkId = null;
    public string $paymentRequestId;
    public string $endToEndId;
    public string $clientReference;
    public ?string $status = null;
    public ?string $statusReason = null;

    private ?string $paymentLinkStatus;
    private ?string $paymentLinkClientReference;

    public function setPaymentLinkClientReference(?string $paymentLinkClientReference): NotificationContent
    {
        $this->clientReference = $paymentLinkClientReference;

        return $this;
    }

    public function setPaymentLinkStatus(?string $paymentLinkStatus): NotificationContent
    {
        $this->status = $paymentLinkStatus;
        return $this;
    }

    public function setPaymentRequestId(string $paymentRequestId): NotificationContent
    {
        $this->paymentRequestId = $paymentRequestId;
        return $this;
    }

    public function setEndToEndId(string $endToEndId): NotificationContent
    {
        $this->endToEndId = $endToEndId;
        return $this;
    }

    public function setStatusReason(?string $statusReason): NotificationContent
    {
        $this->statusReason = $statusReason;
        return $this;
    }

    /**
     * @param string $paymentLinkId
     * @return NotificationContent
     */
    public function setPaymentLinkId(string $paymentLinkId): NotificationContent
    {
        $this->paymentLinkId = $paymentLinkId;
        return $this;
    }

    public function setPaymentTransactionId(string $paymentTransactionId): NotificationContent
    {
        $this->paymentTransactionId = $paymentTransactionId;
        return $this;
    }
}
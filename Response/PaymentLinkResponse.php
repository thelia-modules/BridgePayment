<?php

namespace BridgePayment\Response;

class PaymentLinkResponse
{
    public string $id;
    public string $url;
    public string $status;
    public string $expiredAt;
    public string $expired_at;
}
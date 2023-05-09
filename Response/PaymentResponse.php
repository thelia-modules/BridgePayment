<?php

namespace BridgePayment\Response;

use BridgePayment\Model\Api\User;

class PaymentResponse
{
    /** @var string  */
    public $id;
    /** @var string  */
    public $consentUrl;
    /** @var string  */
    public $status;
    /** @var User */
    public $user;
    /** @var string */
    public $link;
    /** @var string */
    public $clientReference;
    /** @var array */
    public $transactions;
    /** @var string */
    public $endToEndId;
    /** @var ?string */
    public $expiredAt;
    /** @var ?string */
    public $createdAt;
    /** @var ?string */
    public $updatedAt;
}
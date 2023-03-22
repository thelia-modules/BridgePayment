<?php

namespace BridgePayment\Response;

use BridgePayment\Model\Api\User;

class PaymentLinkResponse
{
    /** @var string  */
    public $id;
    /** @var string  */
    public $url;
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
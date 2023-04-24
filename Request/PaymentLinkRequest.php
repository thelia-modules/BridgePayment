<?php

namespace BridgePayment\Request;

use BridgePayment\Model\Api\Transaction;
use BridgePayment\Model\Api\User;
use JsonSerializable;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Thelia\Model\Order;
use Thelia\Tools\URL;

class PaymentLinkRequest implements JsonSerializable
{
    /** @var User */
    public $user;
    /** @var Transaction[] */
    public $transactions;
    /** @var string */
    public $clientReference;
    /** @var string */
    public $callbackUrl;

    /**
     * @throws PropelException
     * @return string => PaymentLinkRequest encoded in json
     */
    public function hydrate(Order $order): string
    {

        $this->user = $this->constructUserPaymentLinkRequest($order);
        $this->transactions = [$this->constructTransactionPaymentLinkRequest($order)];

        $this->callbackUrl = URL::getInstance()->absoluteUrl("/bridge/payment/" . $order->getId());
        $this->clientReference = $order->getCustomer()->getRef();

        return $this->jsonSerialize();
    }

    public function jsonSerialize(): string
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());

        // User ignored attributes
        $ignoredAttributes = [
            'email',
            'uuid',
            'iban'
        ];
        if(!$this->user->getCompanyName()) {
            $ignoredAttributes[] = 'companyName';
        }
        // Transaction ignored attributes
        $ignoredAttributes[] = 'executionDate';
        $ignoredAttributes[] = 'beneficiary';
        $normalizer->setIgnoredAttributes($ignoredAttributes);

        $encoder = new JsonEncoder();
        $serializer = new Serializer([$normalizer], [$encoder]);

        return $serializer->serialize($this, 'json');
    }

    /**
     * @throws PropelException
     */
    protected function constructUserPaymentLinkRequest(Order $order): User
    {
        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();
        $customer = $order->getCustomer();

        $user = new User();
        $user
            ->setFirstName($invoiceAddress->getFirstname())
            ->setLastName($invoiceAddress->getLastname())
            ->setExternalReference($customer->getRef());

        if($invoiceAddress->getCompany()) {
            $user->setCompanyName($invoiceAddress->getCompany());
        }

        return $user;
    }

    /**
     * @throws PropelException
     */
    protected function constructTransactionPaymentLinkRequest(Order $order): Transaction
    {
        $transaction = new Transaction();
        $transaction
            ->setLabel($order->getRef())
            ->setCurrency($order->getCurrency()->getCode())
            ->setAmount(round($order->getTotalAmount(), 2))
            ->setEndToEndId($order->getRef());

        return $transaction;
    }
}
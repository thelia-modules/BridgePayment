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

class PaymentRequest implements JsonSerializable
{
    /** @var string */
    public $successfulCallbackUrl;
    /** @var string */
    public $unsuccessfulCallbackUrl;
    /** @var Transaction[] */
    public $transactions;
    /** @var User */
    public $user;
    /** @var string */
    public $clientReference;
    /** @var int */
    public $bankId;


    /**
     * @throws PropelException
     */
    public function hydrate(Order $order, int $bankId): PaymentRequest
    {
        $this->successfulCallbackUrl = URL::getInstance()->absoluteUrl("/order/placed/" . $order->getId());
        $this->unsuccessfulCallbackUrl = URL::getInstance()->absoluteUrl("/order/failed/" . $order->getId() . "/error");
        $this->transactions = [$this->constructTransactionPaymentRequest($order)];
        $this->user = $this->constructUserPaymentRequest($order);
        $this->clientReference = $order->getCustomer()->getRef();
        $this->bankId = $bankId;

        return $this;
    }

    /**
     * @throws PropelException
     */
    protected function constructUserPaymentRequest(Order $order): User
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
    protected function constructTransactionPaymentRequest(Order $order): Transaction
    {
        $transaction = new Transaction();
        $transaction
            ->setLabel($order->getRef())
            ->setCurrency($order->getCurrency()->getCode())
            ->setAmount(round($order->getTotalAmountLegacy(), 2))
            ->setEndToEndId($order->getRef())
            ->setClientReference($order->getCustomer()->getRef());

        return $transaction;
    }

    public function jsonSerialize(): string
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());

        // User ignored attributes
        $ignoredAttributes = [
            'email',
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
}
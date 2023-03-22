<?php

namespace BridgePayment\Request;

use BridgePayment\Model\Api\Transaction;
use BridgePayment\Model\Api\User;
use DateInterval;
use DateTimeImmutable;
use Exception;
use JsonSerializable;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Thelia\Model\Order;
use Thelia\Tools\URL;

class PaymentLinkRequest implements JsonSerializable
{
    /** @var User */
    public $user;
    /** @var string */
    public $expiredDate;
    /** @var string */
    public $clientReference;
    /** @var Transaction[] */
    public $transactions;
    /** @var string */
    public $callbackUrl;

    /**
     * @throws PropelException|Exception
     */
    public function hydrate(Order $order): PaymentLinkRequest
    {
        $this->user = $this->constructUserPaymentLinkRequest($order);

        $orderCreatedAt = new DateTimeImmutable($order->getCreatedAt()->format('Y-M-d H:i:s'));
        $interval = DateInterval::createFromDateString('30 day');
        $this->expiredDate = $orderCreatedAt->add($interval)->format('Y-m-d\\TH:i:s.O');

        $this->clientReference = $order->getCustomer()->getRef();
        $this->transactions = [$this->constructTransactionPaymentLinkRequest($order)];
        $this->callbackUrl = URL::getInstance()->absoluteUrl("/bridge/payment/" . $order->getId());

        return $this;
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

        if ($invoiceAddress->getCompany()) {
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

    public function jsonSerialize(): string
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());

        // User ignored attributes
        $ignoredAttributes = [
            'email',
            'iban'
        ];
        if (!$this->user->getCompanyName()) {
            $ignoredAttributes[] = 'companyName';
        }
        // Transaction ignored attributes
        $ignoredAttributes[] = 'clientReference';
        $ignoredAttributes[] = 'executionDate';
        $ignoredAttributes[] = 'beneficiary';
        //$normalizer->setIgnoredAttributes($ignoredAttributes);

        $encoder = new JsonEncoder();
        $serializer = new Serializer([$normalizer], [$encoder]);

        return $serializer->serialize($serializer->normalize($this, null, [AbstractNormalizer::IGNORED_ATTRIBUTES => $ignoredAttributes]), "json");
    }
}
<?php

namespace BridgePayment\Request;

use JsonSerializable;
use BridgePayment\BridgePayment;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Order;
use Thelia\Tools\URL;

class PaymentLinkRequest implements JsonSerializable
{
    public array $user;
    public array $transactions;
    public string $clientReference;
    public string $callbackUrl;

    /**
     * @throws PropelException
     */
    public function hydrate(Order $order): PaymentLinkRequest
    {
        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();
        $customer = $order->getCustomer();

        $this->user = [
            'first_name' => $invoiceAddress->getFirstname(),
            'last_name' => $invoiceAddress->getLastname(),
            'external_reference' => $customer->getRef()
        ];

        $this->transactions = [
            [
                'label' => $order->getRef(),
                'currency' => $order->getCurrency()->getCode(),
                'amount' => round($order->getTotalAmount(), 2),
                'end_to_end_id' => $order->getRef(),
                'beneficiary' => [
                    "iban" => rtrim(BridgePayment::getConfigValue('iban')),
                    "company_name" => ConfigQuery::read('store_name')
                ]
            ]
        ];

        $this->callbackUrl = URL::getInstance()->absoluteUrl("/order/placed/" . $order->getId());
        $this->clientReference = $order->getCustomer()->getRef();

        return $this;
    }

    /**
     * @throws ExceptionInterface
     */
    public function jsonSerialize(): mixed
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
        $serializer = new Serializer([$normalizer]);

        return $serializer->normalize($this);
    }
}
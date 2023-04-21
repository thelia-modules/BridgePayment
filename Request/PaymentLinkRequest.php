<?php

namespace BridgePayment\Request;

use Doctrine\Common\Annotations\AnnotationRegistry;
use JsonSerializable;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Thelia\Model\Order;
use Thelia\Tools\URL;

class PaymentLinkRequest
{
    /** @var User  */
    public $user;
    /** @var array  */
    public $transactions;
    /** @var string */
    public $clientReference;
    /** @var string */
    public $callbackUrl;

    /**
     * @throws PropelException
     */
    public function hydrate(Order $order)
    {
        $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();
        $customer = $order->getCustomer();

        $this->user = new User();
        $this->user
            ->setFirstName($invoiceAddress->getFirstname())
            ->setLastName($invoiceAddress->getLastname())
            ->setReference($customer->getRef());
        if(!$this->user->getFirstName() || !$this->user->getLastName()) {
            $this->user->setCompanyName($invoiceAddress->getCompany());
        }

        $transaction = new Transaction();
        $transaction
            ->setLabel($order->getRef())
            ->setCurrency($order->getCurrency()->getCode())
            ->setAmount(round($order->getTotalAmount(), 2))
            ->setEndToEndId($order->getRef());

        $this->transactions = [$transaction];

        $this->callbackUrl = URL::getInstance()->absoluteUrl("/bridge/payment/" . $order->getId());
        $this->clientReference = $order->getCustomer()->getRef();

        var_dump($this->user->jsonSerialize());
//        var_dump($this->jsonSerialize());
        die();
        return json_encode($this->jsonSerialize());
    }

//    /**
//     * @return mixed
//     */
//    public function jsonSerialize()
//    {
//        AnnotationRegistry::registerAutoloadNamespace('BridgePayment\Request');
//        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
//        $serializer = new Serializer([$normalizer]);
//
//        return $serializer->normalize($this);
//    }
}
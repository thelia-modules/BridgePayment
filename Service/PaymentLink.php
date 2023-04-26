<?php

namespace BridgePayment\Service;

use Exception;
use BridgePayment\Model\Notification\NotificationContent;
use BridgePayment\Exception\BridgePaymentLinkException;
use BridgePayment\Model\BridgePaymentLink;
use BridgePayment\Model\BridgePaymentLinkQuery;
use BridgePayment\BridgePayment;
use BridgePayment\Request\PaymentLinkRequest;
use BridgePayment\Response\PaymentLinkErrorResponse;
use BridgePayment\Response\PaymentLinkResponse;
use GuzzleHttp\Exception\GuzzleException;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Order;

class PaymentLink
{
    /** @var array[] */
    public const PAYMENT_LINK_STATUS = [
        'VALID' => [
            'color' => '#bae313',
        ],
        'EXPIRED' => [
            'color' => '#e31313'
        ],
        'REVOKED' => [
            'color' => '#e3138b'
        ],
        'COMPLETED' => [
            'color' => '#13e319'
        ]
    ];
    /** @var BridgeApi */
    protected $apiService;
    /** @var SerializerInterface */
    protected $serializer;

    public function __construct(BridgeApi $apiService)
    {
        $this->apiService = $apiService;
        $this->serializer = new Serializer([new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter())], [new JsonEncoder()]);
    }

    /**
     * @throws Exception|GuzzleException
     */
    public function createPaymentLink(Order $order)
    {
        $paymentLinkRequest = (new PaymentLinkRequest())->hydrate($order);
        $response = $this->apiService->apiCall(
            'POST',
            BridgePayment::BRIDGE_API_URL . '/v2/payment-links',
            $paymentLinkRequest->jsonSerialize()
        );

        if ($response->getStatusCode() >= 400) {
            throw new BridgePaymentLinkException(
                (PaymentLinkErrorResponse::class)($this->serializer->deserialize(
                $response->getReasonPhrase(),
                PaymentLinkErrorResponse::class,
                'json'
            )));
        }

        $paymentLinkResponse = $this->serializer->deserialize(
            $response->getBody()->getContents(),
            PaymentLinkResponse::class,
            'json'
        );

        (new BridgePaymentLink())
            ->setExpiredAt($paymentLinkRequest->expiredDate)
            ->setUuid($paymentLinkResponse->id)
            ->setStatus('VALID')
            ->setLink($paymentLinkResponse->url)
            ->setOrderId($order->getId())
            ->save();

        return $paymentLinkResponse->url;
    }

    /**
     * @throws PropelException|Exception
     */
    public function paymentLinkUpdate(NotificationContent $notification): void
    {
        $paymentLink = BridgePaymentLinkQuery::create()
            ->useOrderQuery()
            ->useCustomerQuery()
            ->filterByRef($notification->clientReference)
            ->endUse()
            ->endUse()
            ->filterByUuid($notification->paymentLinkId)
            ->findOne();

        if (!$paymentLink) {
            throw new Exception(sprintf('Payment link not found on this customer : %s', $notification->clientReference));
        }

        if ($paymentLink->getStatus() === 'VALID') {
            $paymentLink->setStatus($notification->status)->save();
        }
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function revokeLink(string $paymentLinkUuid): PaymentLinkResponse
    {
        $response = $this->apiService->apiCall(
            'POST',
            BridgePayment::BRIDGE_API_URL . "/v2/payment-links/$paymentLinkUuid/revoke",
            []
        );

        if ($response->getStatusCode() >= 400) {
            throw new Exception(
                Translator::getInstance()->trans("Can't revoke link.", [], BridgePayment::DOMAIN_NAME)
            );
        }

        $paymentLinkResponse = $this->serializer->deserialize(
            $response->getBody(),
            PaymentLinkResponse::class,
            'json'
        );

        return (PaymentLinkErrorResponse::class)($paymentLinkResponse);
    }

    /**
     * @throws Exception|GuzzleException
     */
    public function refreshLink(string $paymentLinkUuid) : PaymentLinkResponse
    {
        $response = $this->apiService->apiCall(
            'GET',
            BridgePayment::BRIDGE_API_URL . "/v2/payment-links/$paymentLinkUuid",
            ''
        );

        if ($response->getStatusCode() >= 400) {
            throw new Exception(
                Translator::getInstance()->trans("Can't revoke link.", [], BridgePayment::DOMAIN_NAME)
            );
        }

        return $this->serializer->deserialize(
            $response->getBody()->getContents(),
            PaymentLinkResponse::class,
            'json'
        );
    }
}
<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use BridgePayment\Exception\BridgePaymentException;
use BridgePayment\Model\BridgePaymentTransaction;
use BridgePayment\Request\PaymentRequest;
use BridgePayment\Response\PaymentErrorResponse;
use BridgePayment\Response\PaymentResponse;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Thelia\Model\Order;

class BridgePaymentInitiation
{
    /** @var BridgeApi  */
    protected $apiService;
    /** @var Serializer */
    protected $serializer;

    public function __construct( BridgeApi $apiService )
    {
        $this->apiService = $apiService;
        $this->serializer = new Serializer([new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter())], [new JsonEncoder()]);
    }

    /**
     * @throws PropelException| Exception| GuzzleException
     */
    public function createPaymentRequest(Order $order, int $bankId): string
    {
        $data = (new PaymentRequest())->hydrate($order, $bankId)->jsonSerialize();

        $response = $this->apiService->apiCall(
            'POST',
            BridgePayment::BRIDGE_API_URL . '/v2/payment-requests',
            $data
        );

        if ($response->getStatusCode() >= 400) {
            throw new BridgePaymentException(
                (PaymentErrorResponse::class)($this->serializer->deserialize(
                $response->getReasonPhrase(),
                PaymentErrorResponse::class,
                'json'
            )));
        }

        $paymentResponse = $this->serializer->deserialize(
            $response->getBody()->getContents(),
            PaymentResponse::class,
            'json'
        );

        return $paymentResponse->consentUrl;
    }
}
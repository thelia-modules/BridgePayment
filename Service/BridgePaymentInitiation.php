<?php

namespace BridgePayment\Service;

use BridgePayment\BridgePayment;
use BridgePayment\Exception\BridgePaymentException;
use BridgePayment\Request\PaymentRequest;
use BridgePayment\Response\PaymentErrorResponse;
use BridgePayment\Response\PaymentResponse;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Order;

class BridgePaymentInitiation
{
    /** @var BridgeApi */
    protected $apiService;
    /** @var Serializer */
    protected $serializer;

    public function __construct(BridgeApi $apiService, protected RequestStack $requestStack)
    {
        $this->apiService = $apiService;
        $this->serializer = new Serializer([new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter())], [new JsonEncoder()]);
    }

    /**
     * @throws PropelException| Exception| GuzzleException
     */
    public function createPaymentRequest(Order $order, $bankId = null): string
    {
        if (!$bankId = $bankId ?: $this->getBankIdFromSession()) {
            throw new Exception(Translator::getInstance()->trans("Bank not selected"));
        }

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

    protected function getBankIdFromSession(): int|null
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();
        return (int)$session?->get(BridgePayment::SELECTED_BANK_ID, null);
    }
}
<?php

namespace BridgePayment;

use BridgePayment\Exception\BridgePaymentLinkException;
use BridgePayment\Service\BridgePaymentInitiation;
use BridgePayment\Service\Configuration;
use BridgePayment\Service\PaymentLink;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Core\Translation\Translator;
use Thelia\Install\Database;
use Thelia\Log\Tlog;
use Thelia\Model\Order;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;
use Thelia\Module\AbstractPaymentModule;
use Thelia\Tools\URL;

class BridgePayment extends AbstractPaymentModule
{
    /** @var string */
    public const DOMAIN_NAME = 'bridgepayment';
    /** @var string */
    public const BRIDGE_API_VERSION = '2021-06-01';
    /** @var string */
    public const BRIDGE_API_URL = 'https://api.bridgeapi.io';

    /** @var string  */
    public const SELECTED_BANK_ID = 'selected_bank_id';

    /**
     * @throws PropelException
     */
    public function postActivation(ConnectionInterface $con = null): void
    {
        if (!self::getConfigValue('is_initialized', false)) {
            (new Database($con))->insertSql(null, array(__DIR__ . '/Config/TheliaMain.sql'));

            self::setConfigValue('is_initialized', true);
        }
        $statuses = [
            [
                'code' => 'payment_rejected',
                'color' => '#d9534f',
                'i18n' => [
                    'fr_FR' => [
                        'title' => 'Paiement rejeté',
                        'description' => 'Paiement avec BridgePayment rejeté',
                    ],
                    'en_US' => [
                        'title' => 'Rejected payment',
                        'description' => 'Payment with BridgePayment rejected',
                    ]
                ],
            ],
            [
                'code' => 'payment_pending',
                'color' => '#3a97d4',
                'i18n' => [
                    'fr_FR' => [
                        'title' => 'Paiement en attente',
                        'description' => 'Paiement avec BridgePayment en attente',
                    ],
                    'en_US' => [
                        'title' => 'Pending payment',
                        'description' => 'Payment with BridgePayment pending',
                    ]
                ],
            ],
            [
                'code' => 'payment_created',
                'color' => '#d4773a',
                'i18n' => [
                    'fr_FR' => [
                        'title' => 'Paiement crée',
                        'description' => 'Paiement avec BridgePayment est crée',
                    ],
                    'en_US' => [
                        'title' => 'Created payment',
                        'description' => 'Payment with BridgePayment created',
                    ]
                ],
            ]
        ];

        foreach ($statuses as $status) {
            $newStatus = OrderStatusQuery::create()
                ->filterByCode($status['code'])
                ->findOne();

            if ($newStatus) {
                continue;
            }

            $newStatus = (new OrderStatus())
                ->setCode($status['code'])
                ->setColor($status['color']);

            foreach ($status['i18n'] as $locale => $statusI18n) {
                $newStatus
                    ->setLocale($locale)
                    ->setTitle($statusI18n['title'])
                    ->setDescription($statusI18n['description']);
            }

            $newStatus
                ->setPosition($newStatus->getNextPosition())
                ->save();
        }
    }

    /**
     * @param Order $order
     * @return Response|RedirectResponse
     */
    public function pay(Order $order): Response|RedirectResponse
    {
        try {
            /** @var BridgePaymentInitiation $bridgePaymentInitiation */
            $bridgePaymentInitiation = $this->container->get('bridgepayment.payment.initiation.service');

            if ('LINK' === self::getConfigValue('payment_mode', false)) {
                /** @var PaymentLink $paymentLinkService */
                $paymentLinkService = $this->container->get('bridgepayment.payment.link.service');

                return new RedirectResponse($paymentLinkService->createPaymentLink($order));
            }

            return new RedirectResponse($bridgePaymentInitiation->createPaymentRequest($order));

        } catch (BridgePaymentLinkException $bridgePaymentLinkexception) {
            $errorMessage = $bridgePaymentLinkexception->getFormatedErrorMessage();
            Tlog::getInstance()->error($errorMessage);
        } catch (Exception|GuzzleException $ex) {
            $errorMessage = $ex->getMessage();
            Tlog::getInstance()->error($errorMessage);
        }

        return new RedirectResponse(
            URL::getInstance()->absoluteUrl(
                sprintf("/order/failed/%d/%s", $order->getId(), 'Error'),
                [
                    'error_message' => Translator::getInstance()->trans("Payment process error !", [], BridgePayment::DOMAIN_NAME)
                ]
            )
        );
    }

    public function isValidPayment(): bool
    {
        $valid = true;
        if ('TEST' === self::getConfigValue('run_mode')) {
            $raw_ips = explode("\n", self::getConfigValue('allowed_ip_list', ''));

            $allowed_client_ips = [];

            foreach ($raw_ips as $ip) {
                $allowed_client_ips[] = trim($ip);
            }

            $client_ip = $this->getRequest()->getClientIp();

            $valid = in_array($client_ip, $allowed_client_ips, true) || in_array('*', $allowed_client_ips, true);
        }

        if ($valid) {
            $valid = $this->checkMinMaxAmount('minimum_amount', 'maximum_amount');
        }

        try{
            /** @var Configuration $configurationService */
            $configurationService = $this->container->get('bridgepayment.payment.configuration.service');
            $configurationService->checkConfiguration();

        }catch(\Exception){
            return false;
        }

        return $valid;
    }

    protected function checkMinMaxAmount($min, $max): bool
    {
        $order_total = $this->getCurrentOrderTotalAmount();

        $min_amount = self::getConfigValue($min, 0);
        $max_amount = self::getConfigValue($max, 0);

        return $order_total > 0 && ($min_amount <= 0 || $order_total >= $min_amount) && ($max_amount <= 0 || $order_total <= $max_amount);
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR.ucfirst(self::getModuleCode()).'/I18n/*'])
            ->autowire(true)
            ->autoconfigure(true);
    }
}

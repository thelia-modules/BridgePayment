<?php

namespace BridgePayment;

use BridgePayment\Exception\BridgePaymentLinkException;
use BridgePayment\Service\BankService;
use BridgePayment\Service\BridgeApiService;
use BridgePayment\Service\PaymentLink;
use Exception;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Core\HttpFoundation\Response;
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
    const DOMAIN_NAME = 'bridgepayment';

    const BRIDGE_API_VERSION = '2021-06-01';
    const BRIDGE_API_URL = 'https://api.bridgeapi.io';

    /**
     * @throws PropelException
     */
    public function postActivation(ConnectionInterface $con = null): void
    {
        if (!$this->getConfigValue('is_initialized', false)) {
            (new Database($con))->insertSql(null, array(__DIR__ . '/Config/TheliaMain.sql'));

            $this->setConfigValue('is_initialized', true);
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
     * Defines how services are loaded in your modules
     *
     * @param ServicesConfigurator $servicesConfigurator
     */
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode() . '\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()) . "/I18n/*"])
            ->autowire()
            ->autoconfigure();
    }

    /**
     * @param Order $order
     * @return Response|RedirectResponse
     */
    public function pay(Order $order): Response|RedirectResponse
    {
        try {
            if (BridgePayment::getConfigValue('redirect_mode', false)) {
                /** @var PaymentLink $paymentLinkService */
                $paymentLinkService = $this->container->get('bridgepayment.payment.link.service');

                return new RedirectResponse($paymentLinkService->createPaymentLink($order));
            }

            $parser = $this->getContainer()->get("thelia.parser");

            $parser->setTemplateDefinition(
                $parser->getTemplateHelper()->getActiveFrontTemplate(),
                true
            );

            $renderedTemplate = $parser->render(
                "bank-list.html",
                [
                    "orderId" => $order->getId()
                ]
            );

            return new Response($renderedTemplate);

        } catch (BridgePaymentLinkException $bridgePaymentLinkexception) {
            $errorMessage = $bridgePaymentLinkexception->getFormatedErrorMessage();
        } catch (Exception $ex) {
            $errorMessage = $ex->getMessage();
            Tlog::getInstance()->error($errorMessage);
        }

        return new RedirectResponse(
            URL::getInstance()->absoluteUrl(
                sprintf("/order/failed/%d/%s", $order->getId(), 'Error'),
                [
                    'error_message' => $errorMessage
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

            $valid = in_array($client_ip, $allowed_client_ips) || in_array('*', $allowed_client_ips);
        }

        if ($valid) {
            $valid = $this->checkMinMaxAmount('minimum_amount', 'maximum_amount');
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
}

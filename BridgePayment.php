<?php

namespace BridgePayment;

use BridgePayment\Model\BridgepaymentHistory;
use BridgePayment\Model\BridgepaymentHistoryQuery;
use BridgePayment\Service\BridgeApiService;
use PayzenEmbedded\PayzenEmbedded;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Install\Database;
use Thelia\Model\Order;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;
use Thelia\Module\AbstractPaymentModule;
use Thelia\Module\BaseModule;
use Thelia\Tools\URL;

class BridgePayment extends AbstractPaymentModule
{
    /** @var string */
    const DOMAIN_NAME = 'bridgepayment';

    /*
     * You may now override BaseModuleInterface methods, such as:
     * install, destroy, preActivation, postActivation, preDeactivation, postDeactivation
     *
     * Have fun !
     */

    public function postActivation(ConnectionInterface $con = null)
    {

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
            ]
        ];

        foreach ($statuses as $status) {
            $newStatus = OrderStatusQuery::create()
                ->filterByCode($status['code'])
                ->findOne();

            if (null === $newStatus) {
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

    }

    public function pay(Order $order)
    {
        /** @var BridgeApiService $apiService */
        $apiService = $this->container->get('bridgepayment.api.service');

        if (BridgePayment::getConfigValue('redirect_mode', false)) {
            $link = $apiService->getPaymentLink($order);
        }else {
            $invoiceAddress = $order->getOrderAddressRelatedByInvoiceOrderAddressId();
            $banks = $apiService->getBanks($invoiceAddress->getCountry()->getIsoalpha2());

            $parser = $this->getContainer()->get("thelia.parser");

            $parser->setTemplateDefinition(
                $parser->getTemplateHelper()->getActiveFrontTemplate(),
                true
            );

            $renderedTemplate = $parser->render(
                "bank-list.html",
                array_merge(
                    [
                        "order_id" => $order->getId()
                    ],
                    $banks
                )
            );

            return new Response($renderedTemplate);
        }


        if (array_key_exists('error', $link)){
            $orderId = $order->getId();
            $message = $link['error'];
            return new RedirectResponse(URL::getInstance()->absoluteUrl("/order/failed/$orderId/$message"));
        }

        return new RedirectResponse($link['url']);
    }

    public function isValidPayment()
    {
        $mode = self::getConfigValue('run_mode');
        $valid = true;
        if ($mode === 'TEST') {
            $raw_ips = explode("\n", self::getConfigValue('allowed_ip_list', ''));
            $allowed_client_ips = array();

            foreach ($raw_ips as $ip) {
                $allowed_client_ips[] = trim($ip);
            }

            $client_ip = $this->getRequest()->getClientIp();

            $valid = in_array($client_ip, $allowed_client_ips) || in_array('*', $allowed_client_ips);
        }

        if ($valid) {
            // Check if total order amount is in the module's limits
            $valid = $this->checkMinMaxAmount('minimum_amount', 'maximum_amount');
        }

        return $valid;
    }

    protected function checkMinMaxAmount($min, $max)
    {
        $order_total = $this->getCurrentOrderTotalAmount();

        $min_amount = self::getConfigValue($min, 0);
        $max_amount = self::getConfigValue($max, 0);

        return $order_total > 0 && ($min_amount <= 0 || $order_total >= $min_amount) && ($max_amount <= 0 || $order_total <= $max_amount);
    }
}

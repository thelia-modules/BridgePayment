<?php

namespace BridgePayment\Form;

use BridgePayment\BridgePayment;
use BridgePayment\Event\BridgeBankEvent;
use OpenApi\Constraint\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Thelia\Model\ConfigQuery;
use Thelia\Model\CountryQuery;

class BridgePaymentConfiguration extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'run_mode',
                ChoiceType::class,
                [
                    'constraints' => [new NotBlank()],
                    'required' => true,
                    'choices' => [
                        'TEST' => 'Test',
                        'PRODUCTION' => 'Production',
                    ],
                    'label' => Translator::getInstance()->trans('Mode de fonctionnement', [], BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'run_mode'
                    ],
                    'data' => BridgePayment::getConfigValue('run_mode'),
                ]
            )
            ->add(
                'bank_id',
                ChoiceType::class,
                [
                    'required' => false,
                    'choices' => $this->getBanks(),
                    'label' => Translator::getInstance()->trans('Store bank', [], BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'bank_id'
                    ],
                    'data' => BridgePayment::getConfigValue('bank_id'),
                ]
            )
            ->add(
                'client_id',
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'required' => true,
                    'label' => Translator::getInstance()->trans('Client Id'),
                    'label_attr' => [
                        'for' => 'client_id'
                    ],
                    'data' => BridgePayment::getConfigValue('client_id', ''),
                ]
            )
            ->add(
                'client_secret',
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'required' => true,
                    'label' => Translator::getInstance()->trans('Client secret'),
                    'label_attr' => [
                        'for' => 'client_secret'
                    ],
                    'data' => BridgePayment::getConfigValue('client_secret', ''),
                ]
            )
            ->add(
                'hook_secret',
                TextType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Hook Secret', [], BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'hook_secret'
                    ],
                    'data' => BridgePayment::getConfigValue('hook_secret', ''),
                ]
            )
            ->add(
                'iban',
                TextType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('IBAN', [], BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'iban'
                    ],
                    'data' => BridgePayment::getConfigValue('iban', ''),
                ]
            )
            ->add(
                'redirect_mode',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Redirect payment to Bridge', [], BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'redirect_mode'
                    ],
                    'data' => (bool)BridgePayment::getConfigValue('redirect_mode', false),
                ]
            )
            ->add(
                'allowed_ip_list',
                TextareaType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Allowed IPs in test mode'),
                    'data' => BridgePayment::getConfigValue('allowed_ip_list'),
                    'label_attr' => [
                        'for' => 'allowed_ip_list',
                        'help' => Translator::getInstance()->trans(
                            'List of IP addresses allowed to use this payment on the front-office when in test mode (your current IP is %ip). One address per line',
                            [
                                '%ip' => $this->getRequest()->getClientIp()
                            ]
                        ),
                        'rows' => 3
                    ]
                ]
            )
            ->add(
                'minimum_amount',
                NumberType::class,
                [
                    'constraints' => array(
                        new NotBlank(),
                        new GreaterThanOrEqual([
                            'value' => 0
                        ])
                    ),
                    'required' => true,
                    'label' => Translator::getInstance()->trans('Minimum order total'),
                    'data' => BridgePayment::getConfigValue('minimum_amount', 0),
                    'label_attr' => [
                        'for' => 'minimum_amount',
                        'help' => Translator::getInstance()->trans('Minimum order total in the default currency for which this payment method is available. Enter 0 for no minimum')
                    ],
                    'attr' => [
                        'step' => 'any'
                    ]
                ]
            )
            ->add(
                'maximum_amount',
                NumberType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new GreaterThanOrEqual(
                            [
                                'value' => 0
                            ]
                        )
                    ],
                    'required' => true,
                    'label' => Translator::getInstance()->trans('Maximum order total'),
                    'data' => BridgePayment::getConfigValue('maximum_amount', 0),
                    'label_attr' => [
                        'for' => 'maximum_amount',
                        'help' => Translator::getInstance()->trans('Maximum order total in the default currency for which this payment method is available. Enter 0 for no maximum')
                    ],
                    'attr' => [
                        'step' => 'any'
                    ]
                ]
            );
    }

    protected function getBanks(): array
    {
        $event = (new BridgeBankEvent())
            ->setCountry(CountryQuery::create()->findPk(ConfigQuery::read('store_country')));

        $this->dispatcher->dispatch($event, BridgeBankEvent::GET_BANKS_EVENT);

        $bankChoices = [];

        foreach ($event->getBanks() as $bank) {
            $bankChoices[$bank['name']] = $bank['id'];
        }

        return $bankChoices;
    }
}
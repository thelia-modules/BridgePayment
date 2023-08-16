<?php

namespace BridgePayment\Form;

use BridgePayment\BridgePayment;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class BridgePaymentConfiguration extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder
            ->add(
                'run_mode',
                ChoiceType::class,
                [
                    'constraints' => [new NotBlank()],
                    'required' => true,
                    'choices' => [
                        Translator::getInstance()->trans('Sandbox', [], BridgePayment::DOMAIN_NAME) => 'TEST',
                        Translator::getInstance()->trans('Production', [], BridgePayment::DOMAIN_NAME) => 'PROD',
                    ],
                    'label' => Translator::getInstance()->trans('Run mode', [], BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'run_mode',
                        'help' => Translator::getInstance()->trans('Sandbox or production mode.', [] , BridgePayment::DOMAIN_NAME)
                    ],
                    'data' => BridgePayment::getConfigValue('run_mode'),
                ]
            )
            ->add(
                'prod_client_id',
                TextType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Production Client Id', [] , BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'prod_client_id',
                        'help' => Translator::getInstance()->trans('The production Client Id. This is the "Client Id" in your Bridge Back App.', [] , BridgePayment::DOMAIN_NAME),
                    ],
                    'data' => BridgePayment::getConfigValue('prod_client_id', ''),
                ]
            )
            ->add(
                'prod_client_secret',
                TextType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Production Client secret', [] , BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'prod_client_secret',
                        'help' => Translator::getInstance()->trans('The production Client secret. This is the "Client secret" in your Bridge Back App.', [] , BridgePayment::DOMAIN_NAME),
                    ],
                    'data' => BridgePayment::getConfigValue('prod_client_secret', ''),
                ]
            )
            ->add(
                'prod_hook_secret',
                TextType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Production Hook Secret', [], BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'prod_hook_secret',
                        'help' => Translator::getInstance()->trans('The production Hook Secret. This is the "Hook Secret" in your Bridge Back App webhook parameters.', [] , BridgePayment::DOMAIN_NAME),
                    ],
                    'data' => BridgePayment::getConfigValue('prod_hook_secret', ''),
                ]
            )
            ->add(
                'client_id',
                TextType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Sandbox Client Id', [] , BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'client_id',
                        'help' => Translator::getInstance()->trans('The sandbox Client Id. This is the "Client Id" in your Bridge Back App.', [] , BridgePayment::DOMAIN_NAME),
                    ],
                    'data' => BridgePayment::getConfigValue('client_id', ''),
                ]
            )
            ->add(
                'client_secret',
                TextType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Sandbox Client secret', [] , BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'client_secret',
                        'help' => Translator::getInstance()->trans('The sandbox Client Secret. This is the "Client Secret" in your Bridge Back App.', [] , BridgePayment::DOMAIN_NAME),
                    ],
                    'data' => BridgePayment::getConfigValue('client_secret', ''),
                ]
            )
            ->add(
                'hook_secret',
                TextType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Sandbox Hook Secret', [], BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'hook_secret',
                        'help' => Translator::getInstance()->trans('The sandbox Hook Secret. This is the "Hook Secret" in your Bridge Back App webhook parameters.', [] , BridgePayment::DOMAIN_NAME),
                    ],
                    'data' => BridgePayment::getConfigValue('hook_secret', ''),
                ]
            )
            ->add(
                'payment_mode',
                ChoiceType::class,
                [
                    'constraints' => [new NotBlank()],
                    'required' => true,
                    'choices' => [
                        Translator::getInstance()->trans('Use Bridge payment link', [], BridgePayment::DOMAIN_NAME) => 'LINK',
                        Translator::getInstance()->trans('Integrated Bridge payment page', [], BridgePayment::DOMAIN_NAME) => 'CREATE',
                    ],
                    'label' => Translator::getInstance()->trans('Bridge payment page type.', [], BridgePayment::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'payment_mode',
                        'help' => Translator::getInstance()->trans('In Bridge payment creation mode, the bank selection will be displayed on the invoice page instead of order pay redirection.',
                            [],
                            BridgePayment::DOMAIN_NAME
                        ),
                    ],
                    'data' => BridgePayment::getConfigValue('payment_mode'),
                ]
            )
            ->add(
                'allowed_ip_list',
                TextareaType::class,
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Allowed IPs in test mode', [], BridgePayment::DOMAIN_NAME),
                    'data' => BridgePayment::getConfigValue('allowed_ip_list'),
                    'label_attr' => [
                        'for' => 'allowed_ip_list',
                        'help' => Translator::getInstance()->trans(
                            'List of IP addresses allowed to use this payment on the front-office when in sandbox mode (your current IP is %ip). One address per line',
                            [
                                '%ip' => $this->getRequest()->getClientIp()
                            ],
                            BridgePayment::DOMAIN_NAME
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
                    'label' => Translator::getInstance()->trans('Minimum order total', [] , BridgePayment::DOMAIN_NAME),
                    'data' => BridgePayment::getConfigValue('minimum_amount', 0),
                    'label_attr' => [
                        'for' => 'minimum_amount',
                        'help' => Translator::getInstance()->trans('Minimum order total in the default currency for which this payment method is available. Enter 0 for no minimum', [] , BridgePayment::DOMAIN_NAME)
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
                    'label' => Translator::getInstance()->trans('Maximum order total', [] , BridgePayment::DOMAIN_NAME),
                    'data' => BridgePayment::getConfigValue('maximum_amount', 0),
                    'label_attr' => [
                        'for' => 'maximum_amount',
                        'help' => Translator::getInstance()->trans('Maximum order total in the default currency for which this payment method is available. Enter 0 for no maximum', [] , BridgePayment::DOMAIN_NAME)
                    ],
                    'attr' => [
                        'step' => 'any'
                    ]
                ]
            );
    }
}
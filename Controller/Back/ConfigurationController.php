<?php

namespace BridgePayment\Controller\Back;

use BridgePayment\BridgePayment;
use BridgePayment\Form\BridgePaymentConfiguration;
use BridgePayment\Service\Configuration;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/module/BridgePayment', name: "bridgepayment_configure")]
class ConfigurationController extends BaseAdminController
{
    #[Route('/configure', name: "_save", methods: "POST")]
    public function configure(Configuration $configurationService): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'BridgePayment', AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm(BridgePaymentConfiguration::getName());

        try {
            $form = $this->validateForm($configurationForm);

            foreach ($form->getData() as $name => $value) {
                if (in_array($name, ['success_url', 'error_url', 'error_message'])) {
                    continue;
                }

                BridgePayment::setConfigValue($name, (!is_array($value)) ? $value : implode(';', $value));
            }

            return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/BridgePayment'));

        } catch (FormValidationException $ex) {
            $error_msg = $this->createStandardFormValidationErrorMessage($ex);
        } catch (Exception $ex) {
            $error_msg = $ex->getMessage();
        }

        $this->setupFormErrorContext(
            Translator::getInstance()->trans("Configuration", [], BridgePayment::DOMAIN_NAME),
            $error_msg,
            $configurationForm,
            $ex
        );

        return $this->render('module-configure', ['module_code' => 'BridgePayment']);
    }

    #[Route('/check', name: "_check", methods: "GET")]
    public function checkConfiguration(Configuration $configurationService): JsonResponse
    {
        try {
            $configurationService->checkConfiguration();
            return new JsonResponse('');
        } catch (\Exception $ex) {
            return new JsonResponse('', 400);
        }
    }
}
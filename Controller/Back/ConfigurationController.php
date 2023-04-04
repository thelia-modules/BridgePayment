<?php

namespace BridgePayment\Controller\Back;

use BridgePayment\BridgePayment;
use BridgePayment\Form\BridgePaymentConfiguration;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;

/**
 * @Route("/admin/module/bridgepayment", name="bridgepayment_configure")
 */
class ConfigurationController extends BaseAdminController
{
    /**
     * @Route("/configure", name="_save", methods="POST")
     */
    public function configure(Request $request): Response|RedirectResponse
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'BridgePayment', AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm(BridgePaymentConfiguration::getName());

        try {
            $form = $this->validateForm($configurationForm, "POST");

            $data = $form->getData();

            foreach ($data as $name => $value) {
                if (is_array($value)) {
                    $value = implode(';', $value);
                }

                BridgePayment::setConfigValue($name, $value);
            }

            $route = '/admin/modules';

            if ($request->get('save_mode') === 'stay') {
                $route = '/admin/module/BridgePayment';
            }

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($route));

        } catch (FormValidationException $ex) {
            $error_msg = $this->createStandardFormValidationErrorMessage($ex);
        } catch (Exception $ex) {
            $error_msg = $ex->getMessage();
        }

        $this->setupFormErrorContext(
            Translator::getInstance()->trans("Scalapay configuration", [], BridgePayment::DOMAIN_NAME),
            $error_msg,
            $configurationForm,
            $ex
        );

        return $this->render('module-configure', ['module_code' => 'BridgePayment']);
    }
}
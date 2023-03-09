<?php

namespace BridgePayment\Controller;

use BridgePayment\BridgePayment;
use BridgePayment\Form\BridgePaymentConfiguration;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Admin\BaseAdminController;
use Symfony\Component\Routing\Annotation\Route;
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
    public function configure(Request $request)
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

            // Redirect to the success URL,
            if ($request->get('save_mode') === 'stay') {
                // If we have to stay on the same page, redisplay the configuration page/
                $route = '/admin/module/BridgePayment';
            } else {
                // If we have to close the page, go back to the module back-office page.
                $route = '/admin/modules';
            }

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($route));
        }catch (FormValidationException $ex) {
            // Form cannot be validated. Create the error message using
            // the BaseAdminController helper method.
            $error_msg = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            // Any other error
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
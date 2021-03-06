<?php

/* ------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2014 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  Lincence details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

  UPGRADE NOTE:
  Do not edit or add to this file if you wish to upgrade AbanteCart to newer
  versions in the future. If you wish to customize AbanteCart for your
  needs please refer to http://www.AbanteCart.com for more information.
  ------------------------------------------------------------------------------ */
if (!defined('DIR_CORE')) {
    header('Location: static_pages/');
}

include (DIR_EXT . 'checkoutapipayment/autoload.php');

class ControllerResponsesExtensionCheckoutapipayment extends AController
{

    public function getInstance()
    {
        $methodType = $this->config->get('checkoutapipayment_type') ? 'pci' : 'non-pci';

        switch ($methodType) {
            case 'pci':
                $_instance = new Model_Methods_creditcardpci($this->registry, $this->instance_id, $this->controller, $this->parent_controller);
                break;
            default :
                $_instance = new Model_Methods_creditcard($this->registry, $this->instance_id, $this->controller, $this->parent_controller);
                break;
        }
        return $_instance;
    }

    public function main()
    {
        $instance = $this->getInstance();

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('checkoutapipayment/checkoutapipayment');

        $_instance = $this->getInstance();

        $data = $_instance->getData();

        $template = $_instance->getTemplate();

        $this->view->batchAssign($data);

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $_instance->addScriptBottom();

        $this->processTemplate($template);
    }

    public function send()
    {
        return $this->getInstance()->send();
    }

    //This is Checkout.com gw webhook callback
    public function callback()
    {
        //init controller data
       $this->extensions->hk_InitData($this, __FUNCTION__);
       $this->load->model('checkout/order');
       $this->loadLanguage('checkoutapipayment/checkoutapipayment');
        
        $json = file_get_contents('php://input');

        if ($json) {
            
            $Api = CheckoutApi_Api::getApi(array('mode' => $this->config->get('checkoutapipayment_mode')));
            $objectCharge = $Api->chargeToObj($json);
          
            if ($objectCharge->isValid()) {
                
                /*
                 * Need to get track id
                 */
                $order_id = $objectCharge->getTrackId();

                if ($objectCharge->getCaptured()) {
                    
                    $message = 'Your transaction has been successfully captured';
                    $this->model_checkout_order->update($order_id, ORDER_PROCESSING, $message,true);
                    
                } elseif ($objectCharge->getRefunded()) {
                    
                    $message = 'Your payment has been refunded';
                    $this->model_checkout_order->update($order_id, 11, $message);
                    
                } elseif (!$objectCharge->getAuthorised()) {
                    
                    $message = 'Your order has been cancelled';
                    $this->model_checkout_order->update($order_id, 7, $message);
                }
            }
        }

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }
    
    //This is Checkout.com gw success callback
    public function success()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('checkoutapipayment/checkoutapipayment');

        $paymentToken = $this->request->post['cko-payment-token'];

        $config['authorization'] = $this->config->get('checkoutapipayment_secret_key');
        $config['paymentToken'] = $paymentToken;

        $Api = CheckoutApi_Api::getApi(array('mode' => $this->config->get('checkoutapipayment_mode')));
        $objectCharge = $Api->verifyChargePaymentToken($config);
        
        $order_id = $objectCharge->getTrackId();
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $amountCents = (int) (($this->currency->format($order_info['total'],'',false,false)) * 100);
        $this->load->model('checkout/order');
        $toValidate = array(
          'currency' => $this->currency->getCode(),
          'value' => $amountCents,
          'trackId' => $order_id,
        );
        
        $validateRequest = $Api::validateRequest($toValidate,$objectCharge);
        
        if (preg_match('/^1[0-9]+$/', $objectCharge->getResponseCode())) {
          $message = 'Your transaction has been successfully authorized with transaction id : ' . $objectCharge->getId();
          if(!$validateRequest['status']){  
            foreach($validateRequest['message'] as $errormessage){
              $message .= $errormessage . '. ';
            }
    
          }
          $this->model_checkout_order->update($order_id, ORDER_PROCESSING, $message);
          $this->redirect($this->html->getSecureURL('checkout/success'));
          
        } else {
          $Payment_Error = 'Transaction failed : ' . $objectCharge->getErrorMessage() . ' with response code : ' . $objectCharge->getResponseCode();
          $this->model_checkout_order->addHistory($order_id, 0, $Payment_Error);
        } 
        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);
    }

    public function cvv2_help()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('checkoutapipayment/checkoutapipayment');

        $image = '<img src="' . $this->view->templateResource('/image/securitycode.jpg') . '" alt="' . $this->language->get('entry_what_cvv2') . '" />';

        $this->view->assign('description', $image);

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->processTemplate('responses/content/content.tpl');
    }

}

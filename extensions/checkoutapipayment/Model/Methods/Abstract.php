<?php

abstract class Model_Methods_Abstract extends ControllerResponsesExtensionCheckoutapipayment
{


    public function send()
    {
       return $this->_placeorder();
    }

    protected function _placeorder()
    {

       $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        //building charge
        $respondCharge = $this->_createCharge($order_info);

        if ($respondCharge->isValid()) {

            if (preg_match('/^1[0-9]+$/', $respondCharge->getResponseCode())) {
                $message = 'Your transaction has been successfully authorized with transaction id : ' . $respondCharge->getId();
                $this->model_checkout_order->confirm($this->session->data['order_id'], ORDER_PROCESSING, $message);
                $json['success'] = $this->html->getSecureURL('checkout/success');
            }
            else {
                $Payment_Error = 'Transaction failed : ' . $respondCharge->getErrorMessage() . ' with response code : ' . $respondCharge->getResponseCode();
                $this->model_checkout_order->addHistory($this->session->data['order_id'], 0, $Payment_Error);
                $json['error'] = 'We are sorry, but you transaction could not be processed. Please verify your card information and try again.';
            }
        }
        else {

            $json['error'] = $respondCharge->getExceptionState()->getErrorMessage();
        }

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($json));
    }

    protected function _createCharge($order_info)
    {

        $config = array();
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $productsLoad = $this->cart->getProducts();
        $scretKey = $this->config->get('checkoutapipayment_secret_key');
        $orderId = $this->session->data['order_id'];
        $amountCents = (int) (($this->currency->format($order_info['total'],'',false,false)) * 100);
        $config['authorization'] = $scretKey;
        $config['mode'] = $this->config->get('checkoutapipayment_mode');
        $config['timeout'] = $this->config->get('checkoutapipayment_timeout');

        if ($this->config->get('checkoutapipayment_method') == 'authorize_capture') {
            $config = array_merge($config, $this->_captureConfig());
        }
        else {

            $config = array_merge($config, $this->_authorizeConfig());
        }

        $products = array();
        foreach ($productsLoad as $item) {

            $products[] = array(
                'name'     => $item['name'],
                'sku'      => $item['key'],
                'price'    => $this->currency->format($item['price'], $this->currency->getCode(), false, false),
                'quantity' => $item['quantity']
            );
        }
        $billPhoneLength = strlen($order_info['telephone']);
        $billingAddressConfig = array(
            'addressLine1' => $order_info['payment_address_1'],
            'addressLine2' => $order_info['payment_address_2'],
            'postcode'     => $order_info['payment_postcode'],
            'country'      => $order_info['payment_iso_code_2'],
            'city'         => $order_info['payment_city'],
        );
        
        if ($billPhoneLength > 6){
              $bilPhoneArray = array(
                  'phone'  => array('number' => $order_info['telephone'])
              );
              $billingAddressConfig = array_merge_recursive($billingAddressConfig, $bilPhoneArray);  
        }
        $shipPhoneLength = strlen($order_info['telephone']);
        $shippingAddressConfig = array(
            'addressLine1'  => $order_info['shipping_address_1'],
            'addressLine2'  => $order_info['shipping_address_2'],
            'postcode'      => $order_info['shipping_postcode'],
            'country'       => $order_info['payment_iso_code_2'],
            'city'          => $order_info['shipping_city'],
        );
        if ($shipPhoneLength > 6){
              $shipPhoneArray = array(
                  'phone'  => array('number' => $order_info['telephone'])
              );
              $shippingAddressConfig = array_merge_recursive($shippingAddressConfig, $shipPhoneArray);  
        }

        $config['postedParam'] = array_merge($config['postedParam'], array(
            'email'           => $order_info['email'],
            'value'           => $amountCents,
            'trackId'         => $orderId,
            'currency'        => $this->currency->getCode(),
            'description'     => "Order number::$orderId",
            'shippingDetails' => $shippingAddressConfig,
            'products'        => $products,
            'card'            => array(
                'billingDetails' => $billingAddressConfig
            )
        ));
        return $config;
    }

    protected function _captureConfig()
    {
        $to_return['postedParam'] = array(
            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE,
            'autoCapTime' => $this->config->get('checkoutapipayment_autocaptime')
        );

        return $to_return;
    }

    protected function _authorizeConfig()
    {
        $to_return['postedParam'] = array(
            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH,
            'autoCapTime' => 0
        );

        return $to_return;
    }

    protected function _getCharge($config)
            
    {
        $Api = CheckoutApi_Api::getApi(array('mode' => $this->config->get('checkoutapipayment_mode')));
        
        return $Api->createCharge($config);
    }

}

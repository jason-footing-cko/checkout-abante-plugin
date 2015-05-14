<?php

class Model_Methods_creditcard extends Model_Methods_Abstract implements Model_Interface
{

    protected $template = 'responses/creditcard.tpl';

    function getTemplate()
    {
        return $this->template;
    }

    function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getData()
    {

        $this->language->load('checkoutapipayment/checkoutapipayment');
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $paymentTokenArray = $this->generatePaymentToken();

        $toReturn = array(
            'text_card_details' => $this->language->get('text_card_details'),
            'text_wait'         => $this->language->get('text_wait'),
            'order_email'       => $order_info['email'],
            'order_currency'    => $this->currency->getCode(),
            'amount'            => (int) (($this->currency->format($order_info['total'],'',false,false)) * 100),
            'publicKey'         => $this->config->get('checkoutapipayment_public_key'),
            'email'             => $order_info['email'],
            'name'              => $order_info['firstname'] . ' ' . $order_info['lastname'],
            'store_name'        => $order_info['store_name'],
            'paymentToken'      => $paymentTokenArray['token'],
            'mode'              => $this->config->get('checkoutapipayment_mode'),
            'message'           => $paymentTokenArray['message'],
            'success'           => $paymentTokenArray['success'],
            'eventId'           => $paymentTokenArray['eventId'],
            'textWait'          => $this->language->get('text_wait'),
        );

        $toReturn['back'] = HtmlElementFactory::create(array('type' => 'button',
                    'name'  => 'back',
                    'text'  => $this->language->get('button_back'),
                    'style' => 'button',
                    'href'  => $back
        ));
        $toReturn['submit'] = HtmlElementFactory::create(array('type' => 'button',
                    'name'  => 'checkoutapipayment_button',
                    'text'  => $this->language->get('button_confirm'),
                    'style' => 'button'
        ));

        return $toReturn;
    }

    protected function _createCharge($order_info)
    {
        $config = array();

        $scretKey = $this->config->get('checkoutapipayment_secret_key');

        $config['authorization'] = $scretKey;
        $config['timeout'] = $this->config->get('checkoutapipayment_timeout');
        $config['paymentToken'] = $this->request->post['cko_cc_paymenToken'];
        $Api = CheckoutApi_Api::getApi(array('mode' => $this->config->get('checkoutapipayment_mode')));

        return $Api->verifyChargePaymentToken($config);
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


    public function generatePaymentToken()
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

        if ($this->config->get('checkoutapipayment_method') == 'authorization_capture') {
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
            'trackId'         => $orderId,
            'value'           => $amountCents,
            'currency'        => $this->currency->getCode(),
            'description'     => 'Order number::'. $orderId,
            'shippingDetails' => $shippingAddressConfig,
            'products'        => $products,
            'billingDetails'  => $billingAddressConfig
        ));

        $Api = CheckoutApi_Api::getApi(array('mode' => $this->config->get('checkoutapipayment_mode')));
        $paymentTokenCharge = $Api->getPaymentToken($config);

        $paymentTokenArray = array(
            'message' => '',
            'success' => '',
            'eventId' => '',
            'token' => '',
        );

        if ($paymentTokenCharge->isValid()) {
            $paymentTokenArray['token'] = $paymentTokenCharge->getId();
            $paymentTokenArray['success'] = true;
        }
        else {


            $paymentTokenArray['message'] = $paymentTokenCharge->getExceptionState()->getErrorMessage();
            $paymentTokenArray['success'] = false;
            $paymentTokenArray['eventId'] = $paymentTokenCharge->getEventId();
        }

        return $paymentTokenArray;
    }

    public function addScriptBottom()
    {
        
    }

}

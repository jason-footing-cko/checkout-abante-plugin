<?php

class Model_Methods_creditcardpci extends Model_Methods_Abstract implements Model_Interface
{

    public $template = 'responses/creditcardpci.tpl';

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
         $this->load->model('checkout/order');

        $this->language->load('checkoutapipayment/checkoutapipayment');

        $data['text_credit_card'] = $this->language->get('text_credit_card');

        $data['text_wait'] = $this->language->get('text_wait');

        $data['entry_cc_owner'] = $this->language->get('entry_cc_owner');
        $data['cc_owner'] = HtmlElementFactory::create(array('type' => 'input',
                    'name'  => 'cc_owner',
                    'value' => ''));

        $data['entry_cc_number'] = $this->language->get('entry_cc_number');
        $data['cc_number'] = HtmlElementFactory::create(array('type' => 'input',
                    'name'  => 'cc_number',
                    'attr'  => 'autocomplete="off"',
                    'value' => ''));

        $data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');

        $data['entry_cc_cvv2'] = $this->language->get('entry_cc_cvv2');
        $data['entry_cc_cvv2_short'] = $this->language->get('entry_cc_cvv2_short');
        $data['cc_cvv2_help_url'] = $this->html->getURL('r/extension/checkoutapipayment/cvv2_help');

        $data['cc_cvv2'] = HtmlElementFactory::create(array('type' => 'input',
                    'name'  => 'cc_cvv2',
                    'value' => '',
                    'style' => 'short input-mini',
                    'attr'  => ' size="3" autocomplete="off"'
        ));

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['button_back'] = $this->language->get('button_back');

        $months = array();

        for ($i = 1; $i <= 12; $i++) {
            $months[sprintf('%02d', $i)] = strftime('%B', mktime(0, 0, 0, $i, 1, 2000));
        }
        $data['cc_expire_date_month'] = HtmlElementFactory::create(
                        array('type'  => 'selectbox',
                            'name'    => 'cc_expire_date_month',
                            'value'   => sprintf('%02d', date('m')),
                            'options' => $months,
                            'style'   => 'short input-small'
        ));

        $today = getdate();
        $years = array();
        for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
            $years[strftime('%Y', mktime(0, 0, 0, 1, 1, $i))] = strftime('%Y', mktime(0, 0, 0, 1, 1, $i));
        }
        $data['cc_expire_date_year'] = HtmlElementFactory::create(
                        array('type'  => 'selectbox',
                            'name'    => 'cc_expire_date_year',
                            'value'   => sprintf('%02d', date('Y') + 1),
                            'options' => $years,
                            'style'   => 'short input-small'
        ));

        $back = $this->request->get['rt'] != 'checkout/guest_step_3' ? $this->html->getSecureURL('checkout/payment') : $this->html->getSecureURL('checkout/guest_step_2');
        $data['back'] = HtmlElementFactory::create(array('type' => 'button',
                    'name'  => 'back',
                    'text'  => $this->language->get('button_back'),
                    'style' => 'button',
                    'href'  => $back
        ));
        $data['submit'] = HtmlElementFactory::create(array('type' => 'button',
                    'name'  => 'checkoutapipayment_button',
                    'text'  => $this->language->get('button_confirm'),
                    'style' => 'button'
        ));

        return $data;
    }

    protected function _createCharge($order_info)
    {
        $config = parent::_createCharge($order_info);
       

        $config['postedParam']['card'] = array_merge($config['postedParam']['card'], array(
            'name'        => str_replace(' ', '', $this->request->post['cc_owner']),
            'number'      => str_replace(' ', '', $this->request->post['cc_number']),
            'expiryMonth' => str_replace(' ', '', $this->request->post['cc_expire_date_month']),
            'expiryYear'  => str_replace(' ', '', $this->request->post['cc_expire_date_year']),
            'cvv'         => str_replace(' ', '', $this->request->post['cc_cvv2']),
                )
        );
        return $this->_getCharge($config);
    }

    public function addScriptBottom()
    {
        //load creditcard input validation
        $this->document->addScriptBottom($this->view->templateResource('/javascript/credit_card_validation.js'));
    }

}

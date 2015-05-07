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

class ModelExtensionCheckoutapipayment extends Model
{

    public function getMethod()
    {
        $this->load->language('checkoutapipayment/checkoutapipayment');
        if ($this->config->get('checkoutapipayment_status')) {
            $status = TRUE;
        } else {
            $status = FALSE;
        }
        $method_data = array();

        if ($status) {
            $method_data = array(
                'id'         => 'checkoutapipayment',
                'title'      => $this->language->get('text_title'),
                'sort_order' => $this->config->get('checkoutapipayment_sort_order')
            );
        }

        return $method_data;
    }

}

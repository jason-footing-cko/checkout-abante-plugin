<h4 class="heading4"><?php echo $text_credit_card; ?>:</h4>

<form id="checkoutapipayment" class="form-horizontal validate-creditcard" action="#">
    <div class="widget-container"></div>
    <div class="paymentokenWrapper" style="height: 30px">
        <input type="hidden" name="cko_cc_paymenToken" id="cko-cc-paymenToken" value="<?php echo $paymentToken ?>">
        <input type="hidden" name="cko-cc-redirectUrl" id="cko-cc-redirectUrl" value="">
    </div>
    <div class="form-group action-buttons text-center">
        <a id="<?php echo $back->name ?>" href="<?php echo $back->href; ?>" class="btn btn-default mr10" title="<?php echo $back->text ?>">
            <i class="fa fa-arrow-left"></i>
            <?php echo $back->text ?>
        </a>

        <button id="<?php echo $submit->name ?>" class="btn btn-orange" title="<?php echo $submit->text ?>" type="submit">
            <i class="fa fa-check"></i>
            <?php echo $submit->text; ?>
        </button>
    </div>
</form>

<script type="text/javascript">
    var reload = false;
    window.CKOConfig = {
        debugMode: false,
        renderMode: 2,
        namespace: 'CheckoutIntegration',
        publicKey: '<?php echo $publicKey ?>',
        paymentToken: '<?php echo $paymentToken ?>',
        value: '<?php echo $amount ?>',
        currency: '<?php echo $order_currency ?>',
        customerEmail: '<?php echo $email ?>',
        customerName: '<?php echo $name ?>',
        useCurrencyCode: '<?php echo $currencyformat ?>',
        title: '<?php echo $store_name ?>',
        forceMobileRedirect: true,
        subtitle: 'Please enter your credit card details',
        widgetContainerSelector: '.widget-container',
        styling: {
            themeColor: '<?php echo $themecolor ?>',
            buttonColor:'<?php echo $buttoncolor ?>',
            logoUrl: '<?php echo $logourl ?>',
            iconColor: '<?php echo $iconcolor ?>',
        },
        cardCharged: function (event) {
            confirmSubmit();
        },
        lightboxDeactivated: function () {
            if (reload) {
                window.location.reload();
            }
            jQuery('#checkoutapipayment_button').prop("disabled", false);
        },
        paymentTokenExpired: function(event){
            reload = true;
        }, 
        invalidLightboxConfig: function(event){
            reload = true;
        },
        ready : function(event) {
            if(CheckoutIntegration.isMobile()){
                $('#cko-cc-redirectUrl').val(CheckoutIntegration.getRedirectionUrl());
            }
        }

    };
</script>
<?php   if($mode == 'live') : ?>
            <script src="https://www.checkout.com/cdn/js/checkout.js" async ></script>
 <?php  else :?>
            <script src="//sandbox.checkout.com/js/v1/checkout.js" async ></script>
 <?php  endif; ?>

<script>
    $('form#checkoutapipayment').submit(function (event) {
        event.preventDefault();
        if(typeof CheckoutIntegration !='undefined') {
            if(!CheckoutIntegration.isMobile()){ 
                CheckoutIntegration.open();
                $('#checkoutapipayment_button').prop("disabled", true);
            }
            else {
                confirmSubmit();
            }
        }
    });
    
    function confirmSubmit() {
	$.ajax({
		type: 'POST',
		url: 'index.php?rt=extension/checkoutapipayment/send',
		data: $('#checkoutapipayment :input'),
		dataType: 'json',
		beforeSend: function() {
			$('.alert').remove();
			$('#checkoutapipayment .action-buttons').hide(); 
			$('#checkoutapipayment .action-buttons').before('<div class="wait alert alert-info text-center"><i class="fa fa-refresh fa-spin"></i> <?php echo $text_wait; ?></div>');
		},
		success: function(data) {
			if (!data) {
				$('.wait').remove();
				$('#checkoutapipayment .action-buttons').show(); 
				$('#checkoutapipayment').before('<div class="alert alert-danger"><i class="fa fa-bug"></i> <?php echo $error_unknown; ?></div>');
			} else {
				if (data.error) {
					$('.wait').remove();
					$('#checkoutapipayment .action-buttons').show(); 
					$('#checkoutapipayment').before('<div class="alert alert-warning"><i class="fa fa-exclamation"></i> '+data.error+'</div>');
				}	
				if (data.success) {			
					location = data.success;
				}
			}
		},
		error: function (jqXHR, textStatus, errorThrown) {
			$('.wait').remove();
			$('#checkoutapipayment .action-buttons').show(); 
			$('#checkoutapipayment').before('<div class="alert alert-danger"><i class="fa fa-exclamation"></i> '+textStatus+' '+errorThrown+'</div>');
		}				
	});
}
</script>


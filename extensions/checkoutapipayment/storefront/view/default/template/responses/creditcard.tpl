<h4 class="heading4"><?php echo $text_credit_card; ?>:</h4>

<form id="checkoutapipayment" class="form-horizontal validate-creditcard">
    <div class="widget-container"></div>
    <div class="paymentokenWrapper" style="height: 30px">
        <input type="hidden" name="cko_cc_paymenToken" id="cko-cc-paymenToken" value="<?php echo $paymentToken ?>">
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
        paymentMode: 'card',
        title: '<?php echo $store_name ?>',
        subtitle: 'Please enter your credit card details',
        widgetContainerSelector: '.widget-container',
        cardCharged: function (event) {
            document.getElementById('cko-cc-paymenToken').value = event.data.paymentToken;
            $.ajax({
                type: 'POST',
                url: 'index.php?rt=extension/checkoutapipayment/send',
                data: $('#checkoutapipayment :input'),
                dataType: 'json',
                beforeSend: function () {
                    $('.alert').remove();
                    $('#checkoutapipayment .action-buttons').hide();
                    $('#checkoutapipayment .action-buttons').before('<div class="wait alert alert-info text-center"><i class="fa fa-refresh fa-spin"></i> <?php echo $text_wait; ?></div>');
                },
                success: function (data) {
                    if (!data) {
                        $('.wait').remove();
                        $('#checkoutapipayment .action-buttons').show();
                        $('#checkoutapipayment').before('<div class="alert alert-danger"><i class="fa fa-bug"></i> <?php echo $error_unknown; ?></div>');
                    } else {
                        if (data.error) {
                            $('.wait').remove();
                            $('#checkoutapipayment .action-buttons').show();
                            $('#checkoutapipayment').before('<div class="alert alert-warning"><i class="fa fa-exclamation"></i> ' + data.error + '</div>');
                        }
                        if (data.success) {
                            location = data.success;
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('.wait').remove();
                    $('#checkoutapipayment .action-buttons').show();
                    $('#checkoutapipayment').before('<div class="alert alert-danger"><i class="fa fa-exclamation"></i> ' + textStatus + ' ' + errorThrown + '</div>');
                }
            });

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
    };
</script>
<script src="https://www.checkout.com/cdn/js/checkout.js" async ></script>
<script>
    $('form#checkoutapipayment').submit(function (event) {
        jQuery('#checkoutapipayment_button').prop("disabled", true);
        event.preventDefault();
        CheckoutIntegration.open();
    });
</script>


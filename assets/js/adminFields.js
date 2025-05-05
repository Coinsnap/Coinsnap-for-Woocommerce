jQuery(document).ready(function ($) {
    
    if($('#woocommerce_coinsnap_discount_type').length){
        
        enableDiscount();
        
        $('#woocommerce_coinsnap_discount_enable').change(function(){
            enableDiscount();
        });
        
        $('#woocommerce_coinsnap_discount_amount_limit').change(function(){
            if(parseFloat($(this).val()) < 0){
                $(this).val(0);
            }
            if(parseFloat($(this).val()) > 100){
                $(this).val(100);
            }
        });
        
        $('#woocommerce_coinsnap_discount_percentage').change(function(){
            if(parseFloat($(this).val()) < 0){
                $(this).val(0);
            }
            if(parseFloat($(this).val()) > 100){
                $(this).val(100);
            }
        });
        
        $('.discount').keyup(function() {
            $(this).val($(this).val().replace(/[^0-9,]/g,''));
        });
        
        if($('#woocommerce_coinsnap_discount_enable').prop('checked')){
            setDiscount();
        }
        
        $('#woocommerce_coinsnap_discount_type').change(function(){
            setDiscount();
        });
    }
    
    if($('#coinsnap_provider').length){
        
        setProvider();
        
        $('#coinsnap_provider').change(function(){
            setProvider();
        });
    }
    
    function enableDiscount(){
        if($('#woocommerce_coinsnap_discount_enable').prop('checked')){
            $('.discount').closest('tr').show();
        }
        else {
            $('.discount').closest('tr').hide();
        }
    }
    
    function setDiscount(){
        if($('#woocommerce_coinsnap_discount_type').val() === 'fixed'){
            $('.discount.discount-percentage').closest('tr').hide();
            $('.discount.discount-amount').closest('tr').show();
        }
        else {
            $('.discount.discount-amount').closest('tr').hide();
            $('.discount.discount-percentage').closest('tr').show();
        }
    }
    
    function setProvider(){
        if($('#coinsnap_provider').val() === 'coinsnap'){
            $('.btcpay').closest('tr').hide();
            $('.btcpay').removeAttr('required');
            $('.coinsnap').closest('tr').show();
            $('.coinsnap.required').attr('required','required');
        }
        else {
            $('.coinsnap').closest('tr').hide();
            $('.coinsnap').removeAttr('required');
            $('.btcpay').closest('tr').show();
            $('.btcpay.required').attr('required','required');
        }
    }
    
    function isValidUrl(serverUrl) {
        try {
            const url = new URL(serverUrl);
            if (url.protocol !== 'https:' && url.protocol !== 'http:') {
                return false;
            }
	}
        catch (e) {
            console.error(e);
            return false;
	}
        return true;
    }

    $('.btcpay-apikey-link').click(function(e) {
        e.preventDefault();
        const host = $('#btcpay_server_url').val();
	if (isValidUrl(host)) {
            let data = {
                'action': 'btcpay_server_apiurl_handler',
                'host': host,
                'apiNonce': coinsnap_ajax.nonce
            };
            
            $.post(coinsnap_ajax.ajax_url, data, function(response) {
                if (response.data.url) {
                    window.location = response.data.url;
		}
            }).fail( function() {
		alert('Error processing your request. Please make sure to enter a valid BTCPay Server instance URL.')
            });
	}
        else {
            alert('Please enter a valid url including https:// in the BTCPay Server URL input field.')
        }
    });
});


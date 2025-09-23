jQuery(document).ready(function ($) {
    
    const intervalId = setInterval(function() {
    

        if($('input[value="coinsnap"]').length > 0){
            
            clearInterval(intervalId);
            let checkoutElement = $('input[value="coinsnap"] + div span.wc-block-components-payment-method-label');

            let ajaxurl = coinsnap_ajax.ajax_url;
            let data = {
                action: 'coinsnap_checkout',
                _wpnonce: coinsnap_ajax.nonce
            };

            jQuery.post( ajaxurl, data, function( response ){
                checkoutDiscountResponse = $.parseJSON(response);
                console.log(response);
                if(checkoutDiscountResponse['result'] === true){
                    checkoutElement.append('<div id="coinsnap-bitcoin-discount">'+checkoutDiscountResponse['message']+'</div>');
                }
            });
        }
        
    }, 1000);
        
});


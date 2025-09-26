jQuery(document).ready(function ($) {
    
    $(document.body).on('experimental__woocommerce_blocks-updated-checkout', function() {
        alert('Checkout updated!');
        // Perform actions after checkout totals or fields are refreshed
    });
    
    var coinsnapDiscount = '';
    
    const intervalId = setInterval(function(){
    
        if($('input[value="coinsnap"]').length > 0){
            
            clearInterval(intervalId);
            let checkoutElement = ($('input[value="coinsnap"]').length > 0)? $('input[value="coinsnap"] + div span.wc-block-components-payment-method-label') : null;
            

            let ajaxurl = coinsnap_ajax.ajax_url;
            let data = {
                action: 'coinsnap_checkout',
                _wpnonce: coinsnap_ajax.nonce
            };

            jQuery.post( ajaxurl, data, function( response ){
                checkoutDiscountResponse = $.parseJSON(response);
                console.log(response);
                if(checkoutDiscountResponse['result'] === true){
                    coinsnapDiscount = checkoutDiscountResponse['message'];
                    if(checkoutElement){
                        checkoutElement.append('<div class="coinsnap-bitcoin-discount">'+coinsnapDiscount+'</div>');
                    }
                    
                    if($('input[value="coinsnap"]').is(':checked')){
                        setTotalElementDiscount();
                    }
                }
            });
            
            const paymentMethodFieldName = $('input[value="coinsnap"]').attr('name');
            
            
            
            $('input[name="'+paymentMethodFieldName+'"]').change(function(){
                let paymentMethodFieldValue = $('input[name="'+paymentMethodFieldName+'"]:checked').val();
                console.log('Payment method: '+paymentMethodFieldValue);
                if(paymentMethodFieldValue === 'coinsnap'){
                    setTotalElementDiscount();
                }
            });
        }
        
        
    }, 500);
    
    function setTotalElementDiscount(){
        
        const intervalTotal = setInterval(function(){
            
            if($('.wc-block-components-totals-fees__bitcoin-discount').length > 0){
                clearInterval(intervalTotal);
                
                let checkoutTotalsElement = ($('.wc-block-components-totals-fees__bitcoin-discount').length > 0)? $('.wc-block-components-totals-fees__bitcoin-discount') : null;
        
                if(checkoutTotalsElement){
                    checkoutTotalsElement.append('<div class="coinsnap-bitcoin-discount">'+coinsnapDiscount+'</div>');
                }
            }
            
        }, 500);        
    }
        
});



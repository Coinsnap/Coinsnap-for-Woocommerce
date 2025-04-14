jQuery(function ($) {
    
    if($('#woocommerce_coinsnap_discount_type').length){
        
        $('#woocommerce_coinsnap_discount_enable').change(function(){
            if($(this).prop('checked')){
                $('.discount').closest('tr').show();
            }
            else {
                $('.discount').closest('tr').hide();
            }
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
        
        setDiscount();
        
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
});


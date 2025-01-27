jQuery(function ($) {
    
    if($('hr.wp-header-end').length){
        connectionCheckElement = $('hr.wp-header-end');
    }
    else if($('#jp-admin-notices').length){
        connectionCheckElement = $('#jp-admin-notices');
    }
    
    let ajaxurl = '/wp-admin/admin-ajax.php';
    let data = {
	action: 'coinsnap_connection_handler'
    };

    jQuery.post( ajaxurl, data, function( response ){
        console.log( 'Coinsnap connection check JSON:' + response );
        connectionCheckResponse = $.parseJSON(response);
        let resultClass = (connectionCheckResponse.result === true)? 'success' : 'error';
        
        if(connectionCheckElement){
            connectionCheckElement.after('<div class="message '+resultClass+' notice" style="margin-top: 10px;"><p>'+ connectionCheckResponse.message +'</p></div>');
        }
        else {
            $('#wpbody-content').prepend('<div class="message '+resultClass+' notice" style="margin-top: 10px;"><p>'+ connectionCheckResponse.message +'</p></div>');
        }
        
        if($('#coinsnapConnectionStatus').length){
            $('#coinsnapConnectionStatus').html('<span class="'+resultClass+'">'+ connectionCheckResponse.message +'</span>');
        }
    });
    
    
    
    
    
    
});
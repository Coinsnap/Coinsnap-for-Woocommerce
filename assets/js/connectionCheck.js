jQuery(function ($) {
    
    let connectionCheckElement = '';
    
    if($('hr.wp-header-end').length){
        connectionCheckElement = 'hr.wp-header-end';
    }
    else if($('#jp-admin-notices').length){
        connectionCheckElement = '#jp-admin-notices';
    }
    
    let ajaxurl = coinsnap_ajax['ajax_url'];
    let data = {
	action: 'coinsnap_connection_handler',
        _wpnonce: coinsnap_ajax['nonce']
    };

    jQuery.post( ajaxurl, data, function( response ){
        
        connectionCheckResponse = $.parseJSON(response);
        let resultClass = (connectionCheckResponse.result === true)? 'success' : 'error';
        
        $connectionCheckMessage = '<div class="message '+resultClass+' notice" style="margin-top: 10px;"><p>'+ connectionCheckResponse.message +'</p></div>';
        
        if(connectionCheckElement !== ''){
            $(connectionCheckElement).after($connectionCheckMessage);
        }
        else {
            $('#wpbody-content').prepend($connectionCheckMessage);
        }
        
        if($('#coinsnapConnectionStatus').length){
            $('#coinsnapConnectionStatus').html('<span class="'+resultClass+'">'+ connectionCheckResponse.message +'</span>');
        }
    });
});
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
        if(connectionCheckResponse.display === 'everywhere'){
            setCookie('isConnectionStatusHidden', 0, -1);
        }
        var isConnectionStatusHidden = getCookie('isConnectionStatusHidden');
        
        
        if(connectionCheckResponse.display !== 'settingspage' && connectionCheckResponse.display !== '' && !isConnectionStatusHidden){        
        
            $connectionCheckMessage = '<div id="coinsnapConnectionTopStatus" class="message '+resultClass+' notice" style="margin-top: 10px;"><p>'+ connectionCheckResponse.message +'</p></div>';
        
            if(connectionCheckElement !== ''){
                $(connectionCheckElement).after($connectionCheckMessage);
            }
            else {
                $('#wpbody-content').prepend($connectionCheckMessage);
            }
            
            
            
            if(connectionCheckResponse.display === 'hideable'){
                $('#coinsnapConnectionTopStatus').addClass('is-dismissible').append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
                $('#coinsnapConnectionTopStatus .notice-dismiss').click(function(){
                    setCookie('isConnectionStatusHidden', 1, 3);
                    $('#coinsnapConnectionTopStatus').hide(500); 
                });
            }
            
            
        }
        
        if($('#coinsnapConnectionStatus').length){
            $('#coinsnapConnectionStatus').html('<span class="'+resultClass+'">'+ connectionCheckResponse.message +'</span>');
        }
    });
    
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    function setCookie(name, value, seconds) {
        const d = new Date();
        d.setTime(d.getTime() + (seconds * 1000*3600));
        const expires = "expires=" + d.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }
});


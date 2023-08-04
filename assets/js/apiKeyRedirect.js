jQuery(document).ready(function($) {
	function isValidUrl(serverUrl) {
		try {
			const url = new URL(serverUrl);
			if (url.protocol !== 'https:' && url.protocol !== 'http:') {
				return false;
			}
		} catch (e) {
			console.error(e);
			return false;
		}
		return true;
 	}

	$('.coinsnap-api-key-link').click(function(e) {
		e.preventDefault();
		const host = 'https://app.coinsnap.io/api/v1/websites/'+$('#coinsnap_store_id').val(); //$('#btcpay_gf_url').val();
		if (isValidUrl(host)) {
			let data = {
				'action': 'handle_ajax_api_url',
				'host': host,
				'apiNonce': CoinsnapGlobalSettings.apiNonce
			};
			jQuery.post(CoinsnapGlobalSettings.url, data, function(response) {
				if (response) {
                                    alert("Server's answer is: "+response);
					//window.location = response.data.url;
				}
			}).fail( function() {
				alert('Error processing your request. We have a touble with connection to Coinsnap server with your Shop ID')
			});
		} else {
			alert('Please enter a valid url including https:// in the Coinsnap URL input field.')
		}
                
                return false;
	});
});

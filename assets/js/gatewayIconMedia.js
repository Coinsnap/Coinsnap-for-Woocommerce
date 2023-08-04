jQuery(function ($) {
	// Open media library and get the selected image.
	$('.coinsnap-icon-button').click(function (e) {
		e.preventDefault();

		let button = $(this),
			custom_uploader = wp.media({
				title: coinsnapGatewayData.titleText,
				library: {
					type: 'image'
				},
				button: {
					text: coinsnapGatewayData.buttonText
				},
				multiple: false
			}).on('select', function () { // it also has "open" and "close" events
				let attachment = custom_uploader.state().get('selection').first().toJSON();
				let url = '';
				if (attachment.sizes.thumbnail !== undefined) {
					url = attachment.sizes.thumbnail.url;
				} else {
					url = attachment.url;
				}
				$('.coinsnap-icon-image').attr('src', url).show();
				$('.coinsnap-icon-remove').show();
				$('.coinsnap-icon-value').val(attachment.id);
				button.hide();
			}).open();
	});

	// Handle removal of media image.
	$('.coinsnap-icon-remove').click(function (e) {
		e.preventDefault();

		$('.coinsnap-icon-value').val('');
		$('.coinsnap-icon-image').hide();
		$(this).hide();
		$('.coinsnap-icon-button').show();
	});
});

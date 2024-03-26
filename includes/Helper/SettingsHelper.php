<?php

namespace Coinsnap\WC\Helper;

class SettingsHelper {
	public function gatewayFormFields(
		$defaultTitle,
		$defaultDescription
	) {
		$this->form_fields = [
			'title' => [
				'title'       => __('Title', 'coinsnap-for-woocommerce'),
				'type'        => 'text',
				'description' => __('Controls the name of this payment method as displayed to the customer during checkout.', 'coinsnap-for-woocommerce'),
				'default'     => __('Bitcoin, Lightning Network', 'coinsnap-for-woocommerce'),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __('Customer Message', 'coinsnap-for-woocommerce'),
				'type'        => 'textarea',
				'description' => __('Message to explain how the customer will be paying for the purchase.', 'coinsnap-for-woocommerce'),
				'default'     => 'You will be redirected to the Bitcoin Payment Page to complete your purchase.',
				'desc_tip'    => true,
			],
		];

		return $this->form_fields;
	}
}

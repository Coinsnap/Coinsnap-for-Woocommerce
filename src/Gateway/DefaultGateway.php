<?php

namespace Coinsnap\WC\Gateway;

//  Default Gateway that provides all available payment methods of Coinsnap Server store configuration.
class DefaultGateway extends AbstractGateway {
    public function __construct() {
        // Set the id first.
	$this->id = 'coinsnap';

	// Call parent constructor.
	parent::__construct();

		// todo: maybe make the button text configurable via settings.
		// General gateway setup.
		//$this->order_button_text  = __('Proceed to payment gateway', 'coinsnap-for-woocommerce');
		// Admin facing title and description.
		$this->method_title       = 'Coinsnap (default)';
		$this->method_description = __('Coinsnap default gateway supporting all available tokens on your store.', 'coinsnap-for-woocommerce');

		// Actions.
		add_action('woocommerce_api_coinsnap', [$this, 'processWebhook']);
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->get_option('title', 'Bitcoin, Lightning Network');
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): string {
		return $this->get_option('description', 'You will be redirected to the Bitcoin Payment Page to complete your purchase.');
	}

	/**
	 * @inheritDoc
	 */
	public function init_form_fields(): void {
		parent::init_form_fields();
		$this->form_fields += [
			'enforce_payment_tokens' => [
				'title'       => __( 'Enforce payment tokens', 'coinsnap-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enforce payment methods "payment". This way tokens of type promotion will be excluded for this gateway.', 'coinsnap-for-woocommerce' ),
				'default'     => 'yes',
				'value'       => 'yes',
				'description' => __( 'This will override the default Coinsnap payment method (defaults to all supported by Coinsnap Server) and enforce to tokens of type "payment". This is useful if you have enabled separate payment gateways and want full control on what is available on Coinsnap Server payment page.', 'coinsnap-for-woocommerce' ),
				'desc_tip'    => true,
			],
		];
	}

	
	public function getPaymentMethods(): array {

		$coinsnapGateway = [];

		if ($this->get_option('enforce_payment_tokens') === 'yes') {
			$gateways = WC()->payment_gateways->payment_gateways();
			/** @var  $gateway AbstractGateway */
			foreach ($gateways as $id => $gateway) {
				if (
					strpos($id, 'coinsnap') !== FALSE
					&& (isset($gateway->tokenType) && $gateway->tokenType === 'payment')
				) {
					$coinsnapGateway[] = $gateway->primaryPaymentMethod;
				}
			}
			return $coinsnapGateway;
		}

		// If payment tokens are not enforced set all.
		$separateGateways = \Coinsnap\WC\Helper\ApiHelper::supportedPaymentMethods();
		foreach ($separateGateways as $sgw) {
			$coinsnapGateway[] = $sgw['symbol'];
		}

		return $coinsnapGateway;
	}

}

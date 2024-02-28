<?php

namespace Coinsnap\WC\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Coinsnap\WC\Gateway\AbstractGateway;
use Coinsnap\WC\Gateway\DefaultGateway;

final class DefaultGatewayBlocks extends AbstractPaymentMethodType {

    //  The gateway instance.
    private $gateway;

    // Payment method name/id/slug.
    protected $name = 'coinsnap';

    // Initializes the payment method type.
    public function initialize(): void {
	$this->settings = get_option( 'woocommerce_coinsnap_settings', [] );
	$gateways = \WC()->payment_gateways->payment_gateways();
	$this->gateway  = $gateways[$this->name];
    }

    //  Returns if this payment method should be active. If false, the scripts will not be enqueued.
    public function is_active(): bool {
        return $this->gateway->is_available();
    }

    //  Returns an array of scripts/handles to be registered for this payment method.
    public function get_payment_method_script_handles(): array {
        $script_url = COINSNAP_PLUGIN_URL . 'assets/js/blocks.js';
        $script_asset_path = COINSNAP_PLUGIN_FILE_PATH . 'assets/js/blocks.asset.php';
        $script_asset = file_exists( $script_asset_path ) ? require( $script_asset_path ) : array('dependencies' => array(),'version' => COINSNAP_VERSION);

	wp_register_script(
			'coinsnap-gateway-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
	);

	if ( function_exists('wp_set_script_translations') ) {
			wp_set_script_translations(
				'coinsnap-gateway-blocks',
				'coinsnap-for-woocommerce',
				COINSNAP_PLUGIN_FILE_PATH . 'languages/'
			);
	}

        return [ 'coinsnap-gateway-blocks' ];
    }

    //  Returns an array of key=>value pairs of data made available to the payment methods script.
    public function get_payment_method_data(): array {
        return [
            'title' => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
            'supports' => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
        ];
    }
}

<?php

declare(strict_types=1);

namespace Coinsnap\WC\Admin;

use Coinsnap\Client\StorePaymentMethod;
use Coinsnap\WC\Gateway\SeparateGateways;
use Coinsnap\WC\Helper\CoinsnapApiAuthorization;
use Coinsnap\WC\Helper\CoinsnapApiHelper;
use Coinsnap\WC\Helper\CoinsnapApiWebhook;
use Coinsnap\WC\Helper\Logger;
use Coinsnap\WC\Helper\OrderStates;

//  todo: add validation of host/url
class GlobalSettings extends \WC_Settings_Page {

	public function __construct(){
		$this->id = 'coinsnap_settings';
		$this->label = __( 'Coinsnap Settings', 'coinsnap-for-woocommerce' );
		// Register custom field type order_states with OrderStatesField class.
		add_action('woocommerce_admin_field_coinsnap_order_states', [(new OrderStates()), 'renderOrderStatesHtml']);
                parent::__construct();
	}        

	public function output(): void{
		$settings = $this->get_settings_for_default_section();
		\WC_Admin_Settings::output_fields($settings);
	}

	public function get_settings_for_default_section(): array{
		return $this->getGlobalSettings();
	}

	public function getGlobalSettings(): array{
		Logger::debug('Entering Global Settings form.');
		return [
			'title' => [
				'title' => esc_html_x(
					'Bitcoin & Lightning Server Payments Settings',
					'global_settings',
					'coinsnap-for-woocommerce'
				),
				'type' => 'title',
				'desc' => sprintf(
                                    /* translators: 1: Plugin version 2: PHP Version */
                                    _x( 'This plugin version is %1$s and your PHP version is %2$s. <br/><br/>Coinsnap API requires authentication with an API key. Generate your API key by visiting the <a href="https://app.coinsnap.io/register" target="_blank">Coinsnap registration Page</a>.<br/><br/>Thank you for using Coinsnap!', 'global_settings', 'coinsnap-for-woocommerce' ), 
                                        COINSNAP_VERSION, PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ),
				'id' => 'coinsnap'
			],
                        'store_id' => [
				'title'       => esc_html_x( 'Store ID*', 'global_settings','coinsnap-for-woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => _x( 'Your Coinsnap Store ID. You can find it on the store settings page on your <a href="https://app.coinsnap.io/" target="_blank">Coinsnap account</a>.', 'global_settings', 'coinsnap-for-woocommerce' ),
				//'desc'        => _x( '<a href="#" class="coinsnap-api-key-link">Check connection</a>', 'global_settings', 'coinsnap-for-woocommerce' ),
                                'default'     => '',
                                'custom_attributes' => array(
                                    'required' => 'required'
                                ),
				'id' => 'coinsnap_store_id'
			],
			'api_key' => [
				'title'       => esc_html_x( 'API Key*', 'global_settings','coinsnap-for-woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => _x( 'Your Coinsnap API Key. You can find it on the store settings page on your Coinsnap Server.', 'global_settings', 'coinsnap-for-woocommerce' ),
				'default'     => '',
                                'custom_attributes' => array(
                                    'required' => 'required'
                                ),
				'id' => 'coinsnap_api_key'
			],
			'default_description' => [
				'title'       => esc_html_x('Default Customer Message', 'global_settings', 'coinsnap-for-woocommerce' ),
				'type'        => 'textarea',
				'desc'        => esc_html_x('Message to explain how the customer will be paying for the purchase. Can be overwritten on a per gateway basis.', 'global_settings', 'coinsnap-for-woocommerce' ),
				'default'     => esc_html_x('You will be redirected to the Bitcoin Payment Page to complete your purchase', 'global_settings', 'coinsnap-for-woocommerce'),
				'desc_tip'    => true,
				'id' => 'coinsnap_default_description'
			],
			'order_states' => [
				'type' => 'coinsnap_order_states',
				'id' => 'coinsnap_order_states'
			],
			'customer_data' => [
				'title' => __( 'Send customer data to Coinsnap', 'coinsnap-for-woocommerce' ),
				'type' => 'checkbox',
				'default' => 'no',
				'desc' => _x( 'If you want customer email, address, etc. sent to Coinsnap enable this option. By default for privacy and GDPR reasons this is disabled.', 'global_settings', 'coinsnap-for-woocommerce' ),
				'id' => 'coinsnap_send_customer_data'
			],
			'separate_gateways' => [
				'title' => __( 'Separate Payment Gateways', 'coinsnap-for-woocommerce' ),
				'type' => 'checkbox',
				'default' => 'no',
				'desc' => _x( 'Make all payment methods available as their own payment gateway. It will open new possibilities for every payment methods. ', 'global_settings', 'coinsnap-for-woocommerce' ),
				'id' => 'coinsnap_separate_gateways'
			],
			'sats_mode' => [
				'title' => __( 'Sats-Mode', 'coinsnap-for-woocommerce' ),
				'type' => 'checkbox',
				'default' => 'no',
				'desc' => _x( 'Makes Satoshis/Sats available as currency "SAT" (can be found in WooCommerce->Settings->General)', 'global_settings', 'coinsnap-for-woocommerce' ), // and handles conversion to Bitcoin before creating the invoice on Coinsnap.
				'id' => 'coinsnap_sats_mode'
			],
			'debug' => [
				'title' => __( 'Debug Log', 'coinsnap-for-woocommerce' ),
				'type' => 'checkbox',
				'default' => 'no',
				'desc' => sprintf( 
                                        /* translators: 1: Logs link */
                                        _x( 'Enable logging (<small><a href="%1$s">View Logs</a></small>)', 'global_settings', 'coinsnap-for-woocommerce' ), Logger::getLogFileUrl()),
				'id' => 'coinsnap_debug'
			],
			'sectionend' => [
				'type' => 'sectionend',
				'id' => 'coinsnap',
			],
                    
		];
	}

    /**
    * On saving the settings form make sure to check if the API key works and register a webhook if needed.
    */
    public function save() {
		// If we have url, storeID and apiKey we want to check if the api key works and register a webhook.
		Logger::debug('Saving GlobalSettings.');
		if ( $this->hasNeededApiCredentials() ) {
			// Check if api key works for this store.
			$apiUrl  = COINSNAP_SERVER_URL;
                        $nonce = sanitize_text_field(wp_unslash (filter_input(INPUT_POST,'_wpnonce',FILTER_SANITIZE_STRING)));
			$apiKey  = (wp_verify_nonce($nonce,-1) || filter_input(INPUT_POST,'coinsnap_api_key',FILTER_SANITIZE_STRING ))? filter_input(INPUT_POST,'coinsnap_api_key',FILTER_SANITIZE_STRING ) : '';
			$storeId = (wp_verify_nonce($nonce,-1) || filter_input(INPUT_POST,'coinsnap_store_id',FILTER_SANITIZE_STRING ))? filter_input(INPUT_POST,'coinsnap_store_id',FILTER_SANITIZE_STRING ) : '';
                                    
			try {
                                
                            // Continue creating the webhook if the API key permissions are OK.
                            if ( $apiAuth = CoinsnapApiHelper::checkApiConnection() ){
                                    
                                //  Check if we already have a webhook registered for that store.
				if (CoinsnapApiWebhook::webhookExists( $apiUrl, $apiKey, $storeId )){
                                    $messageReuseWebhook = __( 'Webhook already exists, skipping webhook creation.', 'coinsnap-for-woocommerce' );
                                    Notice::addNotice('info', $messageReuseWebhook, true);
                                    Logger::debug($messageReuseWebhook);
				}
                                else {
				// Register a new webhook.
                                    $webhook = CoinsnapApiWebhook::registerWebhook( $apiUrl, $apiKey, $storeId );
                                    Logger::debug('webhook '.print_r($webhook,true));
                                    
                                
                                //  if webhook is created
                                    if ( $webhook ) {
                                        $messageWebhookSuccess = __( 'Successfully registered a new webhook on Coinsnap Server.', 'coinsnap-for-woocommerce' );
                                        Notice::addNotice('success', $messageWebhookSuccess, true );
                                        Logger::debug( $messageWebhookSuccess );
                                    }
                                    else {
					$messageWebhookError = __( 'Could not register a new webhook on the store.', 'coinsnap-for-woocommerce' );
					Notice::addNotice('error', $messageWebhookError );
					Logger::debug($messageWebhookError, true);
                                    }
				}
                            }
			} 
                        
                        catch ( \Throwable $e ) {
				$messageException = sprintf(
                                    /* translators: 1: Error message */
                                    __( 'Error fetching data for this API key from server. Please check if the key is valid. Error: %1$s', 'coinsnap-for-woocommerce' ),
                                    $e->getMessage()
				);
				Notice::addNotice('error', $messageException );
				Logger::debug($messageException, true);
			}

		} else {
			$messageNotConnecting = 'Did not try to connect to Coinsnap API because one of the required information was missing: API key or Store ID';
			Notice::addNotice('warning', $messageNotConnecting);
			Logger::debug($messageNotConnecting);
		}

		parent::save();

		//  Purge separate payment methods cache.
		//  SeparateGateways::cleanUpGeneratedFilesAndCache();
		CoinsnapApiHelper::clearSupportedPaymentMethodsCache();
    }

    private function hasNeededApiCredentials(): bool {
        $apiKey  = (filter_input(INPUT_POST,'coinsnap_api_key',FILTER_SANITIZE_STRING ))? filter_input(INPUT_POST,'coinsnap_api_key',FILTER_SANITIZE_STRING ) : '';
	$storeId = (filter_input(INPUT_POST,'coinsnap_store_id',FILTER_SANITIZE_STRING ))? filter_input(INPUT_POST,'coinsnap_store_id',FILTER_SANITIZE_STRING ) : '';
        
        if(!empty($apiKey) && !empty($storeId)) {
            return true;
	}
	return false;
    }
}

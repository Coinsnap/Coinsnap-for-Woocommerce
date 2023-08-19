<?php

declare(strict_types=1);

namespace Coinsnap\WC\Admin;

use Coinsnap\Client\ApiKey;
use Coinsnap\Client\StorePaymentMethod;
use Coinsnap\WC\Gateway\SeparateGateways;
use Coinsnap\WC\Helper\CoinsnapApiAuthorization;
use Coinsnap\WC\Helper\CoinsnapApiHelper;
use Coinsnap\WC\Helper\CoinsnapApiWebhook;
use Coinsnap\WC\Helper\Logger;
use Coinsnap\WC\Helper\OrderStates;

//  todo: add validation of host/url
class GlobalSettings extends \WC_Settings_Page {

	public function __construct()
	{
		$this->id = 'coinsnap_settings';
		$this->label = __( 'Coinsnap Settings', 'coinsnap-for-woocommerce' );
		// Register custom field type order_states with OrderStatesField class.
		add_action('woocommerce_admin_field_coinsnap_order_states', [(new OrderStates()), 'renderOrderStatesHtml']);

		if (is_admin()) {
			// Register and include JS.
			//wp_register_script('coinsnap_global_settings', COINSNAP_PLUGIN_URL . 'assets/js/apiKeyRedirect.js', ['jquery'], COINSNAP_VERSION);
			//wp_enqueue_script('coinsnap_global_settings');
			/*wp_localize_script('coinsnap_global_settings',
				'CoinsnapGlobalSettings',
				[
					'url' => admin_url( 'admin-ajax.php' ),
					'apiNonce' => wp_create_nonce( 'coinsnap-api-url-nonce' ),
				]);*/
		}
		parent::__construct();
	}

	public function output(): void
	{
		$settings = $this->get_settings_for_default_section();
		\WC_Admin_Settings::output_fields($settings);
	}

	public function get_settings_for_default_section(): array
	{
		return $this->getGlobalSettings();
	}

	public function getGlobalSettings(): array
	{
		Logger::debug('Entering Global Settings form.');
		return [
			'title' => [
				'title' => esc_html_x(
					'Bitcoin & Lightning Server Payments Settings',
					'global_settings',
					'coinsnap-for-woocommerce'
				),
				'type' => 'title',
				'desc' => sprintf( _x( 'This plugin version is %s and your PHP version is %s. <br/><br/>Coinsnap API requires authentication with an API key. Generate your API key by visiting the <a href="https://app.coinsnap.io/register" target="_blank">Coinsnap registration Page</a>.<br/><br/>Thank you for using Coinsnap!', 'global_settings', 'coinsnap-for-woocommerce' ), COINSNAP_VERSION, PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ),
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
				'desc' => _x( 'Make all payment methods available as their own payment gateway. It will open new possibilities for every payment methods. (<a href="https://docs.btcpayserver.org/FAQ/Integrations/#how-to-configure-additional-token-support-separate-payment-gateways" target="_blank">Full guide</a>)', 'global_settings', 'coinsnap-for-woocommerce' ),
				'id' => 'coinsnap_separate_gateways'
			],
			'sats_mode' => [
				'title' => __( 'Sats-Mode', 'coinsnap-for-woocommerce' ),
				'type' => 'checkbox',
				'default' => 'no',
				'desc' => _x( 'Makes Satoshis/Sats available as currency "SAT" (can be found in WooCommerce->Settings->General)', 'global_settings', 'coinsnap-for-woocommerce' ), // and handles conversion to BTC before creating the invoice on Coinsnap.
				'id' => 'coinsnap_sats_mode'
			],
			'debug' => [
				'title' => __( 'Debug Log', 'coinsnap-for-woocommerce' ),
				'type' => 'checkbox',
				'default' => 'no',
				'desc' => sprintf( _x( 'Enable logging (<small><a href="%s">View Logs</a></small>)', 'global_settings', 'coinsnap-for-woocommerce' ), Logger::getLogFileUrl()),
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
			$apiKey  = sanitize_text_field( $_POST['coinsnap_api_key'] );
			$storeId = sanitize_text_field( $_POST['coinsnap_store_id'] );

			//  todo: fix change of url + key + storeid not leading to recreation of webhook.
			//  Check if the provided API key has the right scope and permissions.
                        //  $serverInfo = CoinsnapApiHelper::getApiConnectionSettings();
                        //  $messageDebug = __( implode(' - ',$serverInfo), 'coinsnap-for-woocommerce' );
                        //  \Coinsnap\WC\Helper\Logger::debug($messageDebug);
                        
                        Logger::debug('API URL: ' . WC()->api_request_url( 'coinsnap' ));
                                    
			try {
				/*

				$apiClient  = new ApiKey( $apiUrl, $apiKey );
				$apiKeyData = $apiClient->getCurrent();
				new CoinsnapApiAuthorization($apiKeyData->getData());
				$apiAuth    = CoinsnapApiHelper::checkApiConnection();
                                
                                if ( ! $apiAuth->hasSingleStore() ) {
					$messageSingleStore = __( 'The provided API key scope is valid for multiple stores, please make sure to create one for a single store.', 'coinsnap-for-woocommerce' );
					Notice::addNotice('error', $messageSingleStore );
					Logger::debug($messageSingleStore, true);
					$hasError = true;
				}

				if ( ! $apiAuth->hasRequiredPermissions() ) {
					$messagePermissionsError = sprintf(
						__( 'The provided API key does not match the required permissions. Please make sure the following permissions are are given: %s', 'coinsnap-for-woocommerce' ),
						implode( ', ', CoinsnapApiAuthorization::REQUIRED_PERMISSIONS )
					);
					Notice::addNotice('error', $messagePermissionsError );
					Logger::debug( $messagePermissionsError, true );
					$hasError = true;
				}
                                
                                $hasError   = false;
                                
				// Check server info and sync status.
                                
				if ($serverInfo = CoinsnapApiHelper::getApiConnectionSettings()) {
				$serverinfo_string = "";
                                foreach($serverInfo as $key => $value) $serverinfo_string .= "$key - ";
                                    Logger::debug( 'Serverinfo: ' . $serverinfo_string, true );

					// Show/log notice if the node is not fully synced yet and no invoice creation is possible.
					if ((int) $serverInfo->getData()['fullySynched'] !== 1 ) {
						$messageNotSynched = __( 'Your Coinsnap Server is not fully synched yet. Until fully synched the checkout will not work.', 'coinsnap-for-woocommerce' );
						Notice::addNotice('error', $messageNotSynched, false);
						Logger::debug($messageNotSynched);
					}

					// Show a notice if the Coinsnap Server version does not work with refunds.
					// This needs the coinsnap.store.cancreatenonapprovedpullpayments permission which was introduced with
					// Coinsnap Server v1.7.6
					if (version_compare($serverInfo->getVersion(), '1.7.6', '<')) {
						$messageRefundsSupport = __( 'Your Coinsnap Server version does not support refunds, please update to at least version 1.7.6 or newer.', 'coinsnap-for-woocommerce' );
						Notice::addNotice('error', $messageRefundsSupport, false);
						Logger::debug($messageRefundsSupport);
					} else {
						// Check if the configured api key has refunds permission; show notice if not.
						if (!$apiAuth->hasRefundsPermission()) {
							$messageRefundsPermissionMissing = __( 'Your api key does not support refunds, if you want to use that feature you need to create a new API key with permission. See our guide <a href="https://docs.btcpayserver.org/WooCommerce/#create-a-new-api-key" target="_blank" rel="noreferrer">here</a>.', 'coinsnap-for-woocommerce' );
							Notice::addNotice('info', $messageRefundsPermissionMissing, true);
							Logger::debug($messageRefundsPermissionMissing);
						}
					}
				}
                                //false === $hasError
                                */
                                
                            // Continue creating the webhook if the API key permissions are OK.
                            if ( $apiAuth = CoinsnapApiHelper::checkApiConnection() ){
                                    
                                    //  $webhooks_array = CoinsnapApiWebhook::getWebhooks($storeId);
                                    //  $messageWebhooksArray = 'API URL: ' . WC()->api_request_url( 'coinsnap' ). ' Webhook:' . __( print_r($webhooks_array,true), 'coinsnap-for-woocommerce' );
                                    //  Logger::debug($messageWebhooksArray);
                                    //  deleteWebhook(string $storeId, string $webhookId)
                                
                                //  Check if we already have a webhook registered for that store.
				if (CoinsnapApiWebhook::webhookExists( $apiUrl, $apiKey, $storeId )){
                                    $messageReuseWebhook = __( 'Webhook already exists, skipping webhook creation.', 'coinsnap-for-woocommerce' );
                                    Notice::addNotice('info', $messageReuseWebhook, true);
                                    Logger::debug($messageReuseWebhook);
				}
                                else {
				// Register a new webhook.
                                    $webhook = CoinsnapApiWebhook::registerWebhook( $apiUrl, $apiKey, $storeId );
                                    $messageWebhook = __( print_r($webhook,true), 'coinsnap-for-woocommerce' );
                                    Logger::debug($messageWebhook);
                                
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

					// Make sure there is at least one payment method configured - DOESN'T EXIST ON COINSNAP SERVER
                                        /*
					try {
						$pmClient = new StorePaymentMethod( $apiUrl, $apiKey );
						if (($pmClient->getPaymentMethods($storeId)) === []) {
							$messagePaymentMethodsError = __( 'No wallet configured on your Coinsnap Server store settings. Make sure to add at least one otherwise this plugin will not work.', 'coinsnap-for-woocommerce' );
							Notice::addNotice('error', $messagePaymentMethodsError );
							Logger::debug($messagePaymentMethodsError, true);
						}
					} catch (\Throwable $e) {
						$messagePaymentMethodsCallError = sprintf(
							__('Exception loading wallet information (payment methods) from Coinsnap Server: %s.', 'coinsnap-for-woocommerce'),
							$e->getMessage()
						);
						Logger::debug($messagePaymentMethodsCallError);
						Notice::addNotice('error', $messagePaymentMethodsCallError );
					}
                                        */
				}
			} 
                        
                        catch ( \Throwable $e ) {
				$messageException = sprintf(
					__( 'Error fetching data for this API key from server. Please check if the key is valid. Error: %s', 'coinsnap-for-woocommerce' ),
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
        if(!empty($_POST['coinsnap_api_key']) && !empty($_POST['coinsnap_store_id'])) {
            return true;
	}
	return false;
    }
}

<?php
declare(strict_types=1);
namespace Coinsnap\WC\Admin;
defined( 'ABSPATH' ) || exit();

use Coinsnap\Client\StorePaymentMethod;
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
        add_action('woocommerce_admin_field_custom_markup', [$this, 'coinsnap_output_custom_markup_field']);
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
                'title' => esc_html_x('Bitcoin & Lightning Server Settings','global_settings','coinsnap-for-woocommerce'),
		'type' => 'title',
		'desc' => sprintf(
                /* translators: 1: Plugin version 2: PHP Version */
                _x( '<div id="coinsnapConnectionStatus"></div><p>This plugin version is %1$s and your PHP version is %2$s. <br/><br/>Thank you for using Coinsnap!</p>', 'global_settings', 'coinsnap-for-woocommerce' ), COINSNAP_WC_VERSION, PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ),
		'id' => 'coinsnap'
            ],
            
            'provider' => [
                'title'     => esc_html_x( 'Payment provider', 'global_settings','coinsnap-for-woocommerce' ),
		'type'      => 'select',
                'options'   => [
                    'coinsnap'  => 'Coinsnap',
                    'btcpay'    => 'BTCPay Server'
                ],
                'id' => 'coinsnap_provider',
            ],
                    
            'store_id' => [
		'title'       => esc_html_x( 'Store ID*', 'global_settings','coinsnap-for-woocommerce' ),
		'type'        => 'text',
		'desc_tip'    => _x( 'Your Coinsnap Store ID. You can find it on the store settings page on your <a href="https://app.coinsnap.io/" target="_blank">Coinsnap account</a>.', 'global_settings', 'coinsnap-for-woocommerce' ),
		//'desc'        => _x( '<a href="#" class="coinsnap-api-key-link">Check connection</a>', 'global_settings', 'coinsnap-for-woocommerce' ),
                'default'     => '',
                'custom_attributes' => ['required' => 'required'],
		'id' => 'coinsnap_store_id',
                'class' => 'coinsnap required'
            ],

            'api_key' => [
                'title'       => esc_html_x( 'API Key*', 'global_settings','coinsnap-for-woocommerce' ),
                'type'        => 'text',
                'desc_tip'    => _x( 'Your Coinsnap API Key. You can find it on the store settings page on your Coinsnap Server.', 'global_settings', 'coinsnap-for-woocommerce' ),
		'desc'        => _x('Coinsnap API requires authentication with an API key.<br/>Generate your API key by visiting the <a href="https://app.coinsnap.io/register" target="_blank">Coinsnap registration Page</a>.', 'global_settings', 'coinsnap-for-woocommerce'),
                'default'     => '',
                'custom_attributes' => ['required' => 'required'],
		'id' => 'coinsnap_api_key',
                'class' => 'coinsnap required'
            ],
                    
            'btcpay_server_url' => [
		'title'       => esc_html_x( 'BTCPay server URL*', 'global_settings','coinsnap-for-woocommerce' ),
		'type'        => 'text',
		'desc_tip'    => _x( 'Your BTCPay server URL.', 'global_settings', 'coinsnap-for-woocommerce' ),
		'desc'        => _x( '<a href="#" class="btcpay-apikey-link">Check connection</a>', 'global_settings', 'coinsnap-for-woocommerce' ),
                'default'     => '',
                'custom_attributes' => ['required' => 'required'],
		'id' => 'btcpay_server_url',
                'class' => 'btcpay required'
            ],
            
            'btcpay_store_wizard' => [
                'title'       => esc_html_x( 'Setup wizard', 'global_settings','coinsnap-for-woocommerce' ),
		'type'  => 'custom_markup',
		'markup'  => '<button class="button button-primary btcpay btcpay-apikey-link" type="button" target="_blank">Generate API key</button>',
		'id'    => 'btcpay_wizard_button'
            ],
			
            
            'btcpay_store_id' => [
                'title'       => esc_html_x( 'Store ID*', 'global_settings','coinsnap-for-woocommerce' ),
		'type'        => 'text',
		'desc_tip'    => _x( 'Your BTCPay Store ID. You can find it on the store settings page on your BTCPay Server.', 'global_settings', 'coinsnap-for-woocommerce' ),
		//'desc'        => _x( '<a href="#" class="coinsnap-api-key-link">Check connection</a>', 'global_settings', 'coinsnap-for-woocommerce' ),
                'default'     => '',
                'custom_attributes' => ['required' => 'required'],
		'id' => 'btcpay_store_id',
                'class' => 'btcpay required'
            ],
            
            'btcpay_api_key' => [
                'title'       => esc_html_x( 'API Key*', 'global_settings','coinsnap-for-woocommerce' ),
                'type'        => 'text',
		'desc_tip'    => _x( 'Your BTCPay server API Key. You can generate it in your BTCPay Server.', 'global_settings', 'coinsnap-for-woocommerce' ),
		'default'     => '',
                'custom_attributes' => ['required' => 'required'],
		'id' => 'btcpay_api_key',
                'class' => 'btcpay required'
            ],
            
            'default_description' => [
		'title'       => esc_html_x('Default Customer Message','global_settings','coinsnap-for-woocommerce' ),
		'type'        => 'textarea',
		'desc'        => esc_html_x('Message to explain how the customer will be paying for the purchase. Can be overwritten on a per gateway basis.', 'global_settings', 'coinsnap-for-woocommerce' ),
		'default'     => esc_html_x('You will be redirected to the Bitcoin-Lightning Payment Page to complete your purchase','global_settings','coinsnap-for-woocommerce'),
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
            
            'sats_mode' => [
                'title' => __( 'Sats-Mode', 'coinsnap-for-woocommerce' ),
		'type' => 'checkbox',
		'default' => 'no',
		'desc' => _x( 'Makes Satoshis/Sats available as currency "SAT" (can be found in WooCommerce->Settings->General) and handles conversion to Bitcoin before creating the invoice on BTCPay server', 'global_settings', 'coinsnap-for-woocommerce' ),
		'id' => 'coinsnap_sats_mode'
            ],
            
            'connection_status_display' => [
                'title'     => esc_html_x( 'Display connection status', 'global_settings','coinsnap-for-woocommerce' ),
		'type'      => 'select',
                'options'   => [
                    'settingspage' => __( 'On settings page only', 'coinsnap-for-woocommerce' ),
                    'hideable' => __( 'Can be hidden from Admin pages', 'coinsnap-for-woocommerce' ),
                    'everywhere'  => __( 'On all Admin pages', 'coinsnap-for-woocommerce' )
                ],
                'id' => 'coinsnap_connection_status_display',
            ],
                    
            'autoredirect' => [
                'title' => __( 'Redirect after payment', 'coinsnap-for-woocommerce' ),
		'type' => 'checkbox',
		'default' => 'yes',
		'desc' => _x( 'Redirect to Thank You page after payment automatically', 'global_settings', 'coinsnap-for-woocommerce' ),
		'id' => 'coinsnap_autoredirect'
            ],
            
            'debug' => [
		'title' => __( 'Debug Log', 'coinsnap-for-woocommerce' ),
		'type' => 'checkbox',
		'default' => 'no',
		'desc' => sprintf( 
                    /* translators: 1: Logs link */
                    _x( 'Enable logging (<small><a href="%1$s" target="_blank">View Logs</a></small>)', 'global_settings', 'coinsnap-for-woocommerce' ), Logger::getLogFileUrl()),
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
        
        $_nonce = filter_input(INPUT_POST,'_wpnonce',FILTER_SANITIZE_STRING);
        
        if( wp_verify_nonce($_nonce,'woocommerce-settings')){
            Logger::debug('Saving GlobalSettings');
            
            $coinsnap_provider = filter_input(INPUT_POST,'coinsnap_provider',FILTER_SANITIZE_STRING );
            $apiUrl  = ($coinsnap_provider !== 'btcpay')? COINSNAP_SERVER_URL : filter_input(INPUT_POST,'btcpay_server_url',FILTER_SANITIZE_STRING );
            $storeId = ($coinsnap_provider !== 'btcpay')? 
                sanitize_text_field(filter_input(INPUT_POST,'coinsnap_store_id',FILTER_SANITIZE_STRING )) : 
                sanitize_text_field(filter_input(INPUT_POST,'btcpay_store_id',FILTER_SANITIZE_STRING ));
            $apiKey  = ($coinsnap_provider !== 'btcpay')? 
                sanitize_text_field(filter_input(INPUT_POST,'coinsnap_api_key',FILTER_SANITIZE_STRING )) : 
                sanitize_text_field(filter_input(INPUT_POST,'btcpay_api_key',FILTER_SANITIZE_STRING ));
            
            // If we have url, storeID and apiKey we want to check if the api key works and register a webhook.
            if ( $this->hasNeededApiCredentials($apiUrl,$storeId,$apiKey) ) {
                
                try {        
                    
                    // Continue creating the webhook if the API key permissions are OK.
                    if ( $apiAuth = CoinsnapApiHelper::checkApiConnection($apiUrl,$storeId,$apiKey) ){
                        
                        //  Check if we already have a webhook registered for that store.
			if (CoinsnapApiWebhook::webhookExists( $apiUrl, $apiKey, $storeId )){
                            $messageReuseWebhook = __( 'Webhook already exists, skipping webhook creation.', 'coinsnap-for-woocommerce' );
                            Notice::addNotice('info', $messageReuseWebhook, true);
                            Logger::debug($messageReuseWebhook);
			}
                        else {
                            // Register a new webhook.
                            $webhook = CoinsnapApiWebhook::registerWebhook($apiUrl, $apiKey, $storeId);
                            Logger::debug('webhook '.wp_json_encode($webhook));
                                    
                                
                            //  if webhook is created
                            if ( $webhook ) {
                                $messageWebhookSuccess = ($coinsnap_provider !== 'btcpay')? 
                                    __( 'Successfully registered a new webhook on Coinsnap Server.','coinsnap-for-woocommerce' ) :
                                    __( 'Successfully registered a new webhook on BTCPay Server.','coinsnap-for-woocommerce' );
                                Notice::addNotice('success', $messageWebhookSuccess, true );
                                Logger::debug( $messageWebhookSuccess );
                            }
                            else {
				$messageWebhookError = __( 'Could not register a new webhook on the store.','coinsnap-for-woocommerce' );
				Notice::addNotice('error', $messageWebhookError );
				Logger::debug($messageWebhookError, true);
                            }
			}
                    }
                    else {
                        $messageConnectionError = ($coinsnap_provider !== 'btcpay')? __( 'Coinsnap connection error.', 'coinsnap-for-woocommerce' ) : __( 'BTCPay connection error.', 'coinsnap-for-woocommerce' );
                            
			Notice::addNotice('error', $messageConnectionError );
			Logger::debug($messageConnectionError, true);
                    }
                } 
                        
                catch ( \Throwable $e ) {
                    $messageException = sprintf(
                        /* translators: 1: Error message */
                        __( 'Error fetching data for this API key from server. Please check if the key is valid. Error: %1$s', 'coinsnap-for-woocommerce' ),$e->getMessage());
                    Notice::addNotice('error', $messageException );
                    Logger::debug($messageException, true);
		}
            }
        }
        else {
            Logger::debug('Saving GlobalSettings: nonce error');
        }

        parent::save();
        CoinsnapApiHelper::clearSupportedPaymentMethodsCache();
    }
    
    private function hasNeededApiCredentials($apiUrl = '',$storeId = '',$apiKey = ''): bool {
        
        $hasCredentials = true;
        
        if(empty($apiUrl)){
            $messageNotConnecting = __('Did not try to connect to API because Server URL parameter was missing','coinsnap-for-woocommerce');
            Notice::addNotice('warning', $messageNotConnecting);
            Logger::debug($messageNotConnecting);
            $hasCredentials = false;
        }
            
        if(empty($storeId)){
            $messageNotConnecting = __('Did not try to connect to API because Store Id parameter was missing','coinsnap-for-woocommerce');
            Notice::addNotice('warning', $messageNotConnecting);
            Logger::debug($messageNotConnecting);
            $hasCredentials = false;
        }
            
        if(empty($apiKey)){
            $messageNotConnecting = __('Did not try to connect to API because API Key parameter was missing','coinsnap-for-woocommerce');
            Notice::addNotice('warning', $messageNotConnecting);
            Logger::debug($messageNotConnecting);
            $hasCredentials = false;
        }
        
        return $hasCredentials;
		
    }
    
    public function coinsnap_output_custom_markup_field($value) {
        //$_provider = get_option('coinsnap_provider');
        //if($_provider === 'btcpay'){
            echo '<tr valign="top">';
            echo (!empty($value['title']))? '<th scope="row" class="titledesc">' . esc_html($value['title']) . '</th>' : '<th scope="row" class="titledesc">&nbsp;</th>';
            echo '<td class="forminp" id="'.esc_html($value['id']).'">'.wp_kses($value['markup'],['button' => array('class' => true,'id' => true,'target' => true)]).'</td>';
            echo '</tr>';
        //}
    }
}

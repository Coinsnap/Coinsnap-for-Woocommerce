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
	$this->label = __( 'Coinsnap', 'coinsnap-for-woocommerce' );
	
        // Register custom field type order_states with OrderStatesField class.
	add_action('woocommerce_admin_field_coinsnap_order_states', [(new OrderStates()), 'renderOrderStatesHtml']);
        add_action('woocommerce_admin_field_custom_markup', [$this, 'coinsnap_output_custom_markup_field']);
        add_action('woocommerce_admin_field_coinsnap_secret', [$this, 'outputSecretField']);
        foreach (['coinsnap_api_key', 'btcpay_api_key'] as $secretOption) {
            add_filter('woocommerce_admin_settings_sanitize_option_' . $secretOption, [$this, 'keepStoredSecret'], 10, 3);
        }
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
                'type'        => 'coinsnap_secret',
                'desc_tip'    => _x( 'Your Coinsnap API Key. You can find it on the store settings page on your Coinsnap Server.', 'global_settings', 'coinsnap-for-woocommerce' ),
		'desc'        => _x('Coinsnap API requires authentication with an API key.<br/>Generate your API key by visiting the <a href="https://app.coinsnap.io/register" target="_blank">Coinsnap registration Page</a>.', 'global_settings', 'coinsnap-for-woocommerce'),
                'default'     => '',
		'id' => 'coinsnap_api_key',
                'class' => 'coinsnap'
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
                'type'        => 'coinsnap_secret',
		'desc_tip'    => _x( 'Your BTCPay server API Key. You can generate it in your BTCPay Server.', 'global_settings', 'coinsnap-for-woocommerce' ),
		'default'     => '',
		'id' => 'btcpay_api_key',
                'class' => 'btcpay'
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
            
            'returnurl' => [
                'title' => __( 'Return URL after payment', 'coinsnap-for-woocommerce' ),
		'type' => 'text',
		'default' => '',
		'desc' => _x( 'Custom return URL after successful payment (default URL if blank)', 'global_settings', 'coinsnap-for-woocommerce' ),
		'id' => 'coinsnap_returnurl'
                
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
    private $previousBtcpayUrl = null;
    private $rejectBtcpayKey = false;

    public function save() {
        $this->previousBtcpayUrl = (string) get_option('btcpay_server_url', '');

        $_nonce = filter_input(INPUT_POST,'_wpnonce',FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        if( wp_verify_nonce($_nonce,'woocommerce-settings')){
            Logger::debug('Saving GlobalSettings');
            
            $coinsnap_provider = filter_input(INPUT_POST,'coinsnap_provider',FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $apiUrl  = ($coinsnap_provider !== 'btcpay')? COINSNAP_SERVER_URL : filter_input(INPUT_POST,'btcpay_server_url',FILTER_SANITIZE_URL );
            $storeId = ($coinsnap_provider !== 'btcpay')? 
                sanitize_text_field(filter_input(INPUT_POST,'coinsnap_store_id',FILTER_SANITIZE_FULL_SPECIAL_CHARS )) : 
                sanitize_text_field(filter_input(INPUT_POST,'btcpay_store_id',FILTER_SANITIZE_FULL_SPECIAL_CHARS ));
            $apiKey  = ($coinsnap_provider !== 'btcpay')? 
                sanitize_text_field(filter_input(INPUT_POST,'coinsnap_api_key',FILTER_SANITIZE_FULL_SPECIAL_CHARS )) : 
                sanitize_text_field(filter_input(INPUT_POST,'btcpay_api_key',FILTER_SANITIZE_FULL_SPECIAL_CHARS ));
            // A stored key is reused only for the server it was issued for.
            if ($apiKey === '' && ($coinsnap_provider !== 'btcpay' || $apiUrl === $this->previousBtcpayUrl)) {
                $apiKey = (string) get_option(($coinsnap_provider !== 'btcpay') ? 'coinsnap_api_key' : 'btcpay_api_key', '');
            }
            
            // If we have url, storeID and apiKey we want to check if the api key works and register a webhook.
            if ( $this->hasNeededApiCredentials($apiUrl,$storeId,$apiKey) ) {
                
                try {        
                    
                    // A BTCPay key must be limited to this one store (no unrestricted or server rights), as reported by BTCPay itself.
                    if ( $coinsnap_provider === 'btcpay' && !$this->btcpayKeyIsAcceptable((string) $apiUrl, $apiKey, $storeId) ) {
                        $this->rejectBtcpayKey = true;
                        Notice::addNotice('error', __( 'This BTCPay API key was not saved: it must be limited to this store and have only store permissions. Please use the "Generate API key" button, or create a key in BTCPay with only the required permissions for this store.', 'coinsnap-for-woocommerce' ));
                        Logger::debug('Saving GlobalSettings: BTCPay API key refused (permissions not limited to the configured store)', true);
                    }
                    // Continue creating the webhook if the API key permissions are OK.
                    elseif ( $apiAuth = CoinsnapApiHelper::checkApiConnection($apiUrl,$storeId,$apiKey) ){
                        
                        //  Check if we already have a webhook registered for that store.
			if (CoinsnapApiWebhook::webhookExists( $apiUrl, $apiKey, $storeId )){
                            $messageReuseWebhook = __( 'Webhook already exists, skipping webhook creation for store ', 'coinsnap-for-woocommerce' ).$storeId;
                            Notice::addNotice('info', $messageReuseWebhook, true);
                            Logger::debug($messageReuseWebhook);
			}
                        else {
                            // Register a new webhook.
                            $webhook = CoinsnapApiWebhook::registerWebhook($apiUrl, $apiKey, $storeId);
                            // The webhook carries its secret; only the id goes to the log.
                            Logger::debug('Webhook registered: ' . ($webhook ? (string) ($webhook->getData()['id'] ?? '') : 'failed'));
                                    
                                
                            //  if webhook is created
                            if ( $webhook ) {
                                $messageWebhookSuccess = ($coinsnap_provider !== 'btcpay')? 
                                    __( 'Successfully registered a new webhook for store on Coinsnap Server.','coinsnap-for-woocommerce' ) :
                                    __( 'Successfully registered a new webhook for store on the server ','coinsnap-for-woocommerce' ).$apiUrl;
                                Notice::addNotice('success', $messageWebhookSuccess, true );
                                Logger::debug( $messageWebhookSuccess );
                            }
                            else {
				$messageWebhookError = __( 'Could not register a new webhook on the store ','coinsnap-for-woocommerce' ).$storeId;
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
                    // Show a generic message; the raw API error (URL, response body) goes to the log only.
                    Notice::addNotice('error', __( 'Could not connect with this API key. Please check the server URL, store ID and API key.', 'coinsnap-for-woocommerce' ) );
                    Logger::debug('Saving GlobalSettings: API error: ' . $e->getMessage(), true);
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
    
    // Never prints a stored key; shows only its last 4 characters as a hint.
    public function outputSecretField($value) {
        $stored = (string) get_option($value['id'], '');
        $hint = ($stored === '') ? __('Paste your API key', 'coinsnap-for-woocommerce') : '••••••••' . substr($stored, -4);
        echo '<tr valign="top"><th scope="row" class="titledesc"><label for="' . esc_attr($value['id']) . '">' . esc_html($value['title']) . '</label></th>';
        echo '<td class="forminp"><input type="password" name="' . esc_attr($value['id']) . '" id="' . esc_attr($value['id']) . '" value="" autocomplete="new-password" class="' . esc_attr($value['class'] ?? '') . '" placeholder="' . esc_attr($hint) . '" />';
        if (!empty($value['desc'])) {
            echo '<p class="description">' . wp_kses_post($value['desc']) . '</p>';
        }
        echo '</td></tr>';
    }

    // An empty secret field keeps the stored value (returning null tells WooCommerce not to save it).
    // A changed BTCPay URL clears the stored key separately (BTCPayApiAuthorization::onServerUrlChange).
    public function keepStoredSecret($value, $option, $rawValue) {
        if (($option['id'] ?? '') === 'btcpay_api_key' && $this->rejectBtcpayKey) {
            return null;
        }
        return (is_string($rawValue) && trim($rawValue) !== '') ? $value : null;
    }

    private function btcpayKeyIsAcceptable(string $apiUrl, string $apiKey, string $storeId): bool {
        $auth = \Coinsnap\Client\BTCPayApiAuthorization::fromServer($apiUrl, $apiKey);
        return $auth !== null && $auth->hasRequiredPermissions() && $auth->getStoreID() === $storeId;
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

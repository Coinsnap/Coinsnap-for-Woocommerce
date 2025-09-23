<?php
/**
 * Plugin Name:     Bitcoin payment for WooCommerce
 * Plugin URI:      https://coinsnap.io/coinsnap-for-woocommerce-plugin/
 * Description:     With Coinsnap payment processing, you can accept Bitcoin and Lightning payments on your website or online store.
 * Author:          Coinsnap
 * Author URI:      https://coinsnap.io/
 * Text Domain:     coinsnap-for-woocommerce
 * Domain Path:     /languages
 * Version:         1.6.0
 * Requires PHP:    7.4
 * Tested up to:    6.8
 * Requires at least: 6.0
 * Requires Plugins: woocommerce
 * WC requires at least: 6.0
 * WC tested up to: 10.1.2
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:         true
 */

use Coinsnap\WC\Admin\Notice;
use Coinsnap\WC\Gateway\DefaultGateway;
use Coinsnap\WC\Helper\SatsMode;
use Coinsnap\WC\Helper\CoinsnapApiHelper;
use Coinsnap\WC\Helper\CoinsnapApiWebhook;
use Coinsnap\WC\Helper\BTCPayApiAuthorization;
use Coinsnap\WC\Helper\Logger;

defined( 'ABSPATH' ) || exit();
if(!defined('COINSNAP_WC_PHP_VERSION')){define( 'COINSNAP_WC_PHP_VERSION', '7.4' );}
if(!defined('COINSNAP_WC_VERSION')){define( 'COINSNAP_WC_VERSION', '1.6.0' );}
if(!defined('COINSNAP_VERSION_KEY')){define( 'COINSNAP_VERSION_KEY', 'coinsnap_version' );}
if(!defined('COINSNAP_PLUGIN_FILE_PATH')){define( 'COINSNAP_PLUGIN_FILE_PATH', plugin_dir_path( __FILE__ ) );}
if(!defined('COINSNAP_PLUGIN_URL')){define( 'COINSNAP_PLUGIN_URL', plugin_dir_url(__FILE__ ) );}
if(!defined('COINSNAP_WC_PLUGIN_ID')){define( 'COINSNAP_WC_PLUGIN_ID', 'coinsnap-for-woocommerce' );}
if(!defined('COINSNAP_SERVER_URL')){define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );}
if(!defined('COINSNAP_API_PATH')){define( 'COINSNAP_API_PATH', '/api/v1/');}
if(!defined('COINSNAP_SERVER_PATH')){define( 'COINSNAP_SERVER_PATH', 'stores' );}
if(!defined('COINSNAP_WC_REFERRAL_CODE')){define( 'COINSNAP_WC_REFERRAL_CODE', 'DEV1e1ea54fedd507e2f447e2963' );}
if(!defined('COINSNAP_CURRENCIES')){define( 'COINSNAP_CURRENCIES', array("EUR","USD","SATS","BTC","CAD","JPY","GBP","CHF","RUB") );}

class CoinsnapWCPlugin {
    
    private static $instance;

    public function __construct() {
	$this->includes();
        add_action('woocommerce_thankyou_coinsnap', [$this, 'orderStatusThankYouPage'], 10, 1);

	// Run the updates.
	\Coinsnap\WC\Helper\UpdateManager::processUpdates();

	if (is_admin()) {
            
            add_action( 'admin_enqueue_scripts', [ $this, 'connectionScriptsLoader' ] );
            add_action( 'wp_ajax_coinsnap_connection_handler', [$this, 'coinsnapConnectionHandler'] );
            add_action( 'wp_ajax_btcpay_server_apiurl_handler', [$this, 'btcpayApiUrlHandler'] );
            
            // Register our custom global settings page.
            add_filter(
                'woocommerce_get_settings_pages',
                function ($settings) {
                    $settings[] = new \Coinsnap\WC\Admin\GlobalSettings();
                    return $settings;
                }
            );

            $this->dependenciesNotification();
            //$this->legacyPluginNotification(); // Not ready
            $this->notConfiguredNotification();
	}
        
        
        add_action( 'wp_ajax_coinsnap_checkout', [$this, 'coinsnapCheckoutHandler'] );
        add_action( 'wp_ajax_nopriv_coinsnap_checkout', [$this, 'coinsnapCheckoutHandler'] );
        
    }
    
    public function coinsnapCheckoutHandler(){
        $_nonce = filter_input(INPUT_POST,'_wpnonce',FILTER_SANITIZE_STRING);
        if ( !wp_verify_nonce( $_nonce, 'coinsnap-ajax-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
        }
            
        $gateway = new DefaultGateway;
        $discount = $gateway->getDiscount();
            
        $response = ['result'=>true,'message'=>$discount];
        $this->sendJsonResponse($response);
    }
    
    public function connectionScriptsLoader(){
        wp_register_style('coinsnap-backend-style', plugins_url('assets/css/coinsnap-backend-style.css',__FILE__),array(),COINSNAP_WC_VERSION);
        wp_enqueue_style('coinsnap-backend-style');
        wp_enqueue_script('coinsnap-admin-fields',plugin_dir_url( __FILE__ ) . 'assets/js/adminFields.js',[ 'jquery' ],COINSNAP_WC_VERSION,true);
        wp_enqueue_script('coinsnap-connection-check',plugin_dir_url( __FILE__ ) . 'assets/js/connectionCheck.js',[ 'jquery' ],COINSNAP_WC_VERSION,true);
        
        wp_localize_script('coinsnap-connection-check', 'coinsnap_ajax', array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'nonce'  => wp_create_nonce( 'coinsnap-ajax-nonce' ),
        ));
    }
    
    public function coinsnapConnectionHandler(){
        
        $_nonce = filter_input(INPUT_POST,'_wpnonce',FILTER_SANITIZE_STRING);
        $apiHelper = new CoinsnapApiHelper();
        
        if(empty($apiHelper->url) || empty($apiHelper->apiKey)){
            $response = [
                    'result' => false,
                    'message' => __('WooCommerce: empty gateway URL or API Key', 'coinsnap-for-woocommerce')
            ];
            $this->sendJsonResponse($response);
        }
        
        $_provider = get_option('coinsnap_provider');
        $client = new \Coinsnap\Client\Invoice($apiHelper->url,$apiHelper->apiKey);
        $currency = strtoupper(get_option( 'woocommerce_currency' ));
        
        if($_provider === 'btcpay'){
            try {
                $store = new \Coinsnap\Client\Store($apiHelper->url,$apiHelper->apiKey);            
                $storePaymentMethods = $store->getStorePaymentMethods($apiHelper->storeId);

                if ($storePaymentMethods['code'] === 200) {
                    if($storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning'] && !$storePaymentMethods['result']['usdt']){
                        $checkInvoice = $client->checkPaymentData(0,$currency,'bitcoin','calculation');
                    }
                    elseif($storePaymentMethods['result']['lightning'] || $storePaymentMethods['result']['usdt']){
                        $checkInvoice = $client->checkPaymentData(0,$currency,'lightning','calculation');
                    }
                }
            }
            catch (Exception $e) {
                Logger::debug($e->getMessage());
                $response = [
                        'result' => false,
                        'message' => __('WooCommerce: Error store loading. Wrong or empty Store ID', 'coinsnap-for-woocommerce')
                ];
                $this->sendJsonResponse($response);
            }
        }
        else {
            $checkInvoice = $client->checkPaymentData(0,$currency,'coinsnap','calculation');
        }
        
        if(isset($checkInvoice) && $checkInvoice['result']){
            $connectionData = __('Min order amount is', 'coinsnap-for-woocommerce') .' '. $checkInvoice['min_value'].' '.$currency;
        }
        else {
            $connectionData = __('No payment method is configured', 'coinsnap-for-woocommerce');
        }
        
        $_message_disconnected = ($_provider !== 'btcpay')? 
            __('WooCommerce: Coinsnap server is disconnected', 'coinsnap-for-woocommerce') :
            __('WooCommerce: BTCPay server is disconnected', 'coinsnap-for-woocommerce');
        $_message_connected = ($_provider !== 'btcpay')?
            __('WooCommerce: Coinsnap server is connected', 'coinsnap-for-woocommerce') : 
            __('WooCommerce: BTCPay server is connected', 'coinsnap-for-woocommerce');
        
        if( wp_verify_nonce($_nonce,'coinsnap-ajax-nonce') ){
            $response = ['result' => false,'message' => $_message_disconnected,'display' => get_option('coinsnap_connection_status_display')];

            try {
                if (!CoinsnapApiHelper::checkApiConnection()) {
                    Logger::debug('API connection is not established');
                    $this->sendJsonResponse($response);
                }
                
                $webhookExists = CoinsnapApiWebhook::webhookExists($apiHelper->url,$apiHelper->apiKey,$apiHelper->storeId);

                if($webhookExists) {
                    $response = ['result' => true,'message' => $_message_connected.' ('.$connectionData.')','display' => get_option('coinsnap_connection_status_display')];
                    $this->sendJsonResponse($response);
                }

                $webhook = CoinsnapApiWebhook::registerWebhook($apiHelper->url,$apiHelper->apiKey,$apiHelper->storeId);
                $response['result'] = (bool)$webhook;
                $response['message'] = $webhook ? $_message_connected.' ('.$connectionData.')' : $_message_disconnected.' (Webhook)';
                $response['display'] = get_option('coinsnap_connection_status_display');
            }
            catch (Exception $e) {
                $response['message'] =  __('WooCommerce: API connection is not established', 'coinsnap-for-woocommerce');
                Logger::debug($e->getMessage());
            }

            $this->sendJsonResponse($response);
        }      
    }

    private function sendJsonResponse(array $response): void {
        echo wp_json_encode($response);
        exit();
    }
        

    public function includes(): void {
	
        $autoloader = COINSNAP_PLUGIN_FILE_PATH . 'library/loader.php';
	if (file_exists($autoloader)) {
            // @noinspection PhpIncludeInspection 
            require_once $autoloader;
	}

	// Make sure WP internal functions are available.
	if (!function_exists('is_plugin_active') ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Setup other dependencies.
        
        // Delete non-supported currencies
        add_filter('woocommerce_currencies',[$this, 'currenciesFilter']);
        
         
        // Make SAT / Sats as currency available.        
	if (get_option('coinsnap_sats_mode') === 'yes') {
            SatsMode::instance();
	}
    }
    
    public function currenciesFilter($currencies){
        
        $_provider = get_option('coinsnap_provider');
        if($_provider !== 'coinsnap'){
            return $currencies;
        }
        
        $apiHelper = new CoinsnapApiHelper();
        $client = new \Coinsnap\Client\Invoice($apiHelper->url, $apiHelper->apiKey);
        $coinsnapCurrencies = $client->getCurrencies();
        
        foreach($currencies as $currency_key => $currency_value){
            if( !in_array($currency_key,$coinsnapCurrencies) ){
                unset($currencies[$currency_key]);
                $currency = get_option( 'woocommerce_currency' );
                if($currency_key === $currency){
                    update_option( 'woocommerce_currency','USD' );
                }
            }
        }
        return $currencies;
    }

    public static function initPaymentGateways($gateways): array {
// Default gateway covers all payment methods available on Coinsnap Payment Gateway.
	$gateways[] = DefaultGateway::class;
	return $gateways;
    }
    
    public static function enqueueScripts(): void {
        
        if(get_self_link() === wc_get_checkout_url()){
            wp_register_style('coinsnap_payment', plugins_url('assets/css/coinsnap-woocommerce-checkout.css',__FILE__),array(),COINSNAP_WC_VERSION);
            wp_enqueue_style('coinsnap_payment');
            
            wp_enqueue_script('coinsnap-woocommerce-checkout',plugin_dir_url( __FILE__ ) . 'assets/js/coinsnap-woocommerce-checkout.js',[ 'jquery' ],COINSNAP_WC_VERSION,true);
        
            wp_localize_script('coinsnap-woocommerce-checkout', 'coinsnap_ajax', array(
              'ajax_url' => admin_url('admin-ajax.php'),
              'nonce'  => wp_create_nonce( 'coinsnap-ajax-nonce' ),
            ));
        }
    }

//  Checks if the plugin is not configured yet method shows notice & link to the config page on admin dashboard
    public function notConfiguredNotification(): void {
        if (!CoinsnapApiHelper::getConfig()){
            $message = sprintf(
                /* translators: 1: Link to settings page opening tag 2: Link to settings page closing tag */
                esc_html__('Plugin is not configured yet, please %1$sconfigure the plugin here%2$s','coinsnap-for-woocommerce'),
		'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=coinsnap_settings')) . '">','</a>'
            );
            Notice::addNotice('error', $message);
        }
    }

//  Checks if PHP version is too low or WooCommerce is not installed or CURL is not available and displays notice on admin dashboard
    public function dependenciesNotification() {
        // Check PHP version.70
	if ( version_compare( PHP_VERSION, COINSNAP_WC_PHP_VERSION, '<' ) ) {
            $versionMessage = sprintf( 
                /* translators: 1: PHP version */
                __( 'Your PHP version is %1$s but Coinsnap Payment plugin requires version 7.4+.', 'coinsnap-for-woocommerce' ), PHP_VERSION );
            Notice::addNotice('error', $versionMessage);
	}

	// Check if WooCommerce is installed.
	if ( ! is_plugin_active('woocommerce/woocommerce.php') ) {
            $wcMessage = __('WooCommerce seems to be not installed. Make sure you do before you activate Coinsnap payment gateway.', 'coinsnap-for-woocommerce');
            Notice::addNotice('error', $wcMessage);
	}

	// Check if PHP cURL is available.
	if ( ! function_exists('curl_init') ) {
            $curlMessage = __('The PHP cURL extension is not installed. Make sure it is available otherwise this plugin will not work.', 'coinsnap-for-woocommerce');
            Notice::addNotice('error', $curlMessage);
	}
    }
    
    /**
     * Handles the BTCPay server AJAX callback from the GlobalSettings form.
     */
    public function btcpayApiUrlHandler() {
        $_nonce = filter_input(INPUT_POST,'apiNonce',FILTER_SANITIZE_STRING);
        if ( !wp_verify_nonce( $_nonce, 'coinsnap-ajax-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
        }
        
        Logger::debug('current_user_can_manage_options: ' . current_user_can( 'manage_options' ));

	if ( current_user_can( 'manage_options' ) ) {
            $host = filter_var(filter_input(INPUT_POST,'host',FILTER_SANITIZE_STRING), FILTER_VALIDATE_URL);

            if ($host === false || (substr( $host, 0, 7 ) !== "http://" && substr( $host, 0, 8 ) !== "https://")) {
                wp_send_json_error("Error validating BTCPayServer URL.");
            }

            $permissions = array_merge(BTCPayApiAuthorization::REQUIRED_PERMISSIONS, BTCPayApiAuthorization::OPTIONAL_PERMISSIONS);

            try {
		// Create the redirect url to BTCPay instance.
		$url = \Coinsnap\Client\BTCPayApiKey::getAuthorizeUrl(
                    $host,
                    $permissions,
                    'WooCommerce',
                    true,
                    true,
                    home_url('?coinsnap-for-woocommerce-btcpay-settings-callback'),
                    null
		);

		// Store the host to options before we leave the site.
		update_option('btcpay_server_url', $host);

		// Return the redirect url.
		wp_send_json_success(['url' => $url]);
            }
            
            catch (\Throwable $e) {
                Logger::debug('Error fetching redirect url from BTCPay Server.');
            }
	}
        wp_send_json_error("Error processing Ajax request.");
    }

    public static function orderStatusThankYouPage($order_id){
	if (!$order = wc_get_order($order_id)) {
            return;
	}

	$orderData = $order->get_data();
        $status = $orderData['status'];

            switch ($status){
                case 'on-hold':
                    $statusDesc = __('Waiting for payment settlement', 'coinsnap-for-woocommerce');
                    break;
                case 'processing':
                    $statusDesc = __('Payment processing', 'coinsnap-for-woocommerce');
                    break;
		case 'completed':
                    $statusDesc = __('Payment settled', 'coinsnap-for-woocommerce');
                    break;
		case 'failed':
                    $statusDesc = __('Payment failed', 'coinsnap-for-woocommerce');
                    break;
		default:
                    $statusDesc = ucfirst($status);
                    break;
            }

            echo "<section class='woocommerce-order-payment-status'>
		    <h2 class='woocommerce-order-payment-status-title'>".esc_html__('Payment Status','coinsnap-for-woocommerce')."</h2>
		    <p><strong>".esc_html($statusDesc)."</strong></p>
		</section>";
    }
    
    // Register WooCommerce Blocks support.
    public static function blocksSupport() {
        if ( class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' )){
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry){
                    $payment_method_registry->register(new \Coinsnap\WC\Blocks\DefaultGatewayBlocks());
		}
            );
        }
    }

//  Gets the main plugin loader instance and ensures only one instance can be loaded.
    public static function instance(): \CoinsnapWCPlugin {
        if ( null === self::$instance ) self::$instance = new self();
	return self::$instance;
    }
}

//  Start everything up.
function coinsnap_payment_init() {
    \CoinsnapWCPlugin::instance();
}

//  Bootstrap stuff on init.
add_action('init', function() {
    // Setting up and handling custom endpoint for api key redirect from Coinsnap Server.
    add_rewrite_endpoint('coinsnap-for-woocommerce-btcpay-settings-callback', EP_ROOT);
    // Flush rewrite rules only once after activation.
    if ( ! get_option('coinsnap_permalinks_flushed') ) {
	flush_rewrite_rules(false);
	update_option('coinsnap_permalinks_flushed', 1);
    }
});

// Action links on plugin overview.
add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), function ($links){
    // Settings link.
    $settings_url = esc_url( add_query_arg(['page' => 'wc-settings','tab' => 'coinsnap_settings'],get_admin_url() . 'admin.php') );
    $settings_link = "<a href='$settings_url'>" . __( 'Settings', 'coinsnap-for-woocommerce' ) . '</a>';
    $logs_link = "<a target='_blank' href='" . Logger::getLogFileUrl() . "'>" . __('Debug log', 'coinsnap-for-woocommerce') . "</a>";
    $docs_link = "<a target='_blank' href='". esc_url('https://coinsnap.io/coinsnap-for-woocommerce-plugin/') . "'>" . __('Docs', 'coinsnap-for-woocommerce') . "</a>";
    $installation_link = "<a target='_blank' href='". esc_url('https://coinsnap.io/coinsnap-for-woocommerce-installation-guide/') . "'>" . __('Installation guide', 'coinsnap-for-woocommerce') . "</a>";

    array_unshift($links,$settings_link,$logs_link,$docs_link,$installation_link);
    return $links;
});


// To be able to use the endpoint without appended url segments we need to do this.
add_filter('request', function($vars) {
    if (isset($vars['coinsnap-for-woocommerce-btcpay-settings-callback'])) {
        $vars['coinsnap-for-woocommerce-btcpay-settings-callback'] = true;
        $vars['coinsnap-for-woocommerce-btcpay-nonce'] = wp_create_nonce('coinsnap-btcpay-nonce');
    }
    return $vars;
});

// Adding template redirect handling for coinsnap-for-woocommerce-btcpay-settings-callback.
add_action( 'template_redirect', function() {
    global $wp_query;

    // Only continue on a coinsnap-for-woocommerce-btcpay-settings-callback request.
    if (! isset( $wp_query->query_vars['coinsnap-for-woocommerce-btcpay-settings-callback'] ) ) {
        return;
    }
            
    if(!isset($wp_query->query_vars['coinsnap-for-woocommerce-btcpay-nonce']) || !wp_verify_nonce($wp_query->query_vars['coinsnap-for-woocommerce-btcpay-nonce'],'coinsnap-btcpay-nonce')){
        return;
    }

    $CoinsnapBTCPaySettingsUrl = admin_url('admin.php?page=wc-settings&tab=coinsnap_settings&provider=btcpay');

    $rawData = file_get_contents('php://input');
    Logger::debug('Redirect payload: ' . $rawData);
    
    $btcpay_server_url = get_option( 'btcpay_server_url');
    $btcpay_api_key  = filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    $client = new \Coinsnap\Client\Store($btcpay_server_url,$btcpay_api_key);
    if (count($client->getStores()) < 1) {
        $messageAbort = __('Error on verifiying redirected API Key with stored BTCPay Server url. Aborting API wizard. Please try again or continue with manual setup.', 'coinsnap-for-woocommerce');
	Notice::addNotice('error', $messageAbort);
	wp_redirect($CoinsnapBTCPaySettingsUrl);
    }
    
    // Data does get submitted with url-encoded payload, so parse $_POST here.
    if (!empty($_POST)) {
        $data['apiKey'] = filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
            if(isset($_POST['permissions'])){
                $permissions = array_map('sanitize_text_field', wp_unslash($_POST['permissions']));
                if(is_array($permissions)){
                foreach ($permissions as $key => $value) {
                    $data['permissions'][$key] = sanitize_text_field($permissions[$key] ?? null);
                }
            }
        }
    }
    
    if (isset($data['apiKey']) && isset($data['permissions'])) {
        
        $apiData = new \Coinsnap\Client\BTCPayApiAuthorization($data);
	if ($apiData->hasSingleStore() && $apiData->hasRequiredPermissions()) {
			
            update_option('btcpay_api_key', $apiData->getApiKey());
            update_option('btcpay_store_id', $apiData->getStoreID());
            update_option('coinsnap_provider', 'btcpay');

            Notice::addNotice('success', __('Successfully received api key and store id from BTCPay Server API. Please finish setup by saving this settings form.', 'coinsnap-for-woocommerce'));

            // Register a webhook.
            if (CoinsnapApiWebhook::registerWebhook($btcpay_server_url, $apiData->getApiKey(), $apiData->getStoreID())) {
                $messageWebhookSuccess = __( 'Successfully registered a new webhook on BTCPay Server.', 'coinsnap-for-woocommerce' );
                Notice::addNotice('success', $messageWebhookSuccess, true );
            }
            else {
                $messageWebhookError = __( 'Could not register a new webhook on the store.', 'coinsnap-for-woocommerce' );
                Notice::addNotice('error', $messageWebhookError );
            }

            wp_redirect($CoinsnapBTCPaySettingsUrl);
	}
        else {
            Notice::addNotice('error', __('Please make sure you only select one store on the BTCPay API authorization page.', 'coinsnap-for-woocommerce'));
            wp_redirect($CoinsnapBTCPaySettingsUrl);
	}
    }
    
    Notice::addNotice('error', __('Error processing the data from Coinsnap. Please try again.', 'coinsnap-for-woocommerce'));
    wp_redirect($CoinsnapBTCPaySettingsUrl);
});

// Installation routine.
register_activation_hook( __FILE__, function() {
	update_option('coinsnap_permalinks_flushed', 0);
	update_option( COINSNAP_VERSION_KEY, COINSNAP_WC_VERSION );
});

// Initialize payment gateways and plugin.
add_filter( 'woocommerce_payment_gateways', [ 'CoinsnapWCPlugin', 'initPaymentGateways' ] );
add_action( 'plugins_loaded', 'coinsnap_payment_init', 0 );

// Mark support for HPOS / COT.
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

add_action( 'wp_enqueue_scripts', [ 'CoinsnapWCPlugin', 'enqueueScripts' ]  );
add_action( 'woocommerce_blocks_loaded', [ 'CoinsnapWCPlugin', 'blocksSupport' ] );


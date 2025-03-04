<?php
/**
 * Plugin Name:     Bitcoin payment for WooCommerce
 * Plugin URI:      https://coinsnap.io/coinsnap-for-woocommerce-plugin/
 * Description:     With Coinsnap payment processing, you can accept Bitcoin and Lightning payments on your website or online store. You do not need your own Lightning Node or other technical requirements.
 * Author:          Coinsnap
 * Author URI:      https://coinsnap.io/
 * Text Domain:     coinsnap-for-woocommerce
 * Domain Path:     /languages
 * Version:         1.1.11
 * Requires PHP:    7.4
 * Tested up to:    6.7
 * Requires at least: 5.2
 * Requires Plugins: woocommerce
 * WC requires at least: 6.0
 * WC tested up to: 9.7.0
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
use Coinsnap\WC\Helper\Logger;

defined( 'ABSPATH' ) || exit();
if(!defined('COINSNAP_WC_PHP_VERSION')){define( 'COINSNAP_WC_PHP_VERSION', '7.4' );}
if(!defined('COINSNAP_WC_VERSION')){define( 'COINSNAP_WC_VERSION', '1.1.11' );}
if(!defined('COINSNAP_VERSION_KEY')){define( 'COINSNAP_VERSION_KEY', 'coinsnap_version' );}
if(!defined('COINSNAP_PLUGIN_FILE_PATH')){define( 'COINSNAP_PLUGIN_FILE_PATH', plugin_dir_path( __FILE__ ) );}
if(!defined('COINSNAP_PLUGIN_URL')){define( 'COINSNAP_PLUGIN_URL', plugin_dir_url(__FILE__ ) );}
if(!defined('COINSNAP_WC_PLUGIN_ID')){define( 'COINSNAP_WC_PLUGIN_ID', 'coinsnap-for-woocommerce' );}
if(!defined('COINSNAP_SERVER_URL')){define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );}
if(!defined('COINSNAP_API_PATH')){define( 'COINSNAP_API_PATH', '/api/v1/');}
if(!defined('COINSNAP_SERVER_PATH')){define( 'COINSNAP_SERVER_PATH', 'stores' );}
if(!defined('COINSNAP_WC_REFERRAL_CODE')){define( 'COINSNAP_WC_REFERRAL_CODE', 'DEV1e1ea54fedd507e2f447e2963' );}

class CoinsnapWCPlugin {
    
    private static $instance;

    public function __construct() {
	$this->includes();

	add_action('woocommerce_thankyou_coinsnap', [$this, 'orderStatusThankYouPage'], 10, 1);

	// Run the updates.
	\Coinsnap\WC\Helper\UpdateManager::processUpdates();

	if (is_admin()) {
            
            add_action( 'admin_enqueue_scripts', [ $this, 'connectionCheckScript' ] );
            add_action( 'wp_ajax_coinsnap_connection_handler', [$this, 'coinsnapConnectionHandler'] );
            
            // Register our custom global settings page.
            add_filter(
                'woocommerce_get_settings_pages',
                function ($settings) {
                    $settings[] = new \Coinsnap\WC\Admin\GlobalSettings();
                    return $settings;
                }
            );
            add_action( 'wp_ajax_handle_ajax_api_url', [$this, 'processAjaxApiUrl'] );

            $this->dependenciesNotification();
            //$this->legacyPluginNotification(); // Not in v 1.1
            $this->notConfiguredNotification();
	}
    }
    
    public function connectionCheckScript(){
        wp_register_style('coinsnap-backend-style', plugins_url('assets/css/coinsnap-backend-style.css',__FILE__),array(),COINSNAP_WC_VERSION);
        wp_enqueue_style('coinsnap-backend-style');
        wp_enqueue_script('coinsnap-connection-check',plugin_dir_url( __FILE__ ) . 'assets/js/connectionCheck.js',[ 'jquery' ],COINSNAP_WC_VERSION,true);
        wp_add_inline_script( 'coinsnap-connection-check', 'var wc_secret = "'.wp_create_nonce().'";', 'before' );
    }
    
    public function coinsnapConnectionHandler(){
        
        $_nonce = filter_input(INPUT_POST,'_wpnonce',FILTER_SANITIZE_STRING);
        
        if( wp_verify_nonce($_nonce) ){
            $response = [
                'result' => false,
                'message' => __('WooCommerce: Coinsnap connection error', 'coinsnap-for-woocommerce')
            ];

            try {
                if (!CoinsnapApiHelper::checkApiConnection()) {
                    $this->sendJsonResponse($response);
                }

                $apiHelper = new CoinsnapApiHelper();
                $webhookExists = CoinsnapApiWebhook::webhookExists(
                    $apiHelper->url,
                    $apiHelper->apiKey,
                    $apiHelper->storeId
                );

                if ($webhookExists) {
                    $response['result'] = true;
                    $response['message'] = __('WooCommerce: Coinsnap server is connected', 'coinsnap-for-woocommerce');
                    $this->sendJsonResponse($response);
                }

                $webhook = CoinsnapApiWebhook::registerWebhook(
                    $apiHelper->url,
                    $apiHelper->apiKey,
                    $apiHelper->storeId
                );

                $response['result'] = (bool)$webhook;
                $response['message'] = $webhook 
                    ? __('WooCommerce: Coinsnap server is connected', 'coinsnap-for-woocommerce')
                    : __('WooCommerce: Coinsnap connection error', 'coinsnap-for-woocommerce');

            }
            catch (Exception $e) {
                $response['message'] = $e->getMessage();
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
        // Make SAT / Sats as currency available.        
	if (get_option('coinsnap_sats_mode') === 'yes') {
            SatsMode::instance();
	}
    }

    public static function initPaymentGateways($gateways): array {
// Default gateway covers all payment methods available on Coinsnap Payment Gateway.
	$gateways[] = DefaultGateway::class;
	return $gateways;
    }
    
    public static function enqueueScripts(): void {
        wp_register_style('coinsnap_payment', plugins_url('assets/css/coinsnap-style.css',__FILE__),array(),COINSNAP_WC_VERSION);
        wp_enqueue_style('coinsnap_payment');
    }

//  Checks if the plugin is not configured yet method shows notice & link to the config page on admin dashboard
    public function notConfiguredNotification(): void {
        if (!CoinsnapApiHelper::getConfig()){
            $message = sprintf(
                /* translators: 1: Link to settings page opening tag 2: Link to settings page closing tag */
                esc_html__('Plugin not configured yet, please %1$sconfigure the plugin here%2$s','coinsnap-for-woocommerce'),
		'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=coinsnap_settings')) . '">','</a>'
            );
            Notice::addNotice('error', $message);
        }
    }

//  Checks if PHP version is too low or WooCommerce is not installed or CURL is not available and displays notice on admin dashboard
    public function dependenciesNotification() {
        // Check PHP version.
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

            //$title = esc_html__('Payment Status','coinsnap-for-woocommerce');

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
    // Adding textdomain and translation support.
    load_plugin_textdomain('coinsnap-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
    // Setting up and handling custom endpoint for api key redirect from Coinsnap Server.
    add_rewrite_endpoint('coinsnap-settings-callback', EP_ROOT);
    // Flush rewrite rules only once after activation.
    if ( ! get_option('coinsnap_permalinks_flushed') ) {
	flush_rewrite_rules(false);
	update_option('coinsnap_permalinks_flushed', 1);
    }
});

// Action links on plugin overview.
add_filter( 'plugin_action_links_coinsnap-woocommerce/coinsnap-woocommerce.php', function ($links){
    // Settings link.
    $settings_url = esc_url( add_query_arg(['page' => 'wc-settings','tab' => 'coinsnap_settings'],get_admin_url() . 'admin.php') );
    $settings_link = "<a href='$settings_url'>" . __( 'Settings', 'coinsnap-for-woocommerce' ) . '</a>';
    $logs_link = "<a target='_blank' href='" . Logger::getLogFileUrl() . "'>" . __('Debug log', 'coinsnap-for-woocommerce') . "</a>";
    $docs_link = "<a target='_blank' href='". esc_url('https://coinsnap.io/en/coinsnap-woocommerce-plugin/') . "'>" . __('Docs', 'coinsnap-for-woocommerce') . "</a>";
    //$support_link = "<a target='_blank' href='". esc_url('https://coinsnap.io') . "'>" . __('Support Chat', 'coinsnap-for-woocommerce') . "</a>";

    array_unshift($links,$settings_link,$logs_link,$docs_link,$support_link);
    return $links;
});


// To be able to use the endpoint without appended url segments we need to do this.
add_filter('request', function($vars) {
    if (isset($vars['coinsnap-settings-callback'])) {
        $vars['coinsnap-settings-callback'] = true;
    }
    return $vars;
});

// Adding template redirect handling for coinsnap-settings-callback.
add_action( 'template_redirect', function() {
	global $wp_query;

	// Only continue on a coinsnap-settings-callback request.
	if (! isset( $wp_query->query_vars['coinsnap-settings-callback'] ) ) {
		return;
	}

	$coinsnapSettingsUrl = admin_url('admin.php?page=wc-settings&tab=coinsnap_settings');

	$rawData = file_get_contents('php://input');
	$data = json_decode( $rawData, true );

	// Seems data does get submitted with url-encoded payload, so parse $_POST here.
        $nonce = sanitize_text_field(wp_unslash (filter_input(INPUT_POST,'_wpnonce',FILTER_SANITIZE_STRING)));
	if (wp_verify_nonce($nonce,-1) || is_array($_POST)) {
            
            $data['apiKey'] = (null !== filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_STRING ))? sanitize_text_field(filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_STRING )) : null;
            if (wp_verify_nonce($nonce,-1) || (isset($_POST['permissions']) && is_array($_POST['permissions']))) {
                $permissions = array_map('sanitize_key',$_POST['permissions']);
                foreach ($permissions as $key => $value) {
                    $data['permissions'][$key] = (sanitize_text_field($value))? sanitize_text_field($value) : null;
		}
            }
	}

	if (isset($data['apiKey']) && isset($data['permissions'])) {
		$apiData = new \Coinsnap\WC\Helper\CoinsnapApiAuthorization($data);
		if ($apiData->hasSingleStore() && $apiData->hasRequiredPermissions()) {
			update_option('coinsnap_api_key', $apiData->getApiKey());
			update_option('coinsnap_store_id', $apiData->getStoreID());
			Notice::addNotice('success', __('Successfully received api key and store id from Coinsnap Server API. Please finish setup by saving this settings form.', 'coinsnap-for-woocommerce'));
			wp_redirect($coinsnapSettingsUrl);
		}
                else {
			Notice::addNotice('error', __('Please make sure you only select one store on the Coinsnap API authorization page.', 'coinsnap-for-woocommerce'));
			wp_redirect($coinsnapSettingsUrl);
		}
	}

	Notice::addNotice('error', __('Error processing the data from Coinsnap. Please try again.', 'coinsnap-for-woocommerce'));
	wp_redirect($coinsnapSettingsUrl);
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


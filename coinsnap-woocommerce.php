<?php
/**
 * Plugin Name:     Coinsnap For Woocommerce 1.0
 * Plugin URI:      https://wordpress.org/plugins/coinsnap-woocommerce/
 * Description:     With Coinsnap payment processing, you can accept Bitcoin and Lightning payments on your website or online store. You do not need your own Lightning Node or other technical requirements.
 * Author:          Coinsnap
 * Author URI:      https://coinsnap.io/
 * Text Domain:     coinsnap-woocommerce
 * Domain Path:     /languages
 * Version:         1.0
 * Requires PHP:    7.4
 * Tested up to:    6.2
 * Requires at least: 5.2
 * WC requires at least: 6.0
 * WC tested up to: 7.5
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:         true
 * Update URI:      https://coinsnap.io/en/coinsnap-woocommerce-plugin/update/
 */

use Coinsnap\WC\Admin\Notice;
use Coinsnap\WC\Gateway\DefaultGateway;
use Coinsnap\WC\Helper\SatsMode;
use Coinsnap\WC\Helper\CoinsnapApiHelper;
use Coinsnap\WC\Helper\Logger;

defined( 'ABSPATH' ) || exit();
define( 'SERVER_PHP_VERSION', '7.4' );
define( 'COINSNAP_VERSION', '1.0' );
define( 'COINSNAP_VERSION_KEY', 'coinsnap_version' );
define( 'COINSNAP_PLUGIN_FILE_PATH', plugin_dir_path( __FILE__ ) );
define( 'COINSNAP_PLUGIN_URL', plugin_dir_url(__FILE__ ) );
define( 'COINSNAP_PLUGIN_ID', 'coinsnap-for-woocommerce' );
define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );
define( 'COINSNAP_SERVER_PATH', 'stores' );
define( 'COINSNAP_REFERRAL_CODE', '' );

class CoinsnapWCPlugin {
    
    private static $instance;

    public function __construct() {
	$this->includes();

	add_action('woocommerce_thankyou_coinsnap', [$this, 'orderStatusThankYouPage'], 10, 1);
	//add_action( 'wp_ajax_coinsnap_modal_checkout', [$this, 'processAjaxModalCheckout'] );
	//add_action( 'wp_ajax_nopriv_coinsnap_modal_checkout', [$this, 'processAjaxModalCheckout'] );

	// Run the updates.
	\Coinsnap\WC\Helper\UpdateManager::processUpdates();

	if (is_admin()) {
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
            //$this->legacyPluginNotification();
            $this->notConfiguredNotification();
	}
    }

    public function includes(): void {
	
        $autoloader = COINSNAP_PLUGIN_FILE_PATH . 'loader/autoload.php';
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
        wp_register_style('coinsnap_payment', plugins_url('assets/css/coinsnap-style.css',__FILE__ ));
        wp_enqueue_style('coinsnap_payment');
    }

//  Checks if the plugin is not configured yet method shows notice & link to the config page on admin dashboard
    public function notConfiguredNotification(): void {
        if (!CoinsnapApiHelper::getConfig()){
            $message = sprintf(
                esc_html__('Plugin not configured yet, please %1$sconfigure the plugin here%2$s','coinsnap-for-woocommerce'),
		'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=coinsnap_settings')) . '">','</a>'
            );
            Notice::addNotice('error', $message);
        }
    }

//  Checks if PHP version is too low or WooCommerce is not installed or CURL is not available and displays notice on admin dashboard
    public function dependenciesNotification() {
        // Check PHP version.
	if ( version_compare( PHP_VERSION, SERVER_PHP_VERSION, '<' ) ) {
            $versionMessage = sprintf( __( 'Your PHP version is %s but Coinsnap Payment plugin requires version '.SERVER_PHP_VERSION.'+.', 'coinsnap-for-woocommerce' ), PHP_VERSION );
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
    
/*
//  Handles the AJAX callback from the GlobalSettings form.
    
    public function processAjaxApiUrl() {
        $nonce = $_POST['apiNonce'];
	if ( ! wp_verify_nonce( $nonce, 'coinsnap-api-url-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
	}

	if ( current_user_can( 'manage_options' ) ) {
			$host = filter_var($_POST['host'], FILTER_VALIDATE_URL);

			if ($host === false || (substr( $host, 0, 7 ) !== "http://" && substr( $host, 0, 8 ) !== "https://")) {
				wp_send_json_error("Error validating Coinsnap Server URL.");
			}

			$permissions = array_merge(CoinsnapApiAuthorization::REQUIRED_PERMISSIONS, CoinsnapApiAuthorization::OPTIONAL_PERMISSIONS);

			try {
				// Create the redirect url to Coinsnap instance.
				$url = \Coinsnap\Client\ApiKey::getAuthorizeUrl(
					$host,
					$permissions,
					'WooCommerce',
					true,
					true,
					home_url('?coinsnap-settings-callback'),
					null
				);

				// Store the host to options before we leave the site.
				update_option('coinsnap_url', $host);

				// Return the redirect url.
				wp_send_json_success(['url' => $url]);
			} catch (\Throwable $e) {
				Logger::debug('Error fetching redirect url from Coinsnap Server.');
			}
	}

        wp_send_json_error("Error processing Ajax request.");
    }

// Handles the AJAX callback from the Payment Request on the checkout page.

    public function processAjaxModalCheckout(){
        Logger::debug('Entering ' . __METHOD__);
        $nonce = $_POST['apiNonce'];
	if ( ! wp_verify_nonce( $nonce, 'coinsnap-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
	}

	if ( get_option('coinsnap_modal_checkout') !== 'yes' ) {
            wp_die('Modal checkout mode not enabled.', '', ['response' => 400]);
	}

	wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
            try {
                WC()->checkout()->process_checkout();
            }
        catch (\Throwable $e) {
            Logger::debug('Error processing modal checkout ajax callback: ' . $e->getMessage());
        }
    }
*/

    public static function orderStatusThankYouPage($order_id){
	if (!$order = wc_get_order($order_id)) {
            return;
	}

	$title = _x('Payment Status', 'coinsnap-for-woocommerce');

        $orderData = $order->get_data();
        $status = $orderData['status'];

            switch ($status){
                case 'on-hold':
                    $statusDesc = _x('Waiting for payment settlement', 'coinsnap-for-woocommerce');
                    break;
                case 'processing':
                    $statusDesc = _x('Payment processing', 'coinsnap-for-woocommerce');
                    break;
		case 'completed':
                    $statusDesc = _x('Payment settled', 'coinsnap-for-woocommerce');
                    break;
		case 'failed':
                    $statusDesc = _x('Payment failed', 'coinsnap-for-woocommerce');
                    break;
		default:
                    $statusDesc = _x(ucfirst($status), 'coinsnap-for-woocommerce');
                    break;
            }

            echo "<section class='woocommerce-order-payment-status'>
		    <h2 class='woocommerce-order-payment-status-title'>{$title}</h2>
		    <p><strong>{$statusDesc}</strong></p>
		</section>";
    }

//  Gets the main plugin loader instance and ensures only one instance can be loaded.
    public static function instance(): \CoinsnapWCPlugin {
        if ( null === self::$instance ) self::$instance = new self();
	return self::$instance;
    }
}

//  Start everything up.
function init_coinsnap_payment() {
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
	if (!empty($_POST)) {
		$data['apiKey'] = sanitize_html_class($_POST['apiKey'] ?? null);
		if (is_array($_POST['permissions'])) {
			foreach ($_POST['permissions'] as $key => $value) {
				$data['permissions'][$key] = sanitize_text_field($_POST['permissions'][$key] ?? null);
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
	update_option( COINSNAP_VERSION_KEY, COINSNAP_VERSION );
});

// Initialize payment gateways and plugin.
add_filter( 'woocommerce_payment_gateways', [ 'CoinsnapWCPlugin', 'initPaymentGateways' ] );
add_action( 'plugins_loaded', 'init_coinsnap_payment', 0 );
add_action( 'wp_enqueue_scripts', [ 'CoinsnapWCPlugin', 'enqueueScripts' ]  );


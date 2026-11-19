<?php
/**
 * Plugin Name:     Bitcoin payment for WooCommerce
 * Plugin URI:      https://coinsnap.io/coinsnap-for-woocommerce-plugin/
 * Description:     With Coinsnap payment processing, you can accept Bitcoin and Lightning payments on your website or online store.
 * Author:          Coinsnap
 * Author URI:      https://coinsnap.io/
 * Text Domain:     coinsnap-for-woocommerce
 * Domain Path:     /languages
 * Version:         1.8.0
 * Requires PHP:    7.4
 * Tested up to:    6.9
 * Requires at least: 6.0
 * Requires Plugins: woocommerce
 * WC requires at least: 6.0
 * WC tested up to: 10.3.6
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
if(!defined('COINSNAP_WC_VERSION')){define( 'COINSNAP_WC_VERSION', '1.8.0' );}
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
            
            add_action( 'admin_init', [ Notice::class, 'showFlash' ] );
            add_action( 'admin_init', [ CoinsnapApiWebhook::class, 'maybeRotateWebhook' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'connectionScriptsLoader' ] );
            add_action( 'wp_ajax_coinsnap_connection_handler', [$this, 'coinsnapConnectionHandler'] );
            add_action( 'wp_ajax_btcpay_server_apiurl_handler', [$this, 'btcpayApiUrlHandler'] );
            add_action( 'admin_post_coinsnap_check_payment', [$this, 'checkPaymentHandler'] );
            add_action( 'admin_notices', [$this, 'checkPaymentNotice'] );

            if(filter_input(INPUT_GET,'page',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) === 'wc-orders'){
            
                if(filter_input(INPUT_GET,'action',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) === 'edit'){
                    add_action('woocommerce_admin_order_data_after_billing_address', [$this,'coinsnapOrderDetails']);
                }
                else {
                    add_filter( 'manage_woocommerce_page_wc-orders_columns', [$this, 'coinsnapOrderListColumns']);
                    add_action( 'manage_woocommerce_page_wc-orders_custom_column', [$this, 'coinsnapOrderListColumnsData'], 10, 2 );
                }
            }
            
            
            if(filter_input(INPUT_GET,'post_type',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) === 'shop_order'){
                add_filter( 'manage_edit-shop_order_columns', [$this, 'coinsnapOrderListColumns']);
                add_action( 'manage_shop_order_posts_custom_column', [$this, 'coinsnapOrderListColumnsDataById'], 10, 2 );
            }
            if(get_post_type(filter_input(INPUT_GET,'post',FILTER_SANITIZE_NUMBER_INT)) === 'shop_order' && filter_input(INPUT_GET,'action',FILTER_SANITIZE_FULL_SPECIAL_CHARS) === 'edit'){
                add_action('woocommerce_admin_order_data_after_billing_address', [$this,'coinsnapOrderDetails']);
            }
        
            
            
            
            
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
        
        
        // One-time webhook secret renewal, run by WP-Cron so it does not wait for an admin login.
        add_action( CoinsnapApiWebhook::ROTATE_HOOK, [ CoinsnapApiWebhook::class, 'rotateWebhook' ] );
        CoinsnapApiWebhook::scheduleRotation();

        // Mark the Coinsnap discount fee line, and drop it when the order is paid another way (6.10).
        add_action('woocommerce_checkout_create_order_fee_item', [$this, 'markCoinsnapDiscountFee'], 10, 4);
        add_action('woocommerce_before_pay_action', [$this, 'removeDiscountForOtherMethod'], 10, 1);

        add_action( 'wp_ajax_coinsnap_checkout', [$this, 'coinsnapCheckoutHandler'] );
        add_action( 'wp_ajax_nopriv_coinsnap_checkout', [$this, 'coinsnapCheckoutHandler'] );
        
    }
    
    public function coinsnapOrderDetails($order){
        if( $order->get_meta( 'Coinsnap_transactionurl') !== null && !empty($order->get_meta( 'Coinsnap_transactionurl'))){
                echo '<b>'.esc_html__('Transaction details','coinsnap-for-woocommerce').'</b>:<br/><a href="'.esc_url($order->get_meta( 'Coinsnap_transactionurl')).'" target="_blank" class="">'.esc_url($order->get_meta( 'Coinsnap_transactionurl')).'</a>';
        }
        // Manual recovery when a webhook was missed (Coinsnap does not redeliver automatically).
        if ($order->get_meta('Coinsnap_id') && !$order->is_paid() && current_user_can('edit_shop_orders')) {
            $url = wp_nonce_url(
                add_query_arg(['action' => 'coinsnap_check_payment', 'order_id' => $order->get_id()], admin_url('admin-post.php')),
                'coinsnap_check_payment_' . $order->get_id()
            );
            echo '<p><a class="button" href="' . esc_url($url) . '">' . esc_html__('Check payment status', 'coinsnap-for-woocommerce') . '</a></p>';
        }
    }

    // Asks the payment server for the order's invoice and applies the same paid decision as a webhook.
    public function checkPaymentHandler() {
        $orderId = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        check_admin_referer('coinsnap_check_payment_' . $orderId);
        if (!current_user_can('edit_shop_orders')) {
            wp_die(esc_html__('Unauthorized', 'coinsnap-for-woocommerce'), '', ['response' => 403]);
        }
        $order = wc_get_order($orderId);
        if (!$order) {
            wp_die(esc_html__('Order not found.', 'coinsnap-for-woocommerce'), '', ['response' => 404]);
        }

        // One check per order every 30 seconds, so repeated clicks do not hammer the payment server API.
        $throttleKey = 'coinsnap_wc_check_' . $order->get_id();
        if (get_transient($throttleKey)) {
            wp_safe_redirect(add_query_arg(['coinsnap_check' => 'wait'], $order->get_edit_order_url()));
            exit;
        }
        set_transient($throttleKey, 1, 30);

        $gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : [];
        $gateway = (isset($gateways['coinsnap']) && $gateways['coinsnap'] instanceof DefaultGateway) ? $gateways['coinsnap'] : new DefaultGateway();
        [$result, $status] = $gateway->checkPaymentStatus($order);

        $order->add_order_note(sprintf(
            /* translators: 1: result, 2: invoice status */
            __('Payment status checked manually: %1$s (invoice status: %2$s).', 'coinsnap-for-woocommerce'),
            $result,
            $status !== '' ? $status : '-'
        ));

        wp_safe_redirect(add_query_arg(
            ['coinsnap_check' => $result, 'coinsnap_status' => $status],
            $order->get_edit_order_url()
        ));
        exit;
    }

    public function checkPaymentNotice() {
        $result = isset($_GET['coinsnap_check']) ? sanitize_key(wp_unslash($_GET['coinsnap_check'])) : '';
        if ($result === '' || !current_user_can('edit_shop_orders')) {
            return;
        }
        $status = isset($_GET['coinsnap_status']) ? sanitize_text_field(wp_unslash($_GET['coinsnap_status'])) : '';
        $known = ['New', 'Processing', 'Settled', 'Expired', 'Invalid'];
        $status = in_array($status, $known, true) ? $status : '';
        $messages = [
            'paid' => ['success', __('Payment confirmed by the payment server. The order is marked as paid.', 'coinsnap-for-woocommerce')],
            /* translators: %s: invoice status */
            'not_paid' => ['warning', sprintf(__('The invoice is not paid in full for this order (status: %s). The order was not marked as paid; see the order notes.', 'coinsnap-for-woocommerce'), $status !== '' ? $status : '-')],
            'no_invoice' => ['warning', __('This order has no Coinsnap invoice.', 'coinsnap-for-woocommerce')],
            'api_error' => ['error', __('Could not reach the payment server. Please check the connection and try again.', 'coinsnap-for-woocommerce')],
            'wait' => ['info', __('The payment status of this order was checked a moment ago. Please wait 30 seconds and try again.', 'coinsnap-for-woocommerce')],
        ];
        if (isset($messages[$result])) {
            printf('<div class="notice notice-%1$s is-dismissible"><p><strong>Coinsnap:</strong> %2$s</p></div>', esc_attr($messages[$result][0]), esc_html($messages[$result][1]));
        }
    }
    
    public function coinsnapOrderListColumnsDataById( $column, $post_id ) {
        if ( $column === 'order_transaction_details_url' ) {
            // Get order
            $order = wc_get_order( $post_id );

            if( $order->get_meta( 'Coinsnap_transactionurl') !== null && !empty($order->get_meta( 'Coinsnap_transactionurl'))){
                echo '<b><a href="'.esc_url($order->get_meta( 'Coinsnap_transactionurl')).'" class="coinsnap-service" target="_blank">'.esc_html__('View details','coinsnap-for-woocommerce').'</a></b>';
            }
        }
    }
    
    public function coinsnapOrderListColumnsData( $column, $order ) {
        if ( 'order_transaction_details_url' === $column ) {
            // Replace with your custom logic to display data for the column
            if( $order->get_meta( 'Coinsnap_transactionurl') !== null && !empty($order->get_meta( 'Coinsnap_transactionurl'))){
                echo '<b><a href="'.esc_url($order->get_meta( 'Coinsnap_transactionurl')).'" class="coinsnap-service" target="_blank">'.esc_html__('View details','coinsnap-for-woocommerce').'</a></b>';
            }
        }
    }
    
    public function coinsnapOrderListColumns($columns){
        
        Logger::debug('Order list columns handler');
        
        $new_columns = ( is_array( $columns ) ) ? $columns : array();
        $isHPOS = false;
        if(isset($new_columns[ 'wc_actions' ])){
            unset( $new_columns[ 'wc_actions' ] );
            $isHPOS = true;
        }
        else {
            unset( $new_columns[ 'order_actions' ] );
        }
        
        $new_columns['order_transaction_details_url'] = __('Transaction','coinsnap-for-woocommerce');
	
        if($isHPOS){
            $new_columns[ 'wc_actions' ] = $columns[ 'wc_actions' ];
        }
        else {
            $new_columns[ 'order_actions' ] = $columns[ 'order_actions' ];
        }
        
  	return $new_columns;
    }
    
    public function markCoinsnapDiscountFee($item, $feeKey, $fee, $order){
        // The fee is only added to the cart while Coinsnap is the chosen payment method.
        if (is_object($fee) && isset($fee->name) && $fee->name === __('Bitcoin discount','coinsnap-for-woocommerce')) {
            $item->add_meta_data('_coinsnap_discount', 'yes', true);
        }
    }

    public function removeDiscountForOtherMethod($order){
        // "Pay for order" page, before the new payment method is set; WooCommerce has checked its nonce.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $method = isset($_POST['payment_method']) ? sanitize_text_field(wp_unslash($_POST['payment_method'])) : '';
        if ($order instanceof \WC_Order && $method !== 'coinsnap') {
            \Coinsnap\WC\Gateway\AbstractGateway::removeCoinsnapDiscount($order);
        }
    }

    public function coinsnapCheckoutHandler(){
        $_nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
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
          'nonce'  => wp_create_nonce( 'coinsnap-admin-nonce' ),
        ));
    }
    
    public function coinsnapConnectionHandler(){
        
        $_nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if ( !wp_verify_nonce($_nonce, 'coinsnap-admin-nonce') || !current_user_can('manage_woocommerce') ) {
            wp_send_json_error(null, 403);
        }
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
        
        {
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
            catch (\Throwable $e) {
                // Includes a failed webhook lookup; no new webhook is created then.
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
        
        // A fixed list: no API client, so this also works before an API key is saved.
        $coinsnapCurrencies = COINSNAP_CURRENCIES;
        
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
        
        if(get_self_link() === wc_get_checkout_url()){  //   || get_self_link() === wc_get_cart_url()
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
        $_nonce = isset($_POST['apiNonce']) ? sanitize_text_field(wp_unslash($_POST['apiNonce'])) : '';
        if ( !wp_verify_nonce( $_nonce, 'coinsnap-admin-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
        }
        
        Logger::debug('current_user_can_manage_options: ' . current_user_can( 'manage_options' ));

	if ( current_user_can( 'manage_options' ) ) {
            $host = filter_var(isset($_POST['host']) ? esc_url_raw(wp_unslash($_POST['host'])) : '', FILTER_VALIDATE_URL);

            if ($host === false || (substr( $host, 0, 7 ) !== "http://" && substr( $host, 0, 8 ) !== "https://")) {
                wp_send_json_error("Error validating BTCPayServer URL.");
            }

            $permissions = BTCPayApiAuthorization::REQUIRED_PERMISSIONS;

            try {
                // Store the host before we leave the site; a change clears the old key (see onServerUrlChange).
                update_option('btcpay_server_url', $host);
                $callbackUrl = add_query_arg(
                    ['coinsnap-for-woocommerce-btcpay-settings-callback' => '1', 'state' => BTCPayApiAuthorization::startState((string) get_option('btcpay_server_url', ''))],
                    home_url('/')
                );

		// Create the redirect url to BTCPay instance.
		$url = \Coinsnap\Client\BTCPayApiKey::getAuthorizeUrl(
                    $host,
                    $permissions,
                    'WooCommerce',
                    true,
                    true,
                    $callbackUrl,
                    null
		);


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
    }
    return $vars;
});

// BTCPay posts the new API key here from its own site, so the admin login cookie is usually absent.
// The proof is the one-time state created when the admin started the wizard.
add_action( 'template_redirect', function() {
    global $wp_query;

    if (! isset( $wp_query->query_vars['coinsnap-for-woocommerce-btcpay-settings-callback'] ) ) {
        return;
    }

    $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
    if (!BTCPayApiAuthorization::consumeState($state, (string) get_option('btcpay_server_url', ''))) {
        Logger::security('btcpay callback rejected', 'invalid state');
        wp_die(esc_html__('Unauthorized', 'coinsnap-for-woocommerce'), '', ['response' => 403]);
    }

    $settingsUrl = admin_url('admin.php?page=wc-settings&tab=coinsnap_settings&provider=btcpay');
    $btcpayUrl = (string) get_option('btcpay_server_url', '');
    $apiKey = isset($_POST['apiKey']) ? sanitize_text_field(wp_unslash($_POST['apiKey'])) : '';

    // Ask BTCPay what the key can do; never trust permissions or a store id from the request.
    $apiData = \Coinsnap\Client\BTCPayApiAuthorization::fromServer($btcpayUrl, $apiKey);
    if ($apiData === null) {
        Notice::addFlash('error', __('Could not verify the API key with the stored BTCPay Server URL. Please try again or continue with manual setup.', 'coinsnap-for-woocommerce'));
        wp_safe_redirect($settingsUrl);
        exit;
    }

    if (!$apiData->hasSingleStore() || !$apiData->hasRequiredPermissions()) {
        Notice::addFlash('error', __('The API key must be limited to one store and have exactly the requested store permissions.', 'coinsnap-for-woocommerce'));
        wp_safe_redirect($settingsUrl);
        exit;
    }

    update_option('btcpay_api_key', $apiData->getApiKey());
    update_option('btcpay_store_id', $apiData->getStoreID());
    update_option('coinsnap_provider', 'btcpay');
    Notice::addFlash('success', __('Successfully received api key and store id from BTCPay Server API. Please finish setup by saving this settings form.', 'coinsnap-for-woocommerce'));

    if (CoinsnapApiWebhook::registerWebhook($btcpayUrl, $apiData->getApiKey(), $apiData->getStoreID())) {
        Notice::addFlash('success', __('Successfully registered a new webhook on BTCPay Server.', 'coinsnap-for-woocommerce'));
    }
    else {
        Notice::addFlash('error', __('Could not register a new webhook on the store.', 'coinsnap-for-woocommerce'));
    }

    wp_safe_redirect($settingsUrl);
    exit;
});

// A key and store id belong to the BTCPay server that issued them.
add_action('update_option_btcpay_server_url', [BTCPayApiAuthorization::class, 'onServerUrlChange'], 10, 2);

// Installation routine.
register_activation_hook( __FILE__, function() {
	update_option('coinsnap_permalinks_flushed', 0);
	update_option( COINSNAP_VERSION_KEY, COINSNAP_WC_VERSION );
	require_once COINSNAP_PLUGIN_FILE_PATH . 'library/loader.php';
	\Coinsnap\WC\Helper\UpdateManager::ensureTables();
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


<?php
declare( strict_types=1 );
namespace Coinsnap\WC\Gateway;
defined( 'ABSPATH' ) || exit();

use Coinsnap\Client\Invoice;
use Coinsnap\Client\Store;
use Coinsnap\Util\PreciseNumber;
use Coinsnap\WC\Helper\CoinsnapApiHelper;
use Coinsnap\WC\Helper\CoinsnapApiWebhook;
use Coinsnap\WC\Helper\Logger;
use Coinsnap\WC\Helper\OrderStates;

abstract class AbstractGateway extends \WC_Payment_Gateway {
    const ICON_MEDIA_OPTION = 'icon_media_id';
    public $tokenType;
    public $primaryPaymentMethod;
    protected $apiHelper;

    public function __construct() {
        
        // General gateway setup.
	$this->icon              = $this->getIcon();
	$this->has_fields        = false;
        $this->coinsnap_discount = 0;

	// Load the settings.
	$this->init_form_fields();
	$this->init_settings();

	// Define user facing set variables.
	$this->title        = $this->getTitle();
	$this->description  = $this->getDescription();
        $this->order_button_text = $this->getButton();

	$this->apiHelper = new CoinsnapApiHelper();
		// Debugging & informational settings.
		$this->debug_php_version    = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		$this->debug_plugin_version = COINSNAP_WC_VERSION;

		// Actions.
		add_action('admin_enqueue_scripts', [$this, 'addAdminScripts']);
		add_action('wp_enqueue_scripts', [$this, 'addPublicScripts']);
		add_action('woocommerce_update_options_payment_gateways_' . $this->getId(), [$this, 'process_admin_options']);
                add_action('woocommerce_cart_calculate_fees', [$this, 'cartCoinsnapDiscount'] , 10, 1 );
	

        // Supported features.
        $this->supports = ['products'];
    }
    
    
    public function cartCoinsnapDiscount($cart){
        if (WC()->session->chosen_payment_method == 'coinsnap') {
            
            $discount_enabled = (null !== $this->get_option('discount_enable') && $this->get_option('discount_enable') > 0)? true : false;
            if($discount_enabled){
            
                $cart_amount = $cart->cart_contents_total;

                $discount_type = $this->get_option('discount_type');
                $isDiscount = false;

                if($discount_type === 'fixed' && floatval($this->get_option('discount_amount')) > 0){
                    $discount_amount = round(floatval($this->get_option('discount_amount')),2);
                    $discount_amount_limit = floatval($this->get_option('discount_amount_limit'));

                    if($discount_amount > 0 && $discount_amount_limit > 0 && $discount_amount_limit < 100){
                        if($discount_amount > ($cart_amount * $discount_amount_limit / 100)){
                            $discount_amount = round($cart_amount * $discount_amount_limit / 100,2);
                        }
                        if($discount_amount < $cart_amount){
                            $isDiscount = true;
                        }
                    }
                }
                elseif(null !== $this->get_option('discount_percentage')) {
                    $discount_percentage = $this->get_option('discount_percentage');

                    if($discount_percentage > 0 && $discount_percentage < 100){
                        $discount_amount = $cart_amount * $discount_percentage / 100;
                        $isDiscount = true;
                    }
                }
                if($isDiscount){
                    $cart->add_fee( __('Coinsnap discount','coinsnap-for-woocommerce'), -$discount_amount );
                    $this->coinsnap_discount = $discount_amount;
                }
            }
        }
    }

	//  Initialise Gateway Settings Form Fields
	public function init_form_fields() {
            $currency = get_option( 'woocommerce_currency' );
		$this->form_fields = [
			'enabled' => [
				'title'       => __( 'Enabled/Disabled', 'coinsnap-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable this payment gateway', 'coinsnap-for-woocommerce' ),
				'default'     => 'no',
				'value'       => 'yes',
				'desc_tip'    => false,
			],
			'title'       => [
				'title'       => __( 'Title', 'coinsnap-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Controls the name of this payment method as displayed to the customer during checkout.', 'coinsnap-for-woocommerce' ),
				'default'     => $this->getTitle(),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __( 'Customer Message','coinsnap-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Message to explain how the customer will be paying for the purchase.', 'coinsnap-for-woocommerce' ),
				'default'     => $this->getDescription(),
				'desc_tip'    => true,
			],
			'button' => [
				'title'       => __('Button Text','coinsnap-for-woocommerce'),
				'type'        => 'text',
				'description' => __('Text on the button', 'coinsnap-for-woocommerce'),
				'default'     => $this->getButton(),
				'desc_tip'    => true,
			],
			'discount_enable' => [
				'title'       => __( 'Enable discount', 'coinsnap-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable discount', 'coinsnap-for-woocommerce' ),
				'default'     => 'no',
				'value'       => 'yes',
				'desc_tip'    => false,
			],
			'discount_type' => [
				'title'       => __( 'Discount type', 'coinsnap-for-woocommerce' ),
				'type'        => 'select',
				'label'       => __( 'Discount type', 'coinsnap-for-woocommerce' ),
				'description' => sprintf(
                                                    /* translators: 1: Currency */
                                                    __( 'Choose discount type: %1$s or percents', 'coinsnap-for-woocommerce' ),$currency),
				'options'   => [
                                                    'fixed' => 'Fixed',
                                                    'percentage'   => 'Percentage'
                                                ],
				'desc_tip'    => true,
                            'class' => 'discount'
			],
                    
			'discount_amount' => [
				'title'       => sprintf(
                                                    /* translators: 1: Currency */
                                                    __('Discount amount, %1$s', 'coinsnap-for-woocommerce'),$currency),
				'type'        => 'decimal',
				'description' => __('Discount amount', 'coinsnap-for-woocommerce'),
				'default'     => 0,
				'desc_tip'    => true,
                            'class' => 'discount discount-amount',
			],
			'discount_amount_limit' => [
				'title'       => __('Max discount amount, %', 'coinsnap-for-woocommerce'),
				'type'        => 'decimal',
				'description' => __('Max discount amount for fixed discount, %', 'coinsnap-for-woocommerce'),
				'default'     => 0,
				'desc_tip'    => true,
                            'class' => 'discount discount-amount',
			],
			'discount_percentage' => [
				'title'       => __('Discount amount, %', 'coinsnap-for-woocommerce'),
				'type'        => 'decimal',
				'description' => __('Discount amount in percents (%)', 'coinsnap-for-woocommerce'),
				'default'     => 0,
				'desc_tip'    => true,
                            'class' => 'discount discount-percentage',
			],
			'icon_upload' => [
				'type'        => 'icon_upload',
			],
		];
	}

//  @inheritDoc
    public function process_payment( $orderId ) {
        if ( ! $this->apiHelper->configured ) {
            Logger::debug( 'Coinsnap API connection not configured, aborting. Please go to Coinsnap Server settings and set it up.' );
            $message = __( "Can't process order. Please contact us if the problem persists.", 'coinsnap-for-woocommerce' );
            throw new \Exception( esc_html($message) );
	}

	// Load the order and check it.
	$order = new \WC_Order( $orderId );
	if ( $order->get_id() === 0 ) {
            $message = 'Could not load order id ' . $orderId . ', aborting.';
            Logger::debug( $message, true );
            throw new \Exception( esc_html($message) );
	}

	// Check if the order is a modal payment.
        if (null !== filter_input(INPUT_POST,'action',FILTER_SANITIZE_FULL_SPECIAL_CHARS )) {
            $action = filter_input(INPUT_POST,'action',FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            if ( $action === 'coinsnap_modal_checkout' ) {
		Logger::debug( 'process_payment called via modal checkout.' );
            }
	}

	// Check for existing invoice and redirect instead.
	if ( $this->validInvoiceExists( $orderId ) ) {
            $existingInvoiceId = get_post_meta( $orderId, 'Coinsnap_id', true );
            Logger::debug( 'Found existing Coinsnap invoice and redirecting to it. Invoice id: ' . $existingInvoiceId );

            return [
		'result' => 'success',
		'redirect' => $this->apiHelper->getInvoiceRedirectUrl( $existingInvoiceId ),
		'invoiceId' => $existingInvoiceId,
		'orderCompleteLink' => $order->get_checkout_order_received_url(),
            ];
	}

	// Create an invoice.
        Logger::debug( 'Invoice data check' );
        
        $client = new Invoice( $this->apiHelper->url, $this->apiHelper->apiKey );
        $amount = $order->get_total();
        $currency = $order->get_currency();
        
        $_provider = get_option('coinsnap_provider');
        if($_provider == 'btcpay'){
        
            $store = new Store($this->apiHelper->url, $this->apiHelper->apiKey);            
            $storePaymentMethods = $store->getStorePaymentMethods($this->apiHelper->storeId);
            
            if ($storePaymentMethods['code'] === 200) {
                if(!$storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                    $errorMessage = __( 'No payment method is configured on BTCPay server', 'coinsnap-for-woocommerce' );
                    throw new \Exception( esc_html($errorMessage) );
                }
            }
            else {
                Logger::debug( 'Error loading BTCPay store payment methods');
            }
            
            if($storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ),'bitcoin');
            }
            elseif($storePaymentMethods['result']['lightning']){
                $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ),'lightning');
            }
            else {
                $errorMessage = __( 'No payment method is configured on BTCPay server', 'coinsnap-for-woocommerce' );
                throw new \Exception( esc_html($errorMessage) );
            }
        
        }
        else {
            $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ));
        }
        
        
        
        if($checkInvoice['result'] === true){
            Logger::debug( 'Creating invoice on Coinsnap Server' );
            
            if( $invoice = $this->createInvoice( $order ) ) {

                Logger::debug( 'Invoice creation successful, redirecting user.' );

		$url = $invoice->getData()['checkoutLink'];
			
		return [
                    'result' => 'success',
                    'redirect' => $url,
                    'invoiceId' => $invoice->getData()['id'],
                    'orderCompleteLink' => $order->get_checkout_order_received_url(),
		];
            }
        }
        else {
            
            if($checkInvoice['error'] === 'currencyError'){
                $errorMessage = sprintf( 
                /* translators: 1: Currency */
                __( 'Currency %1$s is not supported by Coinsnap', 'coinsnap-for-woocommerce' ), strtoupper( $currency ));
            }      
            elseif($checkInvoice['error'] === 'amountError'){
                $errorMessage = sprintf( 
                /* translators: 1: Amount, 2: Currency */
                __( 'Invoice amount cannot be less than %1$s %2$s', 'coinsnap-for-woocommerce' ), $checkInvoice['min_value'], strtoupper( $currency ));
            }
            if(!empty($errorMessage)){
                Logger::debug( $errorMessage );
            }
            throw new \Exception( esc_html($errorMessage) );
        }
    }

    public function process_admin_options() {
		// Store media id.
		$iconFieldName = 'woocommerce_' . $this->getId() . '_' . self::ICON_MEDIA_OPTION;
                if ($mediaId = sanitize_key(filter_input(INPUT_POST,$iconFieldName,FILTER_SANITIZE_FULL_SPECIAL_CHARS ))) {
			if ($mediaId !== $this->get_option(self::ICON_MEDIA_OPTION)) {
				$this->update_option(self::ICON_MEDIA_OPTION, $mediaId);
			}
		} else {
			// Reset to empty otherwise.
			$this->update_option(self::ICON_MEDIA_OPTION, '');
		}
		return parent::process_admin_options();
	}

	/**
	 * Generate html for handling icon uploads with media manager.
	 *
	 * Note: `generate_$type_html()` is a pattern you can use from WooCommerce Settings API to render custom fields.
	 */
	public function generate_icon_upload_html() {
            $mediaId = $this->get_option(self::ICON_MEDIA_OPTION);
            $iconFieldName = 'woocommerce_' . $this->getId() . '_' . self::ICON_MEDIA_OPTION;
            ob_start();?>
            <tr valign="top">
                <th scope="row" class="titledesc"><?php echo esc_html(__('Gateway Icon:', 'coinsnap-for-woocommerce')); ?></th>
                <td class="forminp" id="coinsnap_icon">
                    <div id="coinsnap_icon_container">
                        <input class="coinsnap-icon-button" type="button"
                            name="woocommerce_coinsnap_icon_upload_button"
                            value="<?php echo esc_html(__('Upload or select icon', 'coinsnap-for-woocommerce')); ?>"
                            style="<?php echo $mediaId ? 'display:none;' : ''; ?>"
			/><?php echo wp_get_attachment_image($mediaId,'full',false,array('class'  => 'coinsnap-icon-image'));?>
			<input class="coinsnap-icon-remove" type="button"
                            name="woocommerce_coinsnap_icon_button_remove"
                            value="<?php echo esc_html(__('Remove image', 'coinsnap-for-woocommerce')); ?>"
                            style="<?php echo $mediaId ? '' : 'display:none;'; ?>"
			/>
			<input class="input-text regular-input coinsnap-icon-value" type="hidden"
                            name="<?php echo esc_attr($iconFieldName); ?>"
                            id="<?php echo esc_attr($iconFieldName); ?>"
                            value="<?php echo esc_attr($mediaId); ?>"
			/>
                    </div>
                </td>
            </tr>
        <?php
            return ob_get_clean();
	}

	public function getId(): string {
		return $this->id;
	}

	/**
	 * Get custom gateway icon, if any.
	 */
	public function getIcon(): string {
		$icon = null;
                //Logger::debug( 'mediaId: '.print_r($this->get_option(self::ICON_MEDIA_OPTION),true) );
		if ($mediaId = $this->get_option(self::ICON_MEDIA_OPTION)) {
			if ($customIcon = wp_get_attachment_image_src($mediaId)[0]) {
				$icon = $customIcon;
			}
		}

		return $icon ?? COINSNAP_PLUGIN_URL . 'assets/images/bitcoin-lightning-logos.png';
	}

	/**
	 * Add scripts.
	 */
	public function addAdminScripts($hook_suffix) {
		if ($hook_suffix === 'woocommerce_page_wc-settings') {
			wp_enqueue_media();
			wp_register_script(
				'coinsnap_abstract_gateway',
				COINSNAP_PLUGIN_URL . 'assets/js/gatewayIconMedia.js',
				['jquery'],
				COINSNAP_WC_VERSION,
                                array('in_footer' => true)
			);
			wp_enqueue_script('coinsnap_abstract_gateway');
			wp_localize_script(
				'coinsnap_abstract_gateway',
				'coinsnapGatewayData',
				[
					'buttonText' => __('Use this image', 'coinsnap-for-woocommerce'),
					'titleText' => __('Insert image', 'coinsnap-for-woocommerce'),
				]
			);
		}
	}

	public function addPublicScripts() {
		// We only load the modal checkout scripts when enabled.
		if (get_option('coinsnap_modal_checkout') !== 'yes') {
			return;
		}

		if ($this->apiHelper->configured === false) {
			return;
		}
	}

	// Process webhooks from Coinsnap.
        public function processWebhook() {
            try {
                // First check if we have any input
                $rawPostData = file_get_contents("php://input");
                if (!$rawPostData) {
                    wp_die('No raw post data received', '', ['response' => 400]);
                }

                // Get headers and check for signature
                $headers = getallheaders();
                $signature = null; $payloadKey = null;
                $_provider = (get_option('coinsnap_provider') === 'btcpay')? 'btcpay' : 'coinsnap';
                
                foreach ($headers as $key => $value) {
                    if ((strtolower($key) === 'x-coinsnap-sig' && $_provider === 'coinsnap') || (strtolower($key) === 'btcpay-sig' && $_provider === 'btcpay')) {
                        $signature = $value;
                        $payloadKey = strtolower($key);
                    }
                }

                // Handle missing or invalid signature
                if (!isset($signature)) {
                    $missingHeader = ($_provider === 'coinsnap')? 'X-Coinsnap-Sig' : 'BTCPay-Sig';
                    Logger::debug("Missing $missingHeader header for Webhook payload request");
                    wp_die('Authentication required', '', ['response' => 401]);
                }

                // Validate the signature
                if (!$this->apiHelper->validWebhookRequest($signature, $rawPostData)) {
                    Logger::debug("Invalid webhook signature for $payloadKey ($signature) received");
                    wp_die('Invalid authentication signature', '', ['response' => 401]);
                }

                // Parse the JSON payload
                $postData = json_decode($rawPostData, false, 512, JSON_THROW_ON_ERROR);

                if (!isset($postData->invoiceId)) {
                    Logger::debug('No Coinsnap invoiceId provided, aborting.');
                    wp_die('No Coinsnap invoiceId provided', '', ['response' => 400]);
                }

                // Load the order by metadata field Coinsnap_id
                $orders = wc_get_orders([
                    'meta_key' => 'Coinsnap_id',
                    'meta_value' => $postData->invoiceId
                ]);

                // Handle no orders found
                if (count($orders) === 0) {
                    Logger::debug('Could not load order by '.ucfirst($_provider).' invoiceId: ' . $postData->invoiceId);
                    wp_die('No order found for this invoiceId.', '', ['response' => 200]);
                }

                // Handle multiple orders found
                if (count($orders) > 1) {
                    Logger::debug('Found multiple orders for invoiceId: ' . $postData->invoiceId);
                    wp_die('Multiple orders found for this invoiceId', '', ['response' => 409]);
                }

                // Process the order status
                $this->processOrderStatus($orders[0], $postData);

                // Return success
                http_response_code(200);
                exit('OK');

            }
            catch (JsonException $e) {
                Logger::debug('Error decoding webhook payload: ' . $e->getMessage());
                Logger::debug($rawPostData);
                wp_die('Invalid JSON payload', '', ['response' => 400]);
            }
            catch (\Throwable $e) {
                Logger::debug('Unexpected error processing webhook: ' . $e->getMessage());
                Logger::debug($rawPostData);
                wp_die('Internal server error', '', ['response' => 500]);
            }
        }

	protected function processOrderStatus(\WC_Order $order, \stdClass $webhookData) {
		if (!in_array($webhookData->type, CoinsnapApiWebhook::WEBHOOK_EVENTS)) {
			Logger::debug('Webhook event received but ignored: ' . $webhookData->type);
			return;
		}

		Logger::debug('Updating order (ID = '.$order->get_id().') status with webhook event received for processing: ' . $webhookData->type);
		// Get configured order states or fall back to defaults.
		if (!$configuredOrderStates = get_option('coinsnap_order_states')) {
			$configuredOrderStates = (new OrderStates())->getDefaultOrderStateMappings();
		}

		switch ($webhookData->type) {
			case 'New':
				if ($webhookData->afterExpiration) {
					$this->updateWCOrderStatus($order, $configuredOrderStates[OrderStates::EXPIRED_PAID_PARTIAL]);
					$order->add_order_note(__('Invoice (partial) payment incoming (unconfirmed) after invoice was already expired.', 'coinsnap-for-woocommerce'));
				} else {
					// No need to change order status here, only leave a note.
					$order->add_order_note(__('Invoice (partial) payment incoming (unconfirmed). Waiting for settlement.', 'coinsnap-for-woocommerce'));
				}

				// Store payment data (exchange rate, address).
				//$this->updateWCOrderPayments($order);
				break;
			
			case 'Settled':
				$order->payment_complete();
				if ($webhookData->overPaid) {
					$order->add_order_note(__('Invoice payment settled but was overpaid.', 'coinsnap-for-woocommerce'));
					$this->updateWCOrderStatus($order, $configuredOrderStates[OrderStates::SETTLED_PAID_OVER]);
				} else {
					$order->add_order_note(__('Invoice payment settled.', 'coinsnap-for-woocommerce'));
					$this->updateWCOrderStatus($order, $configuredOrderStates[OrderStates::SETTLED]);
				}

				// Store payment data (exchange rate, address).
				//$this->updateWCOrderPayments($order);

				break;
			case 'Processing': // The invoice is paid in full.
				$this->updateWCOrderStatus($order, $configuredOrderStates[OrderStates::PROCESSING]);
				if ($webhookData->overPaid) {
					$order->add_order_note(__('Invoice payment received fully with overpayment, waiting for settlement.', 'coinsnap-for-woocommerce'));
				} else {
					$order->add_order_note(__('Invoice payment received fully, waiting for settlement.', 'coinsnap-for-woocommerce'));
				}
				break;
			case 'Expired':
				if ($webhookData->partiallyPaid) {
					$this->updateWCOrderStatus($order, $configuredOrderStates[OrderStates::EXPIRED_PAID_PARTIAL]);
					$order->add_order_note(__('Invoice expired but was paid partially, please check.', 'coinsnap-for-woocommerce'));
				} else {
					$this->updateWCOrderStatus($order, $configuredOrderStates[OrderStates::EXPIRED]);
					$order->add_order_note(__('Invoice expired.', 'coinsnap-for-woocommerce'));
				}
				break;
			case 'Invalid':
				$this->updateWCOrderStatus($order, $configuredOrderStates[OrderStates::INVALID]);
				if ($webhookData->manuallyMarked) {
					$order->add_order_note(__('Invoice manually marked invalid.', 'coinsnap-for-woocommerce'));
				} else {
					$order->add_order_note(__('Invoice became invalid.', 'coinsnap-for-woocommerce'));
				}
				break;
		}
	}

	/**
	 * Checks if the order has already a Coinsnap invoice set and checks if it is still
	 * valid to avoid creating multiple invoices for the same order on Coinsnap Server end.
	 *
	 * @param int $orderId
	 *
	 * @return mixed Returns false if no valid invoice found or the invoice id.
	 */
	protected function validInvoiceExists( int $orderId ): bool {
		// Check order metadata for Coinsnap_id.
		if ( $invoiceId = get_post_meta( $orderId, 'Coinsnap_id', true ) ) {
			// Validate the order status on Coinsnap server.
			$client = new Invoice( $this->apiHelper->url, $this->apiHelper->apiKey );
			try {
				Logger::debug( 'Trying to fetch existing invoice from Coinsnap Server.' );
				$invoice       = $client->getInvoice( $this->apiHelper->storeId, $invoiceId );
				$invalidStates = [ 'Expired', 'Invalid' ];
				if ( in_array( $invoice->getData()['status'], $invalidStates ) ) {
					return false;
				}
			} catch ( \Throwable $e ) {
				Logger::debug( $e->getMessage() );
			}
		}

		return false;
	}

	public function markInvoiceInvalid($invoiceId): void {
		Logger::debug( 'Marking invoice as invalid: ' . $invoiceId);
		try {
			$client = new Invoice( $this->apiHelper->url, $this->apiHelper->apiKey );
			$client->markInvoiceStatus($this->apiHelper->storeId, $invoiceId, 'Invalid');
		} catch (\Throwable $e) {
			Logger::debug('Error marking invoice invalid: ' . $e->getMessage());
		}
	}

	/**
	 * Update WC order status (if a valid mapping is set).
	 */
	public function updateWCOrderStatus(\WC_Order $order, string $status): void {
		if ($status !== OrderStates::IGNORE) {
			Logger::debug('Updating order status from ' . $order->get_status() . ' to ' . $status);
			$order->update_status($status);
		}
	}

	public function updateWCOrderPayments(\WC_Order $order): void {
		// Load payment data from API.
		try {
			$client = new Invoice( $this->apiHelper->url, $this->apiHelper->apiKey );
			$allPaymentData = $client->getPaymentMethods($this->apiHelper->storeId, $order->get_meta('Coinsnap_id'));

			foreach ($allPaymentData as $payment) {
				// Only continue if the payment method has payments made.
				if ((float) $payment->getPaymentMethodPaid() > 0.0) {
					$paymentMethodName = $payment->getPaymentMethod();
					// Update order meta data with payment methods and transactions.
					$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_total_paid", $payment->getTotalPaid() ?? '' );
					$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_total_amount", $payment->getAmount() ?? '' );
					$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_total_due", $payment->getDue() ?? '' );
					$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_total_fee", $payment->getNetworkFee() ?? '' );
					$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_rate", $payment->getRate() ?? '' );
					if ((float) $payment->getRate() > 0.0) {
						$formattedRate = number_format((float) $payment->getRate(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
						$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_rateFormatted", $formattedRate );
					}

					// For each actual payment make a separate entry to make sense of it.
					foreach ($payment->getPayments() as $index => $trx) {
						$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_{$index}_id", $trx->getTransactionId() ?? '' );
						$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_{$index}_timestamp", $trx->getReceivedTimestamp() ?? '' );
						$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_{$index}_destination", $trx->getDestination() ?? '' );
						$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_{$index}_amount", $trx->getValue() ?? '' );
						$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_{$index}_status", $trx->getStatus() ?? '' );
						$order->update_meta_data(  "Coinsnap_{$paymentMethodName}_{$index}_networkFee", $trx->getFee() ?? '' );
					}
                                        
                                        // Save the order.
					$order->save();
				}
			}
		} catch (\Throwable $e) {
			Logger::debug( 'Error processing payment data for invoice: ' . $order->get_meta('Coinsnap_id') . ' and order ID: ' . $order->get_id() );
			Logger::debug($e->getMessage());
		}
	}

	/**
	 * Create an invoice on Coinsnap Server.
	 */
	public function createInvoice( \WC_Order $order ): ?\Coinsnap\Result\Invoice {
            
            // In case some plugins customizing the order number we need to pass that along, defaults to internal ID.
            $orderNumber = $order->get_order_number();
            $orderID = ''.$order->get_id();
            Logger::debug( 'Got order number: ' . $orderNumber . ' (order ID: ' . $orderID .')' );

            $metadata = [];
                
            // Send customer data only if option is set.
            if ( get_option( 'coinsnap_send_customer_data' ) ) {
                $metadata = $this->prepareCustomerMetadata( $order );
            }
                
            $buyerEmail = $this->prepareCustomerMetadata( $order )['buyerEmail'];
            $buyerName = $this->prepareCustomerMetadata( $order )['buyerName'];

            // Set included tax amount.
            $metadata['taxIncluded'] = $order->get_cart_tax();

            if(isset($orderNumber) && !empty($orderNumber)){
                $metadata['orderNumber'] = $orderNumber;
            }
                
            $redirectUrl = $this->get_return_url( $order );
            $currency = $order->get_currency();
            $amount = PreciseNumber::parseString( $order->get_total() );		

            // Handle Sats-mode because BTCPay does not understand SAT as a currency we need to change to BTC and adjust the amount.
            if ($currency === 'SATS' && get_option('coinsnap_provider') === 'btcpay') {
                $currency = 'BTC';
                $amountBTC = bcdiv($amount->__toString(), '100000000', 8);
                $amount = PreciseNumber::parseString($amountBTC);
            }
                
            //  Set automatic redirect after payment and wallet message (empty)
            $redirectAutomatically = (get_option('coinsnap_autoredirect', 'yes') === 'yes')? true : false;
            Logger::debug( 'Setting redirect automatically: ' . $redirectAutomatically );
            
            $walletMessage = ($this->coinsnap_discount > 0)? 'Coinsnap discount: '.$this->coinsnap_discount.' '.$currency : '';
            $metadata['coinsnapDiscount'] = $this->coinsnap_discount;

            // Create the invoice on Coinsnap Server.
            $client = new Invoice( $this->apiHelper->url, $this->apiHelper->apiKey );
            Logger::debug( 'Client for invoice is created' );
                
            try {
                $invoice = $client->createInvoice(
                    $this->apiHelper->storeId,
                    $currency,
                    $amount,
                    $orderID,
                    $buyerEmail,
                    $buyerName,
                    $redirectUrl,
                    COINSNAP_WC_REFERRAL_CODE,
                    $metadata,
                    $redirectAutomatically,
                    $walletMessage
                );

                $this->updateOrderMetadata( $order->get_id(), $invoice );
                return $invoice;
            }
            catch ( \Throwable $e ) {
                Logger::debug( $e->getMessage(), true );
            }

            return null;
	}

	/**
	 * Maps customer billing metadata.
	 */
	protected function prepareCustomerMetadata( \WC_Order $order ): array {
            return [
		'buyerEmail'    => $order->get_billing_email(),
		'buyerName'     => $order->get_formatted_billing_full_name(),
		'buyerAddress1' => $order->get_billing_address_1(),
		'buyerAddress2' => $order->get_billing_address_2(),
		'buyerCity'     => $order->get_billing_city(),
		'buyerState'    => $order->get_billing_state(),
		'buyerZip'      => $order->get_billing_postcode(),
		'buyerCountry'  => $order->get_billing_country()
            ];
	}

	/**
	 * Maps POS metadata.
	 */
	protected function preparePosMetadata( $order ): string {
            $posData = [
                'WooCommerce' => [
                    'Order ID'       => $order->get_id(),
                    'Order Number'   => $order->get_order_number(),
                    'Order URL'      => $order->get_edit_order_url(),
                    'Plugin Version' => constant( 'COINSNAP_WC_VERSION' )
                ]
            ];

            return wp_json_encode( $posData, JSON_THROW_ON_ERROR );
	}

	/**
	 * References WC order metadata with Coinsnap invoice data.
	 */
	protected function updateOrderMetadata( int $orderId, \Coinsnap\Result\Invoice $invoice ) {
		// Store relevant Coinsnap invoice data.
                $order = wc_get_order($orderId);
		$order->update_meta_data( 'Coinsnap_redirect', $invoice->getData()['checkoutLink'] );
		$order->update_meta_data( 'Coinsnap_invoiceId', $invoice->getData()['invoiceId'] );
		$order->update_meta_data( 'Coinsnap_id', $invoice->getData()['id'] );
                Logger::debug( 'Store relevant Coinsnap invoice data for order ' . $orderId . ': Coinsnap_id: ' . $invoice->getData()['id'] );
                $order->save();
	}

	/**
	 * Return the total quantity of the whole order for all line items.
	 */
	public function getOrderTotalItemsQuantity(\WC_Order $order): int {
		$total = 0;
		foreach ($order->get_items() as $item ) {
			$total += $item->get_quantity();
		}

		return $total;
	}

	/**
	 * Get customer visible gateway title.
	 */
	public function getTitle(): string {
		return $this->get_option('title', 'Bitcoin + Lightning');
	}

	/**
	 * Get customer facing gateway description.
	 */
	public function getDescription(): string {
		return $this->get_option('description', 'You will be redirected to the Bitcoin-Lightning Payment Page to complete your purchase');
	}

	/**
	 * Get customer button text.
	 */
	public function getButton(): string {
		return $this->get_option('button', 'Pay with Bitcoin');
	}

	/**
	 * Get type of Coinsnap payment method/token as configured. Can be payment or promotion.
	 */
	public function getTokenType(): string {
		return $this->get_option('token_type', 'payment');
	}

	/**
	 * Get allowed Coinsnap payment methods (needed for limiting invoices to specific payment methods).
	 */
	abstract public function getPaymentMethods(): array;
}

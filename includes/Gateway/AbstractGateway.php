<?php
declare( strict_types=1 );
namespace Coinsnap\WC\Gateway;
defined( 'ABSPATH' ) || exit();

use Coinsnap\Client\Invoice;
use Coinsnap\Client\Store;
use Coinsnap\Exception\RequestException;
use Coinsnap\Util\PreciseNumber;
use Coinsnap\WC\Helper\CoinsnapApiHelper;
use Coinsnap\WC\Helper\CoinsnapApiWebhook;
use Coinsnap\WC\Helper\Logger;
use Coinsnap\WC\Helper\OrderStates;

abstract class AbstractGateway extends \WC_Payment_Gateway {
    const ICON_MEDIA_OPTION = 'icon_media_id';
    protected $apiHelper;
    public $tokenType;
    public $primaryPaymentMethod;
    public $coinsnap_discount;
    public $debug_php_version;
    public $debug_plugin_version;

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

        // Supported features.
        $this->supports = ['products'];
        
        // Actions.
	add_action('admin_enqueue_scripts', [$this, 'addAdminScripts']);
	add_action('wp_enqueue_scripts', [$this, 'addPublicScripts']);
	add_action('woocommerce_update_options_payment_gateways_' . $this->getId(), [$this, 'process_admin_options']);

        if(!is_admin() && get_self_link() !== wc_get_cart_url()){
            add_action('woocommerce_cart_calculate_fees', [$this, 'cartCoinsnapDiscount'] , 10, 1 );
        }
    }
    
    /**
     * Discount value indication
     */
    public function getDiscount(): string {
	$discount_enabled = ($this->get_option('discount_enable') === 'yes');
        if($discount_enabled){
            $discount_type = $this->get_option('discount_type');
            if($discount_type === 'fixed' && floatval($this->get_option('discount_amount')) > 0){
                return '<span class="discount_value">-'.esc_html(floatval($this->get_option('discount_amount'))).'</span><span class="discount_currency">'.esc_html(strtoupper(get_option( 'woocommerce_currency' ))).'</span>';
            }
            elseif(null !== $this->get_option('discount_percentage')) {
                return '<span class="discount_percent">-'.esc_html($this->get_option('discount_percentage')).'<small>%</small></span>';
            }
        }
        else {
            return '';
        }
    }
    
    /**
     * Calculate discount on checkout and show in invoice
     */
    public function cartCoinsnapDiscount($cart){
        
        $payment_method = WC()->session->get('chosen_payment_method');
        
        if(empty($payment_method) && $this->get_option('enabled') === 'yes'){
            WC()->session->set('chosen_payment_method', 'coinsnap');
            WC()->cart->calculate_totals();
            WC()->session->save_data();
        }
        
        if ($payment_method === 'coinsnap') {
            
            $discount_enabled = ($this->get_option('discount_enable') === 'yes');
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
                    $cart->add_fee( __('Bitcoin discount','coinsnap-for-woocommerce'), -$discount_amount );
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
            'title' => [
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
                                                    'fixed' => __('Fixed','coinsnap-for-woocommerce'),
                                                    'percentage'   => __('Percentage','coinsnap-for-woocommerce')
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
                'class'       => 'discount discount-amount',
            ],
            'discount_percentage' => [
		'title'       => __('Discount amount, %', 'coinsnap-for-woocommerce'),
		'type'        => 'decimal',
		'description' => __('Discount amount in percents (%)', 'coinsnap-for-woocommerce'),
		'default'     => 0,
		'desc_tip'    => true,
                'class'       => 'discount discount-percentage',
            ],
            'icon_upload' => [
                'type'        => 'icon_upload',
            ],
        ];
    }
    
    public function checkAmount($amount, $currency){
        
        $client = new Invoice( $this->apiHelper->url, $this->apiHelper->apiKey );
        $checkInvoice = [];
        
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
        
        return $checkInvoice;
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
        
        $checkInvoice = $this -> checkAmount($amount, $currency);
        
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

	// Process webhooks from Coinsnap / BTCPay. Only the signature and invoiceId come from the request;
	// everything else is read from the payment server's API.
	public function processWebhook() {
		$rawPostData = (string) file_get_contents('php://input');
		$headers = [];
		foreach (['x-coinsnap-signature', 'x-coinsnap-sig', 'btcpay-sig'] as $name) {
			$key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
			if (isset($_SERVER[$key])) {
				$headers[$name] = sanitize_text_field(wp_unslash($_SERVER[$key]));
			}
		}
		$this->handleWebhook($rawPostData, $headers);
	}

	// $headers: lowercase signature header name => value. Always ends with webhookReply().
	public function handleWebhook(string $rawPostData, array $headers): void {
		if ($rawPostData === '' || !$this->apiHelper->validWebhookRequest($headers, $rawPostData)) {
			Logger::security('webhook rejected', 'bad signature');
			$this->webhookReply(401, 'Unauthorized');
		}

		$data = json_decode($rawPostData, true);
		$invoiceId = (is_array($data) && is_string($data['invoiceId'] ?? null)) ? $data['invoiceId'] : '';
		if ($invoiceId === '') {
			$this->webhookReply(200, 'OK');
		}

		try {
			$invoice = (new Invoice($this->apiHelper->url, $this->apiHelper->apiKey))->getInvoice((string) $this->apiHelper->storeId, $invoiceId);
		} catch (RequestException $e) {
			// Not found (Coinsnap 400, BTCPay 404): unknown invoice, e.g. a dashboard test webhook.
			if (in_array($e->getCode(), [400, 404], true)) {
				Logger::security('webhook rejected', 'unknown invoice', $invoiceId);
				$this->webhookReply(200, 'OK');
			}
			// Anything else (5xx, or 401/403 from a revoked key) must not lose the payment: let the provider retry.
			Logger::debug('Webhook: payment server replied ' . $e->getCode() . ' while fetching invoice ' . $invoiceId . '; asking for a retry.', true);
			$this->webhookReply(503, 'Retry');
		} catch (\Throwable $e) {
			Logger::debug('Webhook: could not fetch invoice ' . $invoiceId . ': ' . $e->getMessage());
			$this->webhookReply(503, 'Retry');
		}

		// A body that claims payment while the API disagrees is the clearest sign of a forged webhook.
		$bodyType = is_string($data['type'] ?? null) ? $data['type'] : '';
		if (in_array($bodyType, ['Settled', 'InvoiceSettled'], true) && ($invoice->getData()['status'] ?? '') !== 'Settled') {
			Logger::security('webhook rejected', 'body claims paid, API disagrees', $invoiceId);
		}

		$apiInvoiceId = (string) ($invoice->getData()['id'] ?? '');
		$orders = $apiInvoiceId === '' ? [] : wc_get_orders([
			'meta_key' => 'Coinsnap_id',
			'meta_value' => $apiInvoiceId,
			'limit' => 2,
		]);
		if (count($orders) !== 1) {
			Logger::security('webhook rejected', count($orders) === 0 ? 'no order for invoice' : 'multiple orders for invoice', $invoiceId);
			$this->webhookReply(200, 'OK');
		}

		try {
			$this->processOrderStatus($orders[0], $invoice);
		} catch (\Throwable $e) {
			Logger::debug('Webhook: error updating order for invoice ' . $invoiceId . ': ' . $e->getMessage(), true);
			$this->webhookReply(500, 'Error');
		}
		$this->webhookReply(200, 'OK');
	}

	protected function webhookReply(int $status, string $message): void {
		status_header($status);
		echo esc_html($message);
		exit;
	}

	// Paid means: Settled per the API, invoice bound to this order, and the expected amount and currency.
	public function invoicePaysOrder(\Coinsnap\Result\Invoice $invoice, \WC_Order $order): bool {
		$data = $invoice->getData();
		$isBtcpay = get_option('coinsnap_provider') === 'btcpay';

		if (($data['status'] ?? '') !== 'Settled') {
			return false;
		}
		if ($isBtcpay && ($data['additionalStatus'] ?? '') === 'Marked') {
			return false;
		}

		$boundOrderId = $data['metadata']['orderId'] ?? ($data['orderId'] ?? null);
		if ($boundOrderId !== null && (string) $boundOrderId !== (string) $order->get_id()) {
			return false;
		}

		$expectedAmount = (float) ($order->get_meta('Coinsnap_expected_amount') ?: $order->get_total());
		$expectedCurrency = strtoupper((string) ($order->get_meta('Coinsnap_expected_currency') ?: $order->get_currency()));
		if ($expectedAmount <= 0) {
			return false;
		}
		if (strtoupper((string) ($data['currency'] ?? '')) !== $expectedCurrency) {
			return false;
		}

		[$paid, $tolerance] = $isBtcpay
			? $this->btcpayPaidAmount($data, $expectedCurrency)
			: $this->coinsnapPaidAmount($data, $expectedCurrency);
		if ($paid === null) {
			return false;
		}
		return abs($paid - $expectedAmount) <= $tolerance + 1e-12;
	}

	// BTCPay: amount is in the invoice currency, exactly as created.
	private function btcpayPaidAmount(array $data, string $currency): array {
		$amount = $data['amount'] ?? null;
		$tolerance = ($currency === 'BTC') ? 0.00000001 : (($currency === 'SATS') ? 1 : 0.01);
		return [is_numeric($amount) ? (float) $amount : null, $tolerance];
	}

	// Coinsnap: amount is whole sats; fiatAmount is derived as sats / 1e8 * exchangeRate (null for BTC/SATS invoices),
	// so a fiat comparison must allow for rounding to a whole sat.
	private function coinsnapPaidAmount(array $data, string $currency): array {
		$sats = $data['amount'] ?? null;
		if ($currency === 'SATS') {
			return [is_numeric($sats) ? (float) $sats : null, 1];
		}
		if ($currency === 'BTC') {
			return [is_numeric($sats) ? (float) $sats / 100000000 : null, 0.00000001];
		}
		$fiat = $data['fiatAmount'] ?? null;
		if (!is_numeric($fiat)) {
			return [null, 0];
		}
		$rate = $data['exchangeRate'] ?? null;
		$satValue = is_numeric($rate) && (float) $rate > 0
			? (float) $rate / 100000000
			: ((is_numeric($sats) && (float) $sats > 0) ? (float) $fiat / (float) $sats : 0);
		return [(float) $fiat, max(0.01, 2 * $satValue)];
	}

	// Records the invoice as handled. Returns false if it was handled before, so payment is completed only once.
	protected function markInvoiceProcessed(string $invoiceId, \WC_Order $order): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'coinsnap_wc_processed';
		$inserted = $wpdb->query($wpdb->prepare(
			"INSERT IGNORE INTO {$table} (invoice_id, order_id, created_at) VALUES (%s, %d, %s)",
			$invoiceId, $order->get_id(), current_time('mysql', true)
		));
		if ($inserted === false) {
			// Table missing: fall back to the order state.
			return !$order->is_paid();
		}
		return $inserted === 1;
	}

	protected function unmarkInvoiceProcessed(string $invoiceId): void {
		global $wpdb;
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}coinsnap_wc_processed WHERE invoice_id = %s", $invoiceId));
	}

	protected function processOrderStatus(\WC_Order $order, \Coinsnap\Result\Invoice $invoice) {
		$data = $invoice->getData();
		$status = (string) ($data['status'] ?? '');
		$invoiceId = (string) ($data['id'] ?? '');

		if (!$configuredOrderStates = get_option('coinsnap_order_states')) {
			$configuredOrderStates = (new OrderStates())->getDefaultOrderStateMappings();
		}
		$mapped = static function (string $key) use ($configuredOrderStates): string {
			return (string) ($configuredOrderStates[$key] ?? OrderStates::IGNORE);
		};

		if ($order->has_status('refunded')) {
			Logger::debug('Webhook for refunded order ' . $order->get_id() . ' ignored (invoice status ' . $status . ').');
			return;
		}

		Logger::debug('Updating order ' . $order->get_id() . ' from API invoice status ' . $status);

		switch ($status) {
			case 'New':
				$order->add_order_note(__('Invoice (partial) payment incoming (unconfirmed). Waiting for settlement.', 'coinsnap-for-woocommerce'));
				break;

			case 'Processing':
				if (!$order->is_paid()) {
					$this->updateWCOrderStatus($order, $mapped(OrderStates::PROCESSING));
					$order->add_order_note(__('Invoice payment received fully, waiting for settlement.', 'coinsnap-for-woocommerce'));
				}
				break;

			case 'Settled':
				if (!$this->invoicePaysOrder($invoice, $order)) {
					Logger::security('webhook rejected', 'not paid per API', $invoiceId);
					$order->add_order_note(__('Coinsnap reported the invoice as settled, but the amount, currency or order did not match, or it was marked settled by hand. The order was not marked paid. Please check the invoice.', 'coinsnap-for-woocommerce'));
					break;
				}
				if (!$this->markInvoiceProcessed($invoiceId, $order)) {
					Logger::debug('Invoice ' . $invoiceId . ' already processed, ignoring repeated webhook.');
					break;
				}
				try {
					$order->payment_complete($invoiceId);
					$order->add_order_note(__('Invoice payment settled.', 'coinsnap-for-woocommerce'));
					$this->updateWCOrderStatus($order, $mapped(OrderStates::SETTLED));
				} catch (\Throwable $e) {
					// Release the invoice so the provider's retry can complete it.
					$this->unmarkInvoiceProcessed($invoiceId);
					throw $e;
				}
				break;

			case 'Expired':
				if (!$order->is_paid()) {
					$this->updateWCOrderStatus($order, $mapped(OrderStates::EXPIRED));
					$partial = ($data['additionalStatus'] ?? '') === 'PaidPartial';
					$order->add_order_note($partial
						? __('Invoice expired but was paid partially, please check.', 'coinsnap-for-woocommerce')
						: __('Invoice expired.', 'coinsnap-for-woocommerce'));
				}
				break;

			case 'Invalid':
				if (!$order->is_paid()) {
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

            // Send customer data (email, name, address) only if the merchant enabled it.
            $sendCustomerData = ( get_option( 'coinsnap_send_customer_data' ) === 'yes' );
            $metadata = $sendCustomerData ? $this->prepareCustomerMetadata( $order ) : [];
            $buyerEmail = $sendCustomerData ? $metadata['buyerEmail'] : null;
            $buyerName = $sendCustomerData ? $metadata['buyerName'] : null;

            // Set included tax amount.
            $metadata['taxIncluded'] = $order->get_cart_tax();

            if(isset($orderNumber) && !empty($orderNumber)){
                $metadata['orderNumber'] = $orderNumber;
            }
            if(get_option('coinsnap_provider') === 'btcpay') {
                $metadata['orderId'] = $orderID;
            }
                
            $redirectUrl = (!empty(get_option('coinsnap_returnurl','')))? get_option('coinsnap_returnurl') : $this->get_return_url( $order );
            
            $currency = strtoupper($order->get_currency());
            $amount = (float)$order->get_total();

            // Create the invoice on Coinsnap Server.
            $client = new Invoice( $this->apiHelper->url, $this->apiHelper->apiKey );
            
            $checkInvoice = $this -> checkAmount($amount, $currency);

            if($checkInvoice['result'] === true){

                if(get_option('coinsnap_provider') === 'btcpay') {
                    $metadata['orderId'] = $orderID;
                }

                if(get_option('coinsnap_provider') === 'btcpay' && $currency !== 'BTC'){
                    $store = new \Coinsnap\Client\Store($this->apiHelper->url, $this->apiHelper->apiKey);
                    $btcpayCurrencies = $store -> getStoreCurrenciesRates($this->apiHelper->storeId,array($currency));
                    $isCurrency = true;
                    if(!isset($btcpayCurrencies['result']['error']) && count($btcpayCurrencies['result']['currencies'])>0){
                            if(!isset($btcpayCurrencies['result']['currencies']['BTC_'.$currency])){
                                $isCurrency = false;
                            }
                    }
                    else {
                        $isCurrency = false;
                    }

                    // Handle currencies non-supported by BTCPay Server, we need to change them BTC and adjust the amount.
                    if( !$isCurrency ){
                            $currency = 'BTC';
                            $rate = 1/$checkInvoice['rate'];
                            $amountBTC = bcdiv(strval($amount), strval($rate), 8);
                            $amount = (float)$amountBTC;
                    }
                }

                $camount = ($currency === 'BTC')? PreciseNumber::parseFloat($amount,8) : PreciseNumber::parseFloat($amount,2);


                //  Set automatic redirect after payment and wallet message (empty)
                $redirectAutomatically = (get_option('coinsnap_autoredirect', 'yes') === 'yes')? true : false;
                Logger::debug( 'Setting redirect automatically: ' . $redirectAutomatically );

                $walletMessage = ($this->coinsnap_discount > 0)? 'Coinsnap discount: '.$this->coinsnap_discount.' '.$currency : '';
                $metadata['coinsnapDiscount'] = $this->coinsnap_discount;
                
                Logger::debug( 'Client for invoice is created. Payment amount is '.$amount.' '.$currency );
                
                try {
                    $invoice = $client->createInvoice(
                        $this->apiHelper->storeId,
                        $currency,
                        $camount,
                        $orderID,
                        $buyerEmail,
                        $buyerName,
                        $redirectUrl,
                        COINSNAP_WC_REFERRAL_CODE,
                        $metadata,
                        $redirectAutomatically,
                        $walletMessage
                    );

                    $this->updateOrderMetadata( $order->get_id(), $invoice, $camount->__toString(), $currency );
                    return $invoice;
                }
                catch ( \Throwable $e ) {
                    Logger::debug( $e->getMessage(), true );
                }
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
	protected function updateOrderMetadata( int $orderId, \Coinsnap\Result\Invoice $invoice, string $expectedAmount, string $expectedCurrency ) {
		// Store relevant Coinsnap invoice data.
                $order = wc_get_order($orderId);
                $orderTransactionURL = (get_option('coinsnap_provider') === 'btcpay')? get_option('btcpay_server_url').'/invoices/'.$invoice->getData()['id'] : COINSNAP_SERVER_URL.'/td/'.$invoice->getData()['id'];
		$order->update_meta_data( 'Coinsnap_transactionurl', $orderTransactionURL );
		$order->update_meta_data( 'Coinsnap_redirect', $invoice->getData()['checkoutLink'] );
		$order->update_meta_data( 'Coinsnap_id', $invoice->getData()['id'] );
		$order->update_meta_data( 'Coinsnap_expected_amount', $expectedAmount );
		$order->update_meta_data( 'Coinsnap_expected_currency', $expectedCurrency );
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

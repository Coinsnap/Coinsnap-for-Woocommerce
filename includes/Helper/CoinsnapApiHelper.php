<?php

declare(strict_types=1);
namespace Coinsnap\WC\Helper;

use Coinsnap\Client\Invoice;
use Coinsnap\Client\Server;
use Coinsnap\Client\Store;
use Coinsnap\Client\StorePaymentMethod;
use Coinsnap\Client\Webhook;
use Coinsnap\Result\AbstractStorePaymentMethodResult;
use Coinsnap\Result\ServerInfo;
use Coinsnap\WC\Admin\Notice;

class CoinsnapApiHelper {
    const PM_CACHE_KEY = 'coinsnap_payment_methods';
    const PM_CLASS_NAME_PREFIX = 'COINSNAP_';
    
    public $configured = false;
    public $url;
    public $apiKey;
    public $storeId;

    public function __construct() {
        if ( $config = self::getConfig() ) {
            $this->url = $config['url'];
            $this->apiKey = $config['api_key'];
            $this->storeId = $config['store_id'];
            $this->webhook = $config['webhook'];
            $this->configured = true;
        }
    }
    
    public static function getConfig(): array {
        $url = COINSNAP_SERVER_URL;
        $key = get_option('coinsnap_api_key');
        if($url && $key) {
            return [
                'url' => rtrim( $url, '/' ),
                'api_key' => $key,
                'store_id' => get_option( 'coinsnap_store_id', null ),
		'webhook' => get_option('coinsnap_webhook', null)
            ];
        } 
        else return [];
    }
    
    public static function checkApiConnection(): bool {
        if ($config = self::getConfig()) {
            $client = new Store($config['url'], $config['api_key']);
            if (!empty($store = $client->getStore($config['store_id']))) {
                return true;
            }
        }
        return false;
    }
    
   public static function getApiConnectionSettings() {
        if ($config = self::getConfig()) {
            $client = new Store($config['url'], $config['api_key']);
            $store = $client->getStore($config['store_id']);
            
            if (!isset($store['error'])) return (array)$store;
            else return array('result' => false, 'error' => 'Coinsnap server is not available');
        }
        return array('result' => false, 'error' => 'Plugin is not configured');
    }

    public static function getServerInfo(): ?ServerInfo {
		if ($config = self::getConfig()) {
			try {
				$client = new Server( $config['url'], $config['api_key'] );
				return $client->getInfo();
			} catch (\Throwable $e) {
				Logger::debug('Error fetching server info: ' . $e->getMessage(), true);
				return null;
			}
		}

		return null;
	}

    //  List supported payment methods by Coinsnap Server.
    public static function supportedPaymentMethods(): array {
		$paymentMethods = [];

		// Use transients API to cache pm for a few minutes to avoid too many requests to Coinsnap Server.
		if ($cachedPaymentMethods = get_transient(self::PM_CACHE_KEY)) {
			return $cachedPaymentMethods;
		}

		if ($config = self::getConfig()) {
			$client = new StorePaymentMethod($config['url'], $config['api_key']);
			if ($storeId = get_option('coinsnap_store_id')) {
				try {
					$pmResult = $client->getPaymentMethods($storeId);
					/** @var AbstractStorePaymentMethodResult $pm */
					foreach ($pmResult as $pm) {
                                            print_r($pm);
						if ($pm->isEnabled() && $pmName = $pm->getData()['paymentMethod'] )  {
							// Convert - to _ and escape value for later use in gateway class generator.
							$symbol = sanitize_html_class(str_replace('-', '_', $pmName));
							$paymentMethods[] = [
								'symbol' => $symbol,
								'className' => self::PM_CLASS_NAME_PREFIX . $symbol
							];
						}
					}
				} catch (\Throwable $e) {
					$exceptionPM = 'Exception loading payment methods: ' . $e->getMessage();
					Logger::debug( $exceptionPM, true);
					Notice::addNotice('error', $exceptionPM);
				}
			}
		}

		// Store payment methods into cache.
		set_transient( self::PM_CACHE_KEY, $paymentMethods,5 * MINUTE_IN_SECONDS );

		return $paymentMethods;
	}

	//  Deletes local cache of supported payment methods.
	public static function clearSupportedPaymentMethodsCache(): void {
		delete_transient( self::PM_CACHE_KEY );
	}

	//  Returns Coinsnap Server invoice url.
	public function getInvoiceRedirectUrl($invoiceId): ?string {
		if ($this->configured) {
			return $this->url . '/i/' . urlencode($invoiceId);
		}
		return null;
	}

	//  Check webhook signature to be a valid request.
	public function validWebhookRequest(string $signature, string $requestData): bool {
            \Coinsnap\WC\Helper\Logger::debug( $signature . ' - ' . $requestData . ' - ' . $this->webhook['secret'], true );
            if ($this->configured) {
                return Webhook::isIncomingWebhookRequestValid($requestData, $signature, $this->webhook['secret']);
            }
            return false;
	}

	//  Checks if the provided API config already exists in options table.
	public static function apiCredentialsExist(string $apiUrl, string $apiKey, string $storeId): bool {
            if ($config = self::getConfig()) {
                if ($config['url'] === $apiUrl && $config['api_key'] === $apiKey && $config['store_id'] === $storeId){
                    return true;
		}
            }
            return false;
	}

	//  Checks if a given invoice id has status of fully paid (settled) or paid late.
	public static function invoiceIsFullyPaid(string $invoiceId): bool {
		if ($config = self::getConfig()) {
			$client = new Invoice($config['url'], $config['api_key']);
			try {
				$invoice = $client->getInvoice($config['store_id'], $invoiceId);
				return $invoice->isFullyPaid() || $invoice->isPaidLate();
			} catch (\Throwable $e) {
				Logger::debug('Exception while checking if invoice settled '. $invoiceId . ': ' . $e->getMessage());
			}
		}

		return false;
	}
}

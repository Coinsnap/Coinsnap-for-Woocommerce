<?php

declare(strict_types=1);

namespace Coinsnap\WC\Helper;

use Coinsnap\Client\Webhook;
use Coinsnap\Result\Webhook as WebhookResult;

class CoinsnapApiWebhook {
    public const COINSNAP_WEBHOOK_EVENTS = ['New','Expired','Settled','Processing','Invalid'];
    public const BTCPAY_WEBHOOK_EVENTS = ['InvoiceCreated','InvoiceExpired','InvoiceSettled','InvoiceProcessing','InvoiceInvalid'];
    public const OPTION = 'coinsnap_wc_webhook';
    private const LEGACY_OPTION = 'coinsnap_webhook';
    private const ROTATED_FLAG = 'coinsnap_wc_webhook_rotated';

    // This plugin's webhook (id, secret, url), falling back to the pre-rotation option.
    public static function getStored() {
        $stored = get_option(self::OPTION);
        return is_array($stored) ? $stored : get_option(self::LEGACY_OPTION);
    }

    // Once per install: create a new webhook so any secret saved before the callback fix stops working.
    public static function maybeRotateWebhook(): void {
        if (get_option(self::ROTATED_FLAG) || wp_doing_ajax() || !current_user_can('manage_options') || get_transient('coinsnap_wc_rotate_wait')) {
            return;
        }
        $config = CoinsnapApiHelper::getConfig();
        if (!$config || empty($config['store_id'])) {
            delete_option(self::LEGACY_OPTION);
            update_option(self::ROTATED_FLAG, 1);
            return;
        }
        $old = self::getStored();
        if (!self::registerWebhook($config['url'], $config['api_key'], $config['store_id'])) {
            set_transient('coinsnap_wc_rotate_wait', 1, HOUR_IN_SECONDS);
            \Coinsnap\WC\Admin\Notice::addNotice('warning', __('Coinsnap could not renew its webhook secret. Please re-save the Coinsnap settings.', 'coinsnap-for-woocommerce'));
            return;
        }
        if (is_array($old) && !empty($old['id'])) {
            try {
                (new Webhook($config['url'], $config['api_key']))->deleteWebhook($config['store_id'], (string) $old['id']);
            } catch (\Throwable $e) {
                Logger::debug('Could not delete the previous webhook: ' . $e->getMessage());
            }
        }
        delete_option(self::LEGACY_OPTION);
        update_option(self::ROTATED_FLAG, 1);
    }

//  Get locally stored webhook data and check if it exists on the store.
    public static function webhookExists(string $apiUrl, string $apiKey, string $storeId): bool {
	$whClient = new Webhook( $apiUrl, $apiKey );
	$storedWebhook = self::getStored();
        $isWebhook = false;
        
        try {
            $storeWebhooks = $whClient->getWebhooks($storeId);
            foreach ($storeWebhooks as $webhook) {
                if(strpos( $webhook->getData()['url'], WC()->api_request_url( 'coinsnap' ) ) !== false){
                    
                    if (is_array($storedWebhook) && $webhook->getData()['id'] === $storedWebhook['id']){
                        $isWebhook = true;
                    }
                    else {
                        $whClient->deleteWebhook( $storeId, $webhook->getData()['id'] );
                        Logger::debug('Deleting webhook ID '.$webhook->getData()['id'].' for '. $webhook->getData()['url']);
                    }
                }
            }
        } catch (\Throwable $e) {
            $errorMessage = 'Error fetching webhooks for store ID '.$storeId.'. Message: ' .$e->getMessage();
            return false;
        }

        return $isWebhook;
    }

	/**
	 * Register a webhook on Coinsnap and store it locally.
	 */
	public static function registerWebhook(string $apiUrl, $apiKey, $storeId): ?WebhookResult {
		try {
			$whClient = new Webhook( $apiUrl, $apiKey );
                        $webhook_events = (get_option('coinsnap_provider')==='btcpay')? self::BTCPAY_WEBHOOK_EVENTS : self::COINSNAP_WEBHOOK_EVENTS;
			$webhook = $whClient->createWebhook(
				$storeId,   //$storeId
				WC()->api_request_url( 'coinsnap' ), //$url
				$webhook_events,   //$specificEvents
				null    //$secret
			);

			// Store in option table.
			update_option(
				self::OPTION,
				[
					'id' => $webhook->getData()['id'],
					'secret' => $webhook->getData()['secret'],
					'url' => $webhook->getData()['url']
				],
				false
			);
                        Logger::debug('New webhook is created');
			return $webhook;
                        
		} catch (\Throwable $e) {
			Logger::debug('Error creating a new webhook on Coinsnap instance: ' . $e->getMessage());
		}

		return null;
	}

	/**
	 * Update an existing webhook on Coinsnap.
	 */
	public static function updateWebhook(
		string $webhookId,
		string $webhookUrl,
		string $secret,
		bool $enabled,
		bool $automaticRedelivery,
		?array $events
	): ?WebhookResult {

		if ($config = CoinsnapApiHelper::getConfig()) {
			try {
				$whClient = new Webhook( $config['url'], $config['api_key'] );
                                $webhook_events = (get_option('coinsnap_provider')==='btcpay')? self::BTCPAY_WEBHOOK_EVENTS : self::COINSNAP_WEBHOOK_EVENTS;
				$webhook = $whClient->updateWebhook(
					$config['store_id'],
					$webhookUrl,
					$webhookId,
					$events ?? $webhook_events,
					$enabled,
					$automaticRedelivery,
					$secret
				);

				return $webhook;
			} catch (\Throwable $e) {
				Logger::debug('Error updating existing Webhook from Coinsnap: ' . $e->getMessage());
				return null;
			}
		} else {
			Logger::debug('Plugin not configured, aborting updating webhook.');
		}

		return null;
	}

	/**
	 * Load existing webhook data from Coinsnap, defaults to locally stored webhook.
	 */
	public static function getWebhook(?string $webhookId): ?WebhookResult {
		$existingWebhook = self::getStored();
		$config = CoinsnapApiHelper::getConfig();

		try {
			$whClient = new Webhook( $config['url'], $config['api_key'] );
			$webhook = $whClient->getWebhook(
				$config['store_id'],
				$webhookId ?? $existingWebhook['id'],
				);

			return $webhook;
		} catch (\Throwable $e) {
			Logger::debug('Error fetching existing Webhook from Coinsnap: ' . $e->getMessage());
		}

		return null;
	}
        
        //  Load existing webhook data from Coinsnap, defaults to locally stored webhook.
	public static function getWebhooks(?string $storeId): ?WebhookResult {
		
            $config = CoinsnapApiHelper::getConfig();

		try {
			$whClient = new Webhook( $config['url'], $config['api_key'] );
			$webhooks = $whClient->getWebhooks($storeId);

			return $webhooks;
		} catch (\Throwable $e) {
			Logger::debug('Error fetching existing Webhook from Coinsnap: ' . $e->getMessage());
		}

		return null;
	}
}

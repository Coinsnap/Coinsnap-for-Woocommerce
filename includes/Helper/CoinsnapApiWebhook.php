<?php

declare(strict_types=1);

namespace Coinsnap\WC\Helper;

use Coinsnap\Client\Webhook;
use Coinsnap\Result\Webhook as WebhookResult;
use Coinsnap\WC\Gateway\DefaultGateway;

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

    public const ROTATE_HOOK = 'coinsnap_wc_rotate_webhook';
    private const ROTATE_WAIT = 'coinsnap_wc_rotate_wait';
    private const ROTATE_LOCK = 'coinsnap_wc_rotate_lock';
    private const ROTATE_ERROR = 'coinsnap_wc_rotate_error';
    private const RECOVERY_MAX_ORDERS = 20;

    // Schedules the one-time secret renewal in WP-Cron so it does not wait for an admin page load.
    public static function scheduleRotation(): void {
        if (get_option(self::ROTATED_FLAG) || get_transient(self::ROTATE_WAIT) || wp_next_scheduled(self::ROTATE_HOOK)) {
            return;
        }
        wp_schedule_single_event(time() + MINUTE_IN_SECONDS, self::ROTATE_HOOK);
    }

    // Admin page load: renew now if still pending, and show why it failed if it did.
    public static function maybeRotateWebhook(): void {
        if (get_option(self::ROTATED_FLAG) || wp_doing_ajax() || !current_user_can('manage_options')) {
            return;
        }
        if (!get_transient(self::ROTATE_WAIT)) {
            self::rotateWebhook();
        }
        $error = get_option(self::ROTATE_ERROR);
        if (is_string($error) && $error !== '' && !get_option(self::ROTATED_FLAG)) {
            \Coinsnap\WC\Admin\Notice::addNotice('warning', $error);
        }
    }

    // Once per install: create a new webhook so any secret saved by an older version (or leaked from its logs)
    // stops working. Returns true when done. Retries at most hourly.
    public static function rotateWebhook(): bool {
        if (get_option(self::ROTATED_FLAG)) {
            return true;
        }
        $lock = get_option(self::ROTATE_LOCK);
        if ($lock && (time() - (int) $lock) < 5 * MINUTE_IN_SECONDS) {
            return false;
        }
        delete_option(self::ROTATE_LOCK);
        if (!add_option(self::ROTATE_LOCK, time(), '', 'no')) {
            return false;
        }

        try {
            $config = CoinsnapApiHelper::getConfig();
            if (!$config || empty($config['store_id'])) {
                self::markRotated();
                return true;
            }
            $old = self::getStored();
            if (!self::registerWebhook($config['url'], $config['api_key'], (string) $config['store_id'])) {
                set_transient(self::ROTATE_WAIT, 1, HOUR_IN_SECONDS);
                update_option(self::ROTATE_ERROR, self::rotationErrorMessage($config), false);
                return false;
            }
            if (is_array($old) && !empty($old['id'])) {
                try {
                    (new Webhook($config['url'], $config['api_key']))->deleteWebhook((string) $config['store_id'], (string) $old['id']);
                } catch (\Throwable $e) {
                    Logger::debug('Could not delete the previous webhook: ' . $e->getMessage());
                }
            }
            self::markRotated();
            self::recoverRecentPayments();
            return true;
        } finally {
            delete_option(self::ROTATE_LOCK);
        }
    }

    // A delivery in flight to the old webhook during the swap is signed with the old secret and rejected,
    // and Coinsnap does not redeliver. Re-check recent unpaid orders so such a payment is not lost.
    private static function recoverRecentPayments(): void {
        $gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : [];
        $gateway = (isset($gateways['coinsnap']) && $gateways['coinsnap'] instanceof DefaultGateway) ? $gateways['coinsnap'] : new DefaultGateway();

        $orders = wc_get_orders([
            'status' => ['pending', 'on-hold', 'failed', 'cancelled'],
            'date_created' => '>' . (time() - 2 * DAY_IN_SECONDS),
            'limit' => 50,
        ]);
        $checked = 0;
        foreach ($orders as $order) {
            if ($checked >= self::RECOVERY_MAX_ORDERS || $order->is_paid() || (string) $order->get_meta('Coinsnap_id') === '') {
                continue;
            }
            $checked++;
            try {
                $gateway->recoverSettledPayment($order);
            } catch (\Throwable $e) {
                Logger::debug('Payment recovery after webhook renewal failed for order ' . $order->get_id() . ': ' . $e->getMessage(), true);
            }
        }
    }

    // The legacy option name is shared with other Coinsnap plugins, so it is left in place.
    private static function markRotated(): void {
        delete_option(self::ROTATE_ERROR);
        update_option(self::ROTATED_FLAG, 1);
    }

    private static function rotationErrorMessage(array $config): string {
        if (get_option('coinsnap_provider') === 'btcpay') {
            $auth = \Coinsnap\Client\BTCPayApiAuthorization::fromServer((string) $config['url'], (string) $config['api_key']);
            if ($auth !== null && !$auth->hasWebhookPermission()) {
                return __('Coinsnap could not renew its webhook secret because the BTCPay API key cannot manage webhooks. Please reconnect BTCPay with the "Generate API key" button in the Coinsnap settings.', 'coinsnap-for-woocommerce');
            }
            if ($auth === null) {
                return __('Coinsnap could not renew its webhook secret because BTCPay Server rejected the API key or could not be reached. Please check the BTCPay connection in the Coinsnap settings.', 'coinsnap-for-woocommerce');
            }
        }
        return __('Coinsnap could not renew its webhook secret. Please check the connection in the Coinsnap settings; it will be retried automatically.', 'coinsnap-for-woocommerce');
    }

    // Whether this plugin's stored webhook exists on the store. Throws when the API can't be asked: an API
    // error is not "no webhook", and answering false would create a new webhook and replace a working secret.
    public static function webhookExists(string $apiUrl, string $apiKey, string $storeId): bool {
        $whClient = new Webhook( $apiUrl, $apiKey );
        $storedWebhook = self::getStored();
        $isWebhook = false;

        foreach ($whClient->getWebhooks($storeId) as $webhook) {
            if (strpos( $webhook->getData()['url'], WC()->api_request_url( 'coinsnap' ) ) === false) {
                continue;
            }
            if (is_array($storedWebhook) && $webhook->getData()['id'] === $storedWebhook['id']) {
                $isWebhook = true;
                continue;
            }
            try {
                $whClient->deleteWebhook( $storeId, $webhook->getData()['id'] );
                Logger::debug('Deleting webhook ID '.$webhook->getData()['id'].' for '. $webhook->getData()['url']);
            } catch (\Throwable $e) {
                Logger::debug('Could not delete stale webhook ' . $webhook->getData()['id'] . ': ' . $e->getMessage());
            }
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
			// A webhook registered by this version has a fresh secret, so the one-time renewal is not needed.
			update_option(self::ROTATED_FLAG, 1);
			delete_option(self::ROTATE_ERROR);
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

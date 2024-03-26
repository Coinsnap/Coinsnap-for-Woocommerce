<?php

declare(strict_types=1);

namespace Coinsnap\WC\Helper;

use Coinsnap\Client\Webhook;
use Coinsnap\Result\Webhook as WebhookResult;

class CoinsnapApiWebhook {
    public const WEBHOOK_EVENTS = ['New','Expired','Settled','Processing'];

//  Get locally stored webhook data and check if it exists on the store.
    public static function webhookExists(string $apiUrl, string $apiKey, string $storeId): bool {
	if ($storedWebhook = get_option( 'coinsnap_webhook' )) {
            try {
				$whClient = new Webhook( $apiUrl, $apiKey );
				$existingWebhook = $whClient->getWebhook( $storeId, $storedWebhook['id'] );
				// Check for the url here as it could have been changed on Coinsnap making the webhook not work for WooCommerce anymore.
				if (
					$existingWebhook->getData()['id'] === $storedWebhook['id'] &&
					strpos( $existingWebhook->getData()['url'], $storedWebhook['url'] ) !== false
				) {
					return true;
				}
			} catch (\Throwable $e) {
				Logger::debug('Error fetching existing Webhook from Coinsnap. Message: ' . $e->getMessage());
			}
		}

		return false;
	}

	/**
	 * Register a webhook on Coinsnap and store it locally.
	 */
	public static function registerWebhook(string $apiUrl, $apiKey, $storeId): ?WebhookResult {
		try {
			$whClient = new Webhook( $apiUrl, $apiKey );
			$webhook = $whClient->createWebhook(
				$storeId,   //$storeId
				WC()->api_request_url( 'coinsnap' ), //$url
				self::WEBHOOK_EVENTS,   //$specificEvents
				null    //$secret
			);

			// Store in option table.
			update_option(
				'coinsnap_webhook',
				[
					'id' => $webhook->getData()['id'],
					'secret' => $webhook->getData()['secret'],
					'url' => $webhook->getData()['url']
				]
			);

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
				$webhook = $whClient->updateWebhook(
					$config['store_id'],
					$webhookUrl,
					$webhookId,
					$events ?? self::WEBHOOK_EVENTS,
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
		$existingWebhook = get_option('coinsnap_webhook');
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

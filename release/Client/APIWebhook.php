<?php
declare(strict_types=1);

namespace Coinsnap\Client;

use Coinsnap\Client\Webhook;
use Coinsnap\Client\Webhook as WebhookResult;

class APIWebhook {
    public const WEBHOOK_EVENTS = ['New','Expired','Settled','Processing'];

//  Get locally stored webhook data and check if it exists on the store.
    public static function webhookExists(string $apiUrl, string $apiKey, string $storeId, $storedWebhook = null): bool {
	if ($storedWebhook) {
            try {
                $webhookClient = new Webhook( $apiUrl, $apiKey );
                $existingWebhook = $webhookClient->getWebhook( $storeId, $storedWebhook['id'] );
                // Check for the url here as it could have been changed on Coinsnap
		if ($existingWebhook->getData()['id'] === $storedWebhook['id'] && strpos( $existingWebhook->getData()['url'], $storedWebhook['url'] ) !== false){
                    return true;
		}
            }
            catch (\Throwable $e) {
                return false;
                //Logger::debug('Error fetching existing Webhook from Coinsnap. Message: ' . $e->getMessage());
            }
	}
        return false;
    }

//  Register a webhook on Coinsnap and store it locally.
    public static function registerWebhook(string $apiUrl, $apiKey, $storeId, $webhookURL): ?array {
        try {
            $whClient = new Webhook( $apiUrl, $apiKey );
            $webhook = $whClient->createWebhook(
		$storeId,   //$storeId
		$webhookURL, //$url
		self::WEBHOOK_EVENTS,   //$specificEvents
		null    //$secret
            );
                        
            $result = array('id' => $webhook->getData()['id'],'secret' => $webhook->getData()['secret'],'url' => $webhook->getData()['url']);
            return $result;
	}
        catch (\Throwable $e) {
            return null;
            //Logger::debug('Error creating a new webhook on Coinsnap instance: ' . $e->getMessage());
            }
	}

//  Update an existing webhook on Coinsnap.
    public static function updateWebhook(string $apiUrl, string $apiKey, string $storeId,string $webhookUrl,string $storedWebhook, bool $enabled): ?WebhookResult {
        try {
            $webhookClient = new Webhook($apiUrl,$apiKey);
            $webhook = $webhookClient->updateWebhook(
                $storeId,
                $storedWebhook['url'],
                $storedWebhook['id'],
                $events ?? self::WEBHOOK_EVENTS,
		$enabled,
		$storedWebhook['secret']
            );

            return $webhook;
	}
        catch (\Throwable $e) {
            //  Logger::debug('Error updating existing Webhook from Coinsnap: ' . $e->getMessage());
            return null;
	}
        return null;
    }

//  Load existing webhook data from Coinsnap, defaults to locally stored webhook.
    public static function getWebhook(?string $webhookId): ?WebhookResult {
		$existingWebhook = get_option('coinsnap_webhook');
		$config = CoinsnapApiHelper::getConfig();

		try {
			$webhookClient = new Webhook( $config['url'], $config['api_key'] );
			$webhook = $webhookClient->getWebhook(
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
			$webhookClient = new Webhook( $config['url'], $config['api_key'] );
			$webhooks = $webhookClient->getWebhooks($storeId);

			return $webhooks;
		} catch (\Throwable $e) {
			Logger::debug('Error fetching existing Webhook from Coinsnap: ' . $e->getMessage());
		}

		return null;
	}
}

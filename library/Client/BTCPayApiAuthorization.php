<?php
declare(strict_types=1);
namespace Coinsnap\Client;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Permissions of a BTCPay API key as reported by the BTCPay server (GET /api/v1/api-keys/current).
 * Never build this from request data.
 */
class BTCPayApiAuthorization {
	public const REQUIRED_PERMISSIONS = [
		'btcpay.store.canviewinvoices',
		'btcpay.store.cancreateinvoice',
		'btcpay.store.canviewstoresettings',
		'btcpay.store.webhooks.canmodifywebhooks',
	];

	private $apiKey;
	private $permissions;

	public function __construct(string $apiKey, array $serverPermissions) {
		$this->apiKey = $apiKey;
		$this->permissions = array_values(array_filter($serverPermissions, 'is_string'));
	}

	/**
	 * Asks the BTCPay server which permissions the key has. Returns null if the server rejects the key or is unreachable.
	 */
	public static function fromServer(string $btcpayUrl, string $apiKey): ?self {
		if ($btcpayUrl === '' || $apiKey === '') {
			return null;
		}
		try {
			$permissions = (new BTCPayApiKey($btcpayUrl, $apiKey))->getCurrentPermissions();
		} catch (\Throwable $e) {
			return null;
		}
		return $permissions === null ? null : new self($apiKey, $permissions);
	}

	public function getApiKey(): string {
		return $this->apiKey;
	}

	/**
	 * The single store every permission is limited to, or '' if the key is not limited to exactly one store.
	 */
	public function getStoreID(): string {
		$storeId = null;
		foreach ($this->permissions as $permission) {
			$parts = explode(':', $permission, 2);
			$scope = $parts[1] ?? '';
			if ($scope === '' || ($storeId !== null && $scope !== $storeId)) {
				return '';
			}
			$storeId = $scope;
		}
		return $storeId ?? '';
	}

	public function hasSingleStore(): bool {
		return $this->getStoreID() !== '';
	}

	/**
	 * True only if every permission is a store permission for the single store and all required ones are present.
	 */
	public function hasRequiredPermissions(): bool {
		$storeId = $this->getStoreID();
		if ($storeId === '') {
			return false;
		}
		foreach ($this->permissions as $permission) {
			if (strpos($permission, 'btcpay.store.') !== 0) {
				return false;
			}
		}
		foreach (self::REQUIRED_PERMISSIONS as $required) {
			if (!in_array($required . ':' . $storeId, $this->permissions, true)) {
				return false;
			}
		}
		return true;
	}

	public function hasWebhookPermission(): bool {
		return in_array('btcpay.store.webhooks.canmodifywebhooks:' . $this->getStoreID(), $this->permissions, true);
	}
}

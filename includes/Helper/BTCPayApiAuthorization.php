<?php
declare(strict_types=1);
namespace Coinsnap\WC\Helper;
defined( 'ABSPATH' ) || exit();

/**
 * One-time state for the BTCPay connect wizard and the permissions it requests.
 */
class BTCPayApiAuthorization {
	public const REQUIRED_PERMISSIONS = \Coinsnap\Client\BTCPayApiAuthorization::REQUIRED_PERMISSIONS;
	public const OPTIONAL_PERMISSIONS = [];

	private const STATE_TRANSIENT = 'coinsnap_wc_btcpay_state';

	// Creates the single pending state for $host and puts it in the callback URL. Starting again replaces it.
	// Call only after the capability and nonce checks.
	public static function startState(string $host): string {
		$state = bin2hex(random_bytes(32));
		set_transient(self::STATE_TRANSIENT, ['hash' => hash('sha256', $state), 'host' => $host], 10 * MINUTE_IN_SECONDS);
		return $state;
	}

	// One use, and only for the BTCPay host the wizard was started for. A wrong value does not remove the pending state.
	public static function consumeState(string $state, string $currentHost): bool {
		$pending = get_transient(self::STATE_TRANSIENT);
		if (!is_array($pending) || !is_string($pending['hash'] ?? null) || !preg_match('/^[a-f0-9]{64}$/', $state)) {
			return false;
		}
		if (!hash_equals($pending['hash'], hash('sha256', $state)) || ($pending['host'] ?? null) !== $currentHost) {
			return false;
		}
		delete_transient(self::STATE_TRANSIENT);
		return true;
	}

	public static function clearState(): void {
		delete_transient(self::STATE_TRANSIENT);
	}

	// Any change of the stored BTCPay URL drops the key, store id and pending state issued for the old host.
	public static function onServerUrlChange($oldValue, $newValue): void {
		if ((string) $oldValue === (string) $newValue) {
			return;
		}
		update_option('btcpay_api_key', '');
		update_option('btcpay_store_id', '');
		self::clearState();
	}
}

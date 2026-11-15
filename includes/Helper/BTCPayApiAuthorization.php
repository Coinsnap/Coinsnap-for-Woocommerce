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

	private const STATE_PREFIX = 'coinsnap_wc_btcpay_state_';

	// Creates a state to put in the callback URL. Call only after the capability and nonce checks.
	public static function startState(): string {
		$state = bin2hex(random_bytes(32));
		set_transient(self::stateKey($state), hash('sha256', $state), 10 * MINUTE_IN_SECONDS);
		return $state;
	}

	// One use: a matching state is deleted when checked. Keyed by the state's hash, so a wrong value cannot remove a pending one.
	public static function consumeState(string $state): bool {
		if (!preg_match('/^[a-f0-9]{64}$/', $state)) {
			return false;
		}
		$key = self::stateKey($state);
		$expected = get_transient($key);
		if (!is_string($expected) || !hash_equals($expected, hash('sha256', $state))) {
			return false;
		}
		delete_transient($key);
		return true;
	}

	private static function stateKey(string $state): string {
		return self::STATE_PREFIX . substr(hash('sha256', $state), 0, 40);
	}
}

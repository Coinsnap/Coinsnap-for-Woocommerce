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

	// Creates a state to put in the callback URL. Call only after the capability and nonce checks.
	public static function startState(): string {
		$state = bin2hex(random_bytes(32));
		set_transient(self::STATE_TRANSIENT, hash('sha256', $state), 10 * MINUTE_IN_SECONDS);
		return $state;
	}

	// Checks the state from the callback. The stored state is deleted on the first try, even if the check fails.
	public static function consumeState(string $state): bool {
		$expected = get_transient(self::STATE_TRANSIENT);
		delete_transient(self::STATE_TRANSIENT);
		return is_string($expected) && $expected !== '' && $state !== '' && hash_equals($expected, hash('sha256', $state));
	}
}

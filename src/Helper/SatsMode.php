<?php

declare( strict_types=1 );

namespace Coinsnap\WC\Helper;

class SatsMode {

	private static $instance;

	public $currencies = [
		'SAT' => ["Satoshis", "Sats"]
	];

	public function __construct() {
		add_filter( 'woocommerce_currencies', [ $this, 'addCurrency' ] );
		add_filter( 'woocommerce_currency_symbol', [ $this, 'addSymbol' ], 10, 2 );
	}

	public function addCurrency($currencies) {
		foreach ( $this->currencies as $code => $curr ) {
                    $curr_code = $curr[0];
                    $currencies[$code] = $curr[0]; // __($curr_code, 'coinsnap-for-woocommerce');
		}
		return $currencies;
	}

	public function addSymbol($symbol, $currency) {
		if ($currency === 'SAT'){
			$symbol = 'Sats';
                }
		return $symbol;
	}

	public static function instance(): SatsMode {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

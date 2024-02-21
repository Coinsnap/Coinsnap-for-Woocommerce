<?php

declare( strict_types=1 );

namespace Coinsnap\WC\Helper;

class Logger {

	public static function debug( string $message, $force = false): void {
		if ( get_option( 'coinsnap_debug' ) === 'yes' || $force ) {
			// Convert message to string
			if ( ! is_string( $message ) ) {
				$message = wc_print_r( $message, true );
			}

			$logger = new \WC_Logger();
			$context = array( 'source' => COINSNAP_PLUGIN_ID );
			$logger->debug( $message, $context );
		}
	}

	public static function getLogFileUrl(): string {
		$log_file = COINSNAP_PLUGIN_ID . '-' . gmdate('Y-m-d') . '-' . sanitize_file_name( wp_hash( COINSNAP_PLUGIN_ID ) ) . '-log';
		return esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' . $log_file));
	}

}

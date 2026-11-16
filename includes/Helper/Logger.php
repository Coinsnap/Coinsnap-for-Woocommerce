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
			$context = array( 'source' => COINSNAP_WC_PLUGIN_ID );
			$logger->debug( $message, $context );
		}
	}

	// Always logged. $id comes from the request, so it is cleaned before it reaches the log.
	// No IP: it is personal data, and the web server's access log already has it.
	public static function security( string $event, string $reason, string $id = '' ): void {
		$id = substr( (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $id ), 0, 64 );
		self::debug( sprintf( 'SECURITY %s reason=%s id=%s', $event, $reason, $id !== '' ? $id : '-' ), true );
	}

	public static function getLogFileUrl(): string {
		$log_file = COINSNAP_WC_PLUGIN_ID . '-' . gmdate('Y-m-d') . '-' . sanitize_file_name( wp_hash( COINSNAP_WC_PLUGIN_ID ) ) . '-log';
		return esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' . $log_file));
	}

}

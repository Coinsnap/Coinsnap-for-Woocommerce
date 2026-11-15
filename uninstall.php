<?php
// Removes the plugin's secrets, settings and tables when the plugin is deleted.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit();

function coinsnap_wc_uninstall_site(): void {
	global $wpdb;

	$options = [
		// Credentials and webhook secrets.
		'coinsnap_api_key', 'coinsnap_store_id', 'btcpay_api_key', 'btcpay_store_id', 'btcpay_server_url',
		'coinsnap_webhook', 'coinsnap_wc_webhook',
		// Settings.
		'coinsnap_provider', 'coinsnap_autoredirect', 'coinsnap_connection_status_display', 'coinsnap_debug',
		'coinsnap_default_description', 'coinsnap_modal_checkout', 'coinsnap_order_states', 'coinsnap_returnurl',
		'coinsnap_sats_mode', 'coinsnap_send_customer_data', 'coinsnap_separate_gateways',
		'coinsnap_payment_methods_generated', 'woocommerce_coinsnap_settings',
		// Internal state.
		'coinsnap_version', 'coinsnap_permalinks_flushed', 'coinsnap_wc_db_version', 'coinsnap_wc_webhook_rotated',
		'coinsnap_wc_rotate_error', 'coinsnap_wc_rotate_lock',
	];
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	foreach ( [ 'coinsnap_payment_methods', 'coinsnap_wc_btcpay_state', 'coinsnap_wc_flash_notices', 'coinsnap_wc_rotate_wait' ] as $transient ) {
		delete_transient( $transient );
	}

	wp_clear_scheduled_hook( 'coinsnap_wc_rotate_webhook' );

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}coinsnap_wc_processed" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

if ( is_multisite() ) {
	foreach ( get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) as $siteId ) {
		switch_to_blog( (int) $siteId );
		coinsnap_wc_uninstall_site();
		restore_current_blog();
	}
} else {
	coinsnap_wc_uninstall_site();
}

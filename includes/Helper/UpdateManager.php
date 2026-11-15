<?php

namespace Coinsnap\WC\Helper;

use Coinsnap\WC\Admin\Notice;

class UpdateManager {

    private static $updates = [
	//	'1.0.1' => 'update-1.0.1.php'
    ];

    // Table that records handled invoices so a payment is completed only once.
    public static function ensureTables(): void {
        if (get_option('coinsnap_wc_db_version') === '1') {
            return;
        }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'coinsnap_wc_processed';
        dbDelta("CREATE TABLE {$table} (
  invoice_id varchar(64) NOT NULL,
  order_id bigint(20) unsigned NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (invoice_id)
) " . $wpdb->get_charset_collate() . ";");
        update_option('coinsnap_wc_db_version', '1');
    }

    //  Runs updates if available or just updates the stored version.
    public static function processUpdates() {
        self::ensureTables();

	// Check stored version to see if update is needed, will only run once.
	$runningVersion = get_option( COINSNAP_VERSION_KEY, '1.0' );

	if ( version_compare( $runningVersion, COINSNAP_WC_VERSION, '<' ) ) {

            // Run update scripts if there are any.
            foreach ( self::$updates as $updateVersion => $filename ) {
		if ( version_compare( $runningVersion, $updateVersion, '<' ) ) {
                    $file = COINSNAP_PLUGIN_FILE_PATH . 'updates/' . $filename;
                    if ( file_exists( $file ) ) {
                        include $file;
                    }
                    $runningVersion = $updateVersion;
                    update_option( COINSNAP_VERSION_KEY, $updateVersion );
                    Notice::addNotice('success', 'Coinsnap: successfully ran updates to version ' . $runningVersion, true);
		}
            }
            update_option( COINSNAP_VERSION_KEY, COINSNAP_WC_VERSION );
        }
    }
}

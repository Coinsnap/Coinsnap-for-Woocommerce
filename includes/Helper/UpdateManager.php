<?php

namespace Coinsnap\WC\Helper;

use Coinsnap\WC\Admin\Notice;

class UpdateManager {

    private static $updates = [
	//	'1.0.1' => 'update-1.0.1.php'
    ];

    // Orders created before invoices were recorded per order are accepted only this long after the upgrade (guide 6.7).
    public const UPGRADED_AT = 'coinsnap_wc_security_upgraded_at';
    public const LEGACY_WINDOW = DAY_IN_SECONDS;

    public static function legacyWindowOpen(): bool {
        $at = (int) get_option(self::UPGRADED_AT, 0);
        return $at > 0 && (time() - $at) <= self::LEGACY_WINDOW;
    }

    // Table that records handled invoices so a payment is completed only once.
    public static function ensureTables(): void {
        if (get_option('coinsnap_wc_db_version') === '2') {
            return;
        }
        if (!get_option(self::UPGRADED_AT)) {
            add_option(self::UPGRADED_AT, time(), '', 'no');
        }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'coinsnap_wc_processed';
        dbDelta("CREATE TABLE {$table} (
  invoice_id varchar(64) NOT NULL,
  order_id bigint(20) unsigned NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (invoice_id),
  KEY order_id (order_id)
) " . $wpdb->get_charset_collate() . ";");
        update_option('coinsnap_wc_db_version', '2');
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

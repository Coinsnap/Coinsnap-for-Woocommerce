<?php

namespace Coinsnap\WC\Helper;

use Coinsnap\WC\Admin\Notice;

class UpdateManager {

    private static $updates = [
	//	'1.0.1' => 'update-1.0.1.php'
    ];

    //  Runs updates if available or just updates the stored version.
    public static function processUpdates() {

	// Check stored version to see if update is needed, will only run once.
	$runningVersion = get_option( COINSNAP_VERSION_KEY, '1.0' );

	if ( version_compare( $runningVersion, COINSNAP_VERSION, '<' ) ) {

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
            update_option( COINSNAP_VERSION_KEY, COINSNAP_VERSION );
        }
    }
}

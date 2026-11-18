<?php

declare(strict_types=1);
namespace Coinsnap\WC\Admin;

class Notice {
    private const FLASH_TRANSIENT = 'coinsnap_wc_flash_notices';

//  Adds notice to the admin UI.
    public static function addNotice( string $level, string $message, bool $dismissible = false): void {
        add_action(
            'admin_notices',
            function () use ( $level, $message, $dismissible ) {
                $dismiss = $dismissible ? ' is-dismissible' : '';?>
		<div class="notice notice-<?php echo esc_attr( $level ) . esc_attr( $dismiss ); ?>" style="padding:12px 12px">
                    <?php echo '<strong>Coinsnap Server:</strong> ' . wp_kses_post( $message ) ?>
		</div><?php
            }
	);
    }

    // Stores a notice that survives a redirect; shown once to the next admin page load.
    public static function addFlash( string $level, string $message ): void {
        $notices = get_transient(self::FLASH_TRANSIENT);
        $notices = is_array($notices) ? $notices : [];
        $notices[] = ['level' => $level, 'message' => $message];
        set_transient(self::FLASH_TRANSIENT, array_slice($notices, -5), 10 * MINUTE_IN_SECONDS);
    }

    public static function showFlash(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $notices = get_transient(self::FLASH_TRANSIENT);
        if (!is_array($notices)) {
            return;
        }
        delete_transient(self::FLASH_TRANSIENT);
        foreach ($notices as $notice) {
            self::addNotice((string) ($notice['level'] ?? 'info'), (string) ($notice['message'] ?? ''), true);
        }
    }
}

<?php
namespace WPMailPro;

defined( 'ABSPATH' ) || exit;

class Deactivator {

    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'wmp_weekly_report' );
        wp_clear_scheduled_hook( 'wmp_cleanup_logs' );
        flush_rewrite_rules();
    }
}

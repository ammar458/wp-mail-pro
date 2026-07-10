<?php
namespace WPMailPro\Admin;

defined( 'ABSPATH' ) || exit;

class Notices {

    public function display(): void {
        if ( get_transient( 'wmp_settings_saved' ) ) {
            delete_transient( 'wmp_settings_saved' );
            echo '<div class="notice notice-success is-dismissible"><p><strong>WP Mail Pro:</strong> Settings saved successfully.</p></div>';
        }

        if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'wp-mail-pro' ) === 0 ) {
            $mailer = \WPMailPro\Helpers::get_current_mailer();
            $class  = \WPMailPro\Mail\Mailer::get_mailer_class( $mailer );

            if ( $class ) {
                $instance = new $class();
                if ( ! $instance->is_configured() ) {
                    echo '<div class="notice notice-warning"><p><strong>WP Mail Pro:</strong> Your mailer is not fully configured. Emails may not send correctly.</p></div>';
                }
            }
        }
    }
}

<?php
namespace WPMailPro\Alerts;

defined( 'ABSPATH' ) || exit;

class AlertManager {

    public function dispatch_failure( array $mail_data ): void {
        if ( get_option( 'wmp_alert_email_enabled' ) ) {
            ( new EmailAlert() )->send( $mail_data );
        }
    }
}

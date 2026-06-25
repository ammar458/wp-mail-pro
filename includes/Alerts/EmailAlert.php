<?php
namespace WPMailPro\Alerts;

defined( 'ABSPATH' ) || exit;

class EmailAlert {

    public function send( array $mail_data ): void {
        $to = (string) get_option( 'wmp_alert_email_to', get_option( 'admin_email' ) );
        if ( ! $to ) {
            return;
        }

        $site    = get_bloginfo( 'name' );
        $subject = "[{$site}] Email delivery failed";

        $to_str  = is_array( $mail_data['to'] ) ? implode( ', ', $mail_data['to'] ) : $mail_data['to'];
        $error   = is_array( $mail_data['error'] ?? '' ) ? implode( "\n", $mail_data['error'] ) : ( $mail_data['error'] ?? '' );

        $body = "<h2>Email Delivery Failed</h2>"
            . "<p><strong>Site:</strong> {$site}</p>"
            . "<p><strong>To:</strong> " . esc_html( $to_str ) . "</p>"
            . "<p><strong>Subject:</strong> " . esc_html( $mail_data['subject'] ?? '' ) . "</p>"
            . "<p><strong>Error:</strong> " . nl2br( esc_html( $error ) ) . "</p>"
            . "<p><strong>Time:</strong> " . current_time( 'mysql' ) . "</p>"
            . "<p><a href='" . admin_url( 'admin.php?page=wp-mail-pro-log' ) . "'>View Email Log</a></p>";

        // Use PHP mail directly to avoid infinite loop
        add_filter( 'wp_mail_content_type', fn() => 'text/html' );
        wp_mail( $to, $subject, $body );
        remove_filter( 'wp_mail_content_type', fn() => 'text/html' );
    }
}

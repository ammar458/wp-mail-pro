<?php
namespace WPMailPro;

defined( 'ABSPATH' ) || exit;

class Helpers {

    public static function get_mailer_list(): array {
        return [
            'smtp'      => 'SMTP',
            'gmail'     => 'Gmail / Google Workspace',
            'outlook'   => 'Microsoft 365 / Outlook',
            'sendgrid'  => 'SendGrid',
            'mailgun'   => 'Mailgun',
            'amazonses' => 'Amazon SES',
            'postmark'  => 'Postmark',
            'brevo'     => 'Brevo (Sendinblue)',
            'sparkpost' => 'SparkPost / Bird',
        ];
    }

    public static function get_encryption_list(): array {
        return [
            ''    => 'None',
            'ssl' => 'SSL',
            'tls' => 'TLS',
        ];
    }

    public static function mask_string( string $value, int $visible = 4 ): string {
        if ( strlen( $value ) <= $visible ) {
            return str_repeat( '*', strlen( $value ) );
        }
        return str_repeat( '*', strlen( $value ) - $visible ) . substr( $value, - $visible );
    }

    public static function human_time_diff_since( string $datetime ): string {
        return human_time_diff( strtotime( $datetime ), time() ) . ' ago';
    }

    public static function format_recipients( $recipients ): string {
        if ( is_array( $recipients ) ) {
            return implode( ', ', $recipients );
        }
        return (string) $recipients;
    }

    public static function get_current_mailer(): string {
        return (string) get_option( 'wmp_mailer', 'smtp' );
    }

    public static function is_log_enabled(): bool {
        return (bool) get_option( 'wmp_log_emails', true );
    }

    public static function nonce_field( string $action ): void {
        wp_nonce_field( $action, '_wmp_nonce' );
    }

    public static function verify_nonce( string $action ): bool {
        return isset( $_POST['_wmp_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wmp_nonce'] ) ), $action );
    }
}

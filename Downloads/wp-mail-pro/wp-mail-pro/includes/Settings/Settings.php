<?php
namespace WPMailPro\Settings;

defined( 'ABSPATH' ) || exit;

use WPMailPro\Helpers;

class Settings {

    public function save(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        if ( ! Helpers::verify_nonce( 'wmp_save_settings' ) ) {
            wp_die( 'Invalid nonce' );
        }

        $mailer = sanitize_key( $_POST['wmp_mailer'] ?? 'smtp' );
        update_option( 'wmp_mailer', $mailer );
        update_option( 'wmp_from_name',        sanitize_text_field( $_POST['wmp_from_name'] ?? '' ) );
        update_option( 'wmp_from_email',       sanitize_email( $_POST['wmp_from_email'] ?? '' ) );
        update_option( 'wmp_reply_to',         sanitize_email( $_POST['wmp_reply_to'] ?? '' ) );
        update_option( 'wmp_force_from_name',  isset( $_POST['wmp_force_from_name'] ) ? 1 : 0 );
        update_option( 'wmp_force_from_email', isset( $_POST['wmp_force_from_email'] ) ? 1 : 0 );
        update_option( 'wmp_log_emails',       isset( $_POST['wmp_log_emails'] ) ? 1 : 0 );
        update_option( 'wmp_log_retention',    (int) ( $_POST['wmp_log_retention'] ?? 30 ) );

        // Mailer-specific settings
        $this->save_smtp_settings();
        $this->save_sendgrid_settings();
        $this->save_mailgun_settings();
        $this->save_amazonses_settings();
        $this->save_postmark_settings();
        $this->save_brevo_settings();
        $this->save_sparkpost_settings();
        $this->save_gmail_settings();
        $this->save_outlook_settings();

        // Alert settings
        update_option( 'wmp_alert_email_enabled', isset( $_POST['wmp_alert_email_enabled'] ) ? 1 : 0 );
        update_option( 'wmp_alert_email_to',      sanitize_email( $_POST['wmp_alert_email_to'] ?? '' ) );

        // Backup mailer
        update_option( 'wmp_backup_mailer_enabled', isset( $_POST['wmp_backup_mailer_enabled'] ) ? 1 : 0 );
        update_option( 'wmp_backup_mailer',         sanitize_key( $_POST['wmp_backup_mailer'] ?? '' ) );

        set_transient( 'wmp_settings_saved', true, 30 );

        wp_safe_redirect( admin_url( 'admin.php?page=wp-mail-pro&saved=1' ) );
        exit;
    }

    private function save_smtp_settings(): void {
        update_option( 'wmp_smtp', [
            'host'       => sanitize_text_field( $_POST['wmp_smtp_host'] ?? '' ),
            'port'       => (int) ( $_POST['wmp_smtp_port'] ?? 587 ),
            'encryption' => sanitize_key( $_POST['wmp_smtp_encryption'] ?? 'tls' ),
            'auth'       => isset( $_POST['wmp_smtp_auth'] ) ? 1 : 0,
            'user'       => sanitize_text_field( $_POST['wmp_smtp_user'] ?? '' ),
            'pass'       => sanitize_text_field( $_POST['wmp_smtp_pass'] ?? '' ),
        ] );
    }

    private function save_sendgrid_settings(): void {
        update_option( 'wmp_sendgrid', [
            'api_key' => sanitize_text_field( $_POST['wmp_sendgrid_api_key'] ?? '' ),
        ] );
    }

    private function save_mailgun_settings(): void {
        update_option( 'wmp_mailgun', [
            'api_key' => sanitize_text_field( $_POST['wmp_mailgun_api_key'] ?? '' ),
            'domain'  => sanitize_text_field( $_POST['wmp_mailgun_domain'] ?? '' ),
            'region'  => sanitize_key( $_POST['wmp_mailgun_region'] ?? 'us' ),
        ] );
    }

    private function save_amazonses_settings(): void {
        update_option( 'wmp_amazonses', [
            'access_key' => sanitize_text_field( $_POST['wmp_ses_access_key'] ?? '' ),
            'secret_key' => sanitize_text_field( $_POST['wmp_ses_secret_key'] ?? '' ),
            'region'     => sanitize_key( $_POST['wmp_ses_region'] ?? 'us-east-1' ),
        ] );
    }

    private function save_postmark_settings(): void {
        update_option( 'wmp_postmark', [
            'server_token' => sanitize_text_field( $_POST['wmp_postmark_server_token'] ?? '' ),
        ] );
    }

    private function save_brevo_settings(): void {
        update_option( 'wmp_brevo', [
            'api_key' => sanitize_text_field( $_POST['wmp_brevo_api_key'] ?? '' ),
        ] );
    }

    private function save_sparkpost_settings(): void {
        update_option( 'wmp_sparkpost', [
            'api_key' => sanitize_text_field( $_POST['wmp_sparkpost_api_key'] ?? '' ),
        ] );
    }

    private function save_gmail_settings(): void {
        $existing = (array) get_option( 'wmp_gmail', [] );
        update_option( 'wmp_gmail', array_merge( $existing, [
            'client_id'     => sanitize_text_field( $_POST['wmp_gmail_client_id'] ?? '' ),
            'client_secret' => sanitize_text_field( $_POST['wmp_gmail_client_secret'] ?? '' ),
        ] ) );
    }

    private function save_outlook_settings(): void {
        $existing = (array) get_option( 'wmp_outlook', [] );
        update_option( 'wmp_outlook', array_merge( $existing, [
            'client_id'     => sanitize_text_field( $_POST['wmp_outlook_client_id'] ?? '' ),
            'client_secret' => sanitize_text_field( $_POST['wmp_outlook_client_secret'] ?? '' ),
            'tenant_id'     => sanitize_text_field( $_POST['wmp_outlook_tenant_id'] ?? 'common' ),
        ] ) );
    }
}

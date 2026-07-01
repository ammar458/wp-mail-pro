<?php
namespace WPMailPro\Mail;

defined( 'ABSPATH' ) || exit;

use WPMailPro\Helpers;

class Mailer {

    private static array $mailer_map = [
        'smtp'       => \WPMailPro\Mailers\SMTP::class,
        'phpmailer'  => \WPMailPro\Mailers\PHPMailer::class,
        'gmail'      => \WPMailPro\Mailers\Gmail::class,
        'outlook'    => \WPMailPro\Mailers\Outlook::class,
        'sendgrid'   => \WPMailPro\Mailers\SendGrid::class,
        'mailgun'    => \WPMailPro\Mailers\Mailgun::class,
        'amazonses'  => \WPMailPro\Mailers\AmazonSES::class,
        'postmark'   => \WPMailPro\Mailers\Postmark::class,
        'brevo'      => \WPMailPro\Mailers\Brevo::class,
        'sparkpost'  => \WPMailPro\Mailers\SparkPost::class,
    ];

    // Mailers that send via HTTP API and should bypass PHPMailer entirely
    private static array $api_mailers = [ 'gmail', 'outlook' ];

    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $mailer_key = Helpers::get_current_mailer();

        // API-based mailers don't configure PHPMailer — they are handled in pre_wp_mail
        if ( in_array( $mailer_key, self::$api_mailers, true ) ) {
            return;
        }

        $class = self::$mailer_map[ $mailer_key ] ?? null;
        if ( ! $class ) {
            return;
        }

        /** @var \WPMailPro\Mailers\MailerInterface $mailer */
        $mailer = new $class();

        if ( $mailer->is_configured() ) {
            $mailer->configure( $phpmailer );
        }

        // Force from name / email globally
        $from_name  = (string) get_option( 'wmp_from_name' );
        $from_email = (string) get_option( 'wmp_from_email' );

        if ( get_option( 'wmp_force_from_name' ) && $from_name ) {
            $phpmailer->FromName = $from_name;
        }

        if ( get_option( 'wmp_force_from_email' ) && $from_email ) {
            $phpmailer->From = $from_email;
        }

        // Reply-To
        $reply_to = (string) get_option( 'wmp_reply_to' );
        if ( $reply_to ) {
            $phpmailer->addReplyTo( $reply_to );
        }
    }

    /**
     * For API-based mailers (Gmail, Outlook), intercept wp_mail using pre_wp_mail
     * and send directly via HTTP API, then return true to short-circuit PHPMailer.
     */
    public function maybe_send_via_api( array $mail_data ): bool {
        $mailer_key = Helpers::get_current_mailer();

        if ( ! in_array( $mailer_key, self::$api_mailers, true ) ) {
            return false; // Let PHPMailer handle it
        }

        $class = self::$mailer_map[ $mailer_key ] ?? null;
        if ( ! $class ) {
            return false;
        }

        $mailer = new $class();

        if ( ! $mailer->is_configured() ) {
            return false;
        }

        $success = $mailer->send( $mail_data );

        // Log the result
        if ( Helpers::is_log_enabled() ) {
            $log_data           = $mail_data;
            $log_data['status'] = $success ? 'sent' : 'failed';
            $log_data['error']  = $success ? '' : 'API send failed. Check your OAuth token and API permissions.';
            ( new \WPMailPro\Log\Logger() )->log( $log_data );
        }

        if ( ! $success ) {
            do_action( 'wp_mail_failed', new \WP_Error( 'wp_mail_failed', 'API send failed.', $mail_data ) );
        }

        return true; // Signal that we handled it
    }

    public static function get_mailer_class( string $key ): ?string {
        return self::$mailer_map[ $key ] ?? null;
    }
}

<?php
namespace WPMailPro\Mail;

defined( 'ABSPATH' ) || exit;

use WPMailPro\Log\Logger;
use WPMailPro\Helpers;

class Processor {

    private static array $current_mail = [];

    public function before_send( array $args ): array {
        self::$current_mail = $args;
        add_action( 'wp_mail_succeeded', [ $this, 'on_success' ] );
        return $args;
    }

    public function on_success( array $mail_data ): void {
        if ( ! Helpers::is_log_enabled() ) {
            return;
        }

        ( new Logger() )->log( array_merge( $mail_data, [ 'status' => 'sent' ] ) );

        // Fire alerts if configured
        do_action( 'wmp_email_sent', $mail_data );
    }

    public static function get_current_mail(): array {
        return self::$current_mail;
    }
}

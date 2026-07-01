<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

abstract class AbstractMailer implements MailerInterface {

    protected array $settings = [];

    public function __construct() {
        $key            = $this->get_option_key();
        $this->settings = (array) get_option( $key, [] );
    }

    abstract protected function get_option_key(): string;

    protected function get( string $key, $default = '' ) {
        return $this->settings[ $key ] ?? $default;
    }

    protected function set_from( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $from_name  = (string) get_option( 'wmp_from_name', get_bloginfo( 'name' ) );
        $from_email = (string) get_option( 'wmp_from_email', get_option( 'admin_email' ) );

        if ( $from_email ) {
            $phpmailer->setFrom( $from_email, $from_name );
        }
    }
}

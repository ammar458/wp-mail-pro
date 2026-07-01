<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

class SMTP extends AbstractMailer {

    public function get_name(): string {
        return 'SMTP';
    }

    protected function get_option_key(): string {
        return 'wmp_smtp';
    }

    public function is_configured(): bool {
        return ! empty( $this->get( 'host' ) ) && ! empty( $this->get( 'port' ) );
    }

    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $phpmailer->isSMTP();
        $phpmailer->Host       = $this->get( 'host' );
        $phpmailer->Port       = (int) $this->get( 'port', 587 );
        $phpmailer->SMTPAuth   = (bool) $this->get( 'auth', true );
        $phpmailer->Username   = $this->get( 'user' );
        $phpmailer->Password   = $this->get( 'pass' );

        $encryption = $this->get( 'encryption', 'tls' );
        if ( 'ssl' === $encryption ) {
            $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ( 'tls' === $encryption ) {
            $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $phpmailer->SMTPSecure = '';
            $phpmailer->SMTPAutoTLS = false;
        }

        $this->set_from( $phpmailer );
    }
}

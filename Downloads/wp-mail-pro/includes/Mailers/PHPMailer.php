<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

class PHPMailer extends AbstractMailer {

    public function get_name(): string {
        return 'PHPMailer';
    }

    protected function get_option_key(): string {
        return 'wmp_phpmailer';
    }

    public function is_configured(): bool {
        return true; // No external credentials needed
    }

    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $phpmailer->isMail();

        $sender_type = $this->get( 'sender', 'mail' );
        if ( 'sendmail' === $sender_type ) {
            $phpmailer->isSendmail();
            $sendmail_path = $this->get( 'sendmail_path', '/usr/sbin/sendmail' );
            if ( $sendmail_path ) {
                $phpmailer->Sendmail = $sendmail_path;
            }
        }

        $this->set_from( $phpmailer );
    }
}

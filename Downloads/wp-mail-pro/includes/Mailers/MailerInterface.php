<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

interface MailerInterface {

    /**
     * Configure PHPMailer instance with this mailer's settings.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer
     */
    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void;

    /**
     * Return true if this mailer is properly configured.
     */
    public function is_configured(): bool;

    /**
     * Return the display name of this mailer.
     */
    public function get_name(): string;
}

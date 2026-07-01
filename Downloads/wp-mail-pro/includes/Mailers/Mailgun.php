<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

class Mailgun extends AbstractMailer {

    public function get_name(): string {
        return 'Mailgun';
    }

    protected function get_option_key(): string {
        return 'wmp_mailgun';
    }

    public function is_configured(): bool {
        return ! empty( $this->get( 'api_key' ) ) && ! empty( $this->get( 'domain' ) );
    }

    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $region = $this->get( 'region', 'us' );
        $host   = ( 'eu' === $region ) ? 'smtp.eu.mailgun.org' : 'smtp.mailgun.org';

        $phpmailer->isSMTP();
        $phpmailer->Host       = $host;
        $phpmailer->Port       = 587;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $phpmailer->Username   = 'postmaster@' . $this->get( 'domain' );
        $phpmailer->Password   = $this->get( 'api_key' );

        $this->set_from( $phpmailer );
    }

    public function send_via_api( array $mail_data ): bool {
        $api_key = $this->get( 'api_key' );
        $domain  = $this->get( 'domain' );
        $region  = $this->get( 'region', 'us' );

        if ( ! $api_key || ! $domain ) {
            return false;
        }

        $base_url = ( 'eu' === $region )
            ? 'https://api.eu.mailgun.net/v3/'
            : 'https://api.mailgun.net/v3/';

        $response = wp_remote_post( $base_url . $domain . '/messages', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( 'api:' . $api_key ),
            ],
            'body' => [
                'from'    => get_option( 'wmp_from_name' ) . ' <' . get_option( 'wmp_from_email' ) . '>',
                'to'      => is_array( $mail_data['to'] ) ? implode( ',', $mail_data['to'] ) : $mail_data['to'],
                'subject' => $mail_data['subject'],
                'html'    => $mail_data['message'],
            ],
            'timeout' => 15,
        ] );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
    }
}

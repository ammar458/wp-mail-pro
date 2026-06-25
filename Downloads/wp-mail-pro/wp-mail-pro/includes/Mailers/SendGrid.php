<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

class SendGrid extends AbstractMailer {

    public function get_name(): string {
        return 'SendGrid';
    }

    protected function get_option_key(): string {
        return 'wmp_sendgrid';
    }

    public function is_configured(): bool {
        return ! empty( $this->get( 'api_key' ) );
    }

    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        // SendGrid supports SMTP relay
        $phpmailer->isSMTP();
        $phpmailer->Host       = 'smtp.sendgrid.net';
        $phpmailer->Port       = 587;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $phpmailer->Username   = 'apikey';
        $phpmailer->Password   = $this->get( 'api_key' );

        $this->set_from( $phpmailer );
    }

    /**
     * Send via SendGrid HTTP API (alternative to SMTP relay).
     */
    public function send_via_api( array $mail_data ): bool {
        $api_key = $this->get( 'api_key' );
        if ( ! $api_key ) {
            return false;
        }

        $body = [
            'personalizations' => [
                [
                    'to'      => $this->format_addresses( $mail_data['to'] ),
                    'subject' => $mail_data['subject'],
                ],
            ],
            'from'    => [
                'email' => get_option( 'wmp_from_email' ),
                'name'  => get_option( 'wmp_from_name' ),
            ],
            'content' => [
                [
                    'type'  => 'text/html',
                    'value' => $mail_data['message'],
                ],
            ],
        ];

        $response = wp_remote_post( 'https://api.sendgrid.com/v3/mail/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 15,
        ] );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 202;
    }

    private function format_addresses( $addresses ): array {
        $result = [];
        if ( is_string( $addresses ) ) {
            $addresses = explode( ',', $addresses );
        }
        foreach ( (array) $addresses as $address ) {
            $result[] = [ 'email' => trim( $address ) ];
        }
        return $result;
    }
}

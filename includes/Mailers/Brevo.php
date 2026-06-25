<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

class Brevo extends AbstractMailer {

    public function get_name(): string {
        return 'Brevo';
    }

    protected function get_option_key(): string {
        return 'wmp_brevo';
    }

    public function is_configured(): bool {
        return ! empty( $this->get( 'api_key' ) );
    }

    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $phpmailer->isSMTP();
        $phpmailer->Host       = 'smtp-relay.brevo.com';
        $phpmailer->Port       = 587;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $phpmailer->Username   = (string) get_option( 'wmp_from_email' );
        $phpmailer->Password   = $this->get( 'api_key' );

        $this->set_from( $phpmailer );
    }

    public function send_via_api( array $mail_data ): bool {
        $api_key = $this->get( 'api_key' );
        if ( ! $api_key ) {
            return false;
        }

        $to = is_array( $mail_data['to'] ) ? $mail_data['to'] : explode( ',', $mail_data['to'] );
        $recipients = array_map( fn( $email ) => [ 'email' => trim( $email ) ], $to );

        $response = wp_remote_post( 'https://api.brevo.com/v3/smtp/email', [
            'headers' => [
                'api-key'      => $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode( [
                'sender'      => [
                    'name'  => get_option( 'wmp_from_name' ),
                    'email' => get_option( 'wmp_from_email' ),
                ],
                'to'          => $recipients,
                'subject'     => $mail_data['subject'],
                'htmlContent' => $mail_data['message'],
            ] ),
            'timeout' => 15,
        ] );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 201;
    }
}

<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

class Postmark extends AbstractMailer {

    public function get_name(): string {
        return 'Postmark';
    }

    protected function get_option_key(): string {
        return 'wmp_postmark';
    }

    public function is_configured(): bool {
        return ! empty( $this->get( 'server_token' ) );
    }

    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $phpmailer->isSMTP();
        $phpmailer->Host       = 'smtp.postmarkapp.com';
        $phpmailer->Port       = 587;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $phpmailer->Username   = $this->get( 'server_token' );
        $phpmailer->Password   = $this->get( 'server_token' );

        $this->set_from( $phpmailer );
    }

    public function send_via_api( array $mail_data ): bool {
        $token = $this->get( 'server_token' );
        if ( ! $token ) {
            return false;
        }

        $response = wp_remote_post( 'https://api.postmarkapp.com/email', [
            'headers' => [
                'Accept'                  => 'application/json',
                'Content-Type'            => 'application/json',
                'X-Postmark-Server-Token' => $token,
            ],
            'body' => wp_json_encode( [
                'From'     => get_option( 'wmp_from_name' ) . ' <' . get_option( 'wmp_from_email' ) . '>',
                'To'       => is_array( $mail_data['to'] ) ? implode( ',', $mail_data['to'] ) : $mail_data['to'],
                'Subject'  => $mail_data['subject'],
                'HtmlBody' => $mail_data['message'],
            ] ),
            'timeout' => 15,
        ] );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
    }
}

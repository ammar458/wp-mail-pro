<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

class SparkPost extends AbstractMailer {

    public function get_name(): string {
        return 'SparkPost / Bird';
    }

    protected function get_option_key(): string {
        return 'wmp_sparkpost';
    }

    public function is_configured(): bool {
        return ! empty( $this->get( 'api_key' ) );
    }

    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $phpmailer->isSMTP();
        $phpmailer->Host       = 'smtp.sparkpostmail.com';
        $phpmailer->Port       = 587;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $phpmailer->Username   = 'SMTP_Injection';
        $phpmailer->Password   = $this->get( 'api_key' );

        $this->set_from( $phpmailer );
    }

    public function send_via_api( array $mail_data ): bool {
        $api_key = $this->get( 'api_key' );
        if ( ! $api_key ) {
            return false;
        }

        $to = is_array( $mail_data['to'] ) ? $mail_data['to'] : explode( ',', $mail_data['to'] );
        $recipients = array_map( fn( $email ) => [ 'address' => [ 'email' => trim( $email ) ] ], $to );

        $response = wp_remote_post( 'https://api.sparkpost.com/api/v1/transmissions', [
            'headers' => [
                'Authorization' => $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode( [
                'recipients' => $recipients,
                'content'    => [
                    'from'    => [
                        'name'  => get_option( 'wmp_from_name' ),
                        'email' => get_option( 'wmp_from_email' ),
                    ],
                    'subject' => $mail_data['subject'],
                    'html'    => $mail_data['message'],
                ],
            ] ),
            'timeout' => 15,
        ] );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
    }
}

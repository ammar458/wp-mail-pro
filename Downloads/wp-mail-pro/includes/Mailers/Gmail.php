<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

class Gmail extends AbstractMailer {

    public function get_name(): string {
        return 'Gmail / Google Workspace';
    }

    protected function get_option_key(): string {
        return 'wmp_gmail';
    }

    public function is_configured(): bool {
        return ! empty( $this->get( 'client_id' ) )
            && ! empty( $this->get( 'client_secret' ) )
            && ! empty( $this->get( 'access_token' ) );
    }

    /**
     * We override wp_mail entirely via a filter instead of using PHPMailer SMTP,
     * because WordPress's bundled PHPMailer does not support XOAUTH2.
     * Gmail sending goes through the Gmail REST API.
     */
    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        // Hook the actual send to the API — we cancel PHPMailer's send via exception
        // and handle it ourselves in send_via_api(), called from Mail\Mailer before phpmailer fires.
        // Nothing to configure on PHPMailer directly for API-based sending.
    }

    /**
     * Send via Gmail REST API using the RFC 2822 raw message format.
     */
    public function send( array $mail_data ): bool {
        $access_token = $this->get_valid_access_token();
        if ( ! $access_token ) {
            return false;
        }

        $raw = $this->build_raw_message( $mail_data );
        if ( ! $raw ) {
            return false;
        }

        $response = wp_remote_post(
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( [ 'raw' => $raw ] ),
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        return $code === 200;
    }

    private function build_raw_message( array $mail_data ): string {
        $from_name  = (string) get_option( 'wmp_from_name', get_bloginfo( 'name' ) );
        $from_email = (string) get_option( 'wmp_from_email', get_option( 'admin_email' ) );

        $to = $mail_data['to'] ?? '';
        if ( is_array( $to ) ) {
            $to = implode( ', ', $to );
        }

        $subject = $mail_data['subject'] ?? '(no subject)';
        $body    = $mail_data['message'] ?? '';
        $headers = $mail_data['headers'] ?? [];

        // Detect HTML
        $content_type = 'text/plain';
        if ( is_array( $headers ) ) {
            foreach ( $headers as $header ) {
                if ( stripos( $header, 'content-type: text/html' ) !== false ) {
                    $content_type = 'text/html';
                }
            }
        } elseif ( is_string( $headers ) && stripos( $headers, 'text/html' ) !== false ) {
            $content_type = 'text/html';
        }

        $from_header = $from_name ? "{$from_name} <{$from_email}>" : $from_email;

        $message = "From: {$from_header}\r\n"
            . "To: {$to}\r\n"
            . "Subject: =?UTF-8?B?" . base64_encode( $subject ) . "?=\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: {$content_type}; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . chunk_split( base64_encode( $body ) );

        // URL-safe base64
        return rtrim( strtr( base64_encode( $message ), '+/', '-_' ), '=' );
    }

    public function get_valid_access_token(): string {
        $token      = $this->get( 'access_token' );
        $expires_at = (int) $this->get( 'token_expires_at', 0 );

        if ( time() > $expires_at - 60 ) {
            $token = $this->refresh_access_token();
        }

        return (string) $token;
    }

    private function refresh_access_token(): string {
        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id'     => $this->get( 'client_id' ),
                'client_secret' => $this->get( 'client_secret' ),
                'refresh_token' => $this->get( 'refresh_token' ),
                'grant_type'    => 'refresh_token',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $data['access_token'] ) ) {
            $settings                     = $this->settings;
            $settings['access_token']     = $data['access_token'];
            $settings['token_expires_at'] = time() + (int) ( $data['expires_in'] ?? 3600 );
            update_option( 'wmp_gmail', $settings );
            return $data['access_token'];
        }

        return '';
    }
}

<?php
namespace WPMailPro\Mailers;

defined( 'ABSPATH' ) || exit;

class Outlook extends AbstractMailer {

    public function get_name(): string {
        return 'Microsoft 365 / Outlook';
    }

    protected function get_option_key(): string {
        return 'wmp_outlook';
    }

    public function is_configured(): bool {
        return ! empty( $this->get( 'client_id' ) )
            && ! empty( $this->get( 'client_secret' ) )
            && ! empty( $this->get( 'access_token' ) );
    }

    /**
     * Like Gmail, we use the Microsoft Graph API instead of SMTP XOAUTH2,
     * since WordPress's PHPMailer does not support XOAUTH2 natively.
     */
    public function configure( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        // API-based send — nothing to configure on PHPMailer.
    }

    /**
     * Send via Microsoft Graph API sendMail endpoint.
     */
    public function send( array $mail_data ): bool {
        $access_token = $this->get_valid_access_token();
        if ( ! $access_token ) {
            return false;
        }

        $from_name  = (string) get_option( 'wmp_from_name', get_bloginfo( 'name' ) );
        $from_email = (string) get_option( 'wmp_from_email', get_option( 'admin_email' ) );

        $to = $mail_data['to'] ?? '';
        if ( is_string( $to ) ) {
            $to = array_map( 'trim', explode( ',', $to ) );
        }

        $recipients = array_map( fn( $email ) => [
            'emailAddress' => [ 'address' => trim( $email ) ],
        ], (array) $to );

        $headers      = $mail_data['headers'] ?? [];
        $content_type = 'Text';
        if ( is_array( $headers ) ) {
            foreach ( $headers as $h ) {
                if ( stripos( $h, 'text/html' ) !== false ) {
                    $content_type = 'HTML';
                }
            }
        } elseif ( is_string( $headers ) && stripos( $headers, 'text/html' ) !== false ) {
            $content_type = 'HTML';
        }

        $payload = [
            'message' => [
                'subject' => $mail_data['subject'] ?? '',
                'body'    => [
                    'contentType' => $content_type,
                    'content'     => $mail_data['message'] ?? '',
                ],
                'from' => [
                    'emailAddress' => [
                        'name'    => $from_name,
                        'address' => $from_email,
                    ],
                ],
                'toRecipients' => $recipients,
            ],
            'saveToSentItems' => false,
        ];

        $response = wp_remote_post(
            'https://graph.microsoft.com/v1.0/me/sendMail',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $payload ),
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        // Graph API returns 202 Accepted on success
        return wp_remote_retrieve_response_code( $response ) === 202;
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
        $tenant_id = $this->get( 'tenant_id', 'common' );

        $response = wp_remote_post(
            "https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/token",
            [
                'body' => [
                    'client_id'     => $this->get( 'client_id' ),
                    'client_secret' => $this->get( 'client_secret' ),
                    'refresh_token' => $this->get( 'refresh_token' ),
                    'grant_type'    => 'refresh_token',
                    'scope'         => 'https://graph.microsoft.com/Mail.Send offline_access',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $data['access_token'] ) ) {
            $settings                     = $this->settings;
            $settings['access_token']     = $data['access_token'];
            $settings['refresh_token']    = $data['refresh_token'] ?? $settings['refresh_token'];
            $settings['token_expires_at'] = time() + (int) ( $data['expires_in'] ?? 3600 );
            update_option( 'wmp_outlook', $settings );
            return $data['access_token'];
        }

        return '';
    }
}

<?php
namespace WPMailPro\Auth;

defined( 'ABSPATH' ) || exit;

class GmailOAuth {

    private const SCOPES = [
        'https://www.googleapis.com/auth/gmail.send',
        'https://www.googleapis.com/auth/userinfo.email',
        'openid',
        'email',
    ];

    public function get_auth_url(): string {
        $settings    = (array) get_option( 'wmp_gmail', [] );
        $client_id   = $settings['client_id'] ?? '';
        $redirect    = $this->get_redirect_uri();

        $params = [
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => implode( ' ', self::SCOPES ),
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => wp_create_nonce( 'wmp_gmail_oauth' ),
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
    }

    public function handle_callback(): void {
        if ( ! isset( $_GET['code'], $_GET['state'] ) || ! str_contains( $_SERVER['REQUEST_URI'] ?? '', 'wmp_gmail_callback' ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( $_GET['state'] ), 'wmp_gmail_oauth' ) ) {
            wp_die( 'Invalid state parameter.' );
        }

        $settings = (array) get_option( 'wmp_gmail', [] );
        $code     = sanitize_text_field( $_GET['code'] );

        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', [
            'body' => [
                'code'          => $code,
                'client_id'     => $settings['client_id'] ?? '',
                'client_secret' => $settings['client_secret'] ?? '',
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=wp-mail-pro&oauth_error=gmail' ) );
            exit;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $data['access_token'] ) ) {
            $settings['access_token']     = $data['access_token'];
            $settings['refresh_token']    = $data['refresh_token'] ?? '';
            $settings['token_expires_at'] = time() + (int) ( $data['expires_in'] ?? 3600 );
            update_option( 'wmp_gmail', $settings );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=wp-mail-pro&oauth_success=gmail' ) );
        exit;
    }

    private function get_redirect_uri(): string {
        return admin_url( 'admin.php?wmp_gmail_callback=1' );
    }
}

<?php
namespace WPMailPro\Auth;

defined( 'ABSPATH' ) || exit;

class OutlookOAuth {

    // Use Graph API scope (Mail.Send) instead of SMTP.Send
    private const SCOPE = 'https://graph.microsoft.com/Mail.Send offline_access openid email';

    public function get_auth_url(): string {
        $settings  = (array) get_option( 'wmp_outlook', [] );
        $client_id = $settings['client_id'] ?? '';
        $tenant_id = $settings['tenant_id'] ?? 'common';

        $params = [
            'client_id'     => $client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            'state'         => wp_create_nonce( 'wmp_outlook_oauth' ),
        ];

        return "https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/authorize?" . http_build_query( $params );
    }

    public function handle_callback(): void {
        if ( ! isset( $_GET['code'], $_GET['state'] ) || ! str_contains( $_SERVER['REQUEST_URI'] ?? '', 'wmp_outlook_callback' ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( $_GET['state'] ), 'wmp_outlook_oauth' ) ) {
            wp_die( 'Invalid state parameter.' );
        }

        $settings  = (array) get_option( 'wmp_outlook', [] );
        $tenant_id = $settings['tenant_id'] ?? 'common';
        $code      = sanitize_text_field( $_GET['code'] );

        $response = wp_remote_post(
            "https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/token",
            [
                'body' => [
                    'code'          => $code,
                    'client_id'     => $settings['client_id'] ?? '',
                    'client_secret' => $settings['client_secret'] ?? '',
                    'redirect_uri'  => $this->get_redirect_uri(),
                    'grant_type'    => 'authorization_code',
                    'scope'         => self::SCOPE,
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=wp-mail-pro&oauth_error=outlook' ) );
            exit;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $data['access_token'] ) ) {
            $settings['access_token']     = $data['access_token'];
            $settings['refresh_token']    = $data['refresh_token'] ?? '';
            $settings['token_expires_at'] = time() + (int) ( $data['expires_in'] ?? 3600 );
            update_option( 'wmp_outlook', $settings );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=wp-mail-pro&oauth_success=outlook' ) );
        exit;
    }

    private function get_redirect_uri(): string {
        return admin_url( 'admin.php?wmp_outlook_callback=1' );
    }
}

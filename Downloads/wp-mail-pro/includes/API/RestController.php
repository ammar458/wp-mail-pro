<?php
namespace WPMailPro\API;

defined( 'ABSPATH' ) || exit;

use WPMailPro\Log\LogQuery;

class RestController {

    public function register_routes(): void {
        register_rest_route( 'wp-mail-pro/v1', '/stats', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_stats' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ] );

        register_rest_route( 'wp-mail-pro/v1', '/logs', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_logs' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ] );

        register_rest_route( 'wp-mail-pro/v1', '/logs/(?P<id>\d+)/resend', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'resend_email' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ] );

        register_rest_route( 'wp-mail-pro/v1', '/logs/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_log' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ] );

        register_rest_route( 'wp-mail-pro/v1', '/test', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'send_test' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ] );
    }

    public function check_permission(): bool {
        return current_user_can( 'manage_options' );
    }

    public function get_stats(): \WP_REST_Response {
        $stats = ( new LogQuery() )->get_stats();
        return rest_ensure_response( $stats );
    }

    public function get_logs( \WP_REST_Request $request ): \WP_REST_Response {
        $query = new LogQuery();
        $args  = [
            'page'    => $request->get_param( 'page' ) ?? 1,
            'status'  => $request->get_param( 'status' ) ?? '',
            'search'  => $request->get_param( 's' ) ?? '',
        ];

        return rest_ensure_response( [
            'logs'  => $query->get_list( $args ),
            'total' => $query->count( $args ),
        ] );
    }

    public function resend_email( \WP_REST_Request $request ): \WP_REST_Response {
        $id      = (int) $request->get_param( 'id' );
        $success = ( new \WPMailPro\Log\Logger() )->resend( $id );

        return rest_ensure_response( [ 'success' => $success ] );
    }

    public function delete_log( \WP_REST_Request $request ): \WP_REST_Response {
        $id      = (int) $request->get_param( 'id' );
        $deleted = ( new LogQuery() )->delete( $id );

        return rest_ensure_response( [ 'deleted' => $deleted ] );
    }

    public function send_test( \WP_REST_Request $request ): \WP_REST_Response {
        $to      = sanitize_email( $request->get_param( 'to' ) ?? get_option( 'admin_email' ) );
        $subject = sanitize_text_field( $request->get_param( 'subject' ) ?? 'WP Mail Pro Test' );
        $body    = '<p>This is a test email from WP Mail Pro.</p>';

        add_filter( 'wp_mail_content_type', fn() => 'text/html' );
        $result = wp_mail( $to, $subject, $body );
        remove_filter( 'wp_mail_content_type', fn() => 'text/html' );

        return rest_ensure_response( [ 'success' => $result ] );
    }
}

<?php
namespace WPMailPro\Log;

defined( 'ABSPATH' ) || exit;

use WPMailPro\Helpers;
use WPMailPro\Alerts\AlertManager;

class Logger {

    private function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'wmp_email_logs';
    }

    public function log( array $mail_data ): int {
        global $wpdb;

        $status = $mail_data['status'] ?? 'sent';
        $error  = $mail_data['error']  ?? '';

        $inserted = $wpdb->insert(
            $this->table(),
            [
                'subject'     => $mail_data['subject'] ?? '',
                'recipients'  => wp_json_encode( $mail_data['to'] ?? [] ),
                'headers'     => wp_json_encode( $mail_data['headers'] ?? [] ),
                'body'        => $mail_data['message'] ?? '',
                'attachments' => wp_json_encode( $mail_data['attachments'] ?? [] ),
                'mailer'      => Helpers::get_current_mailer(),
                'status'      => $status,
                'error'       => is_array( $error ) ? implode( "\n", $error ) : $error,
                'sent_at'     => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        $log_id = $inserted ? (int) $wpdb->insert_id : 0;

        if ( 'failed' === $status ) {
            ( new AlertManager() )->dispatch_failure( $mail_data );
        }

        return $log_id;
    }

    public function log_failure( \WP_Error $error ): void {
        if ( ! Helpers::is_log_enabled() ) {
            return;
        }

        $mail_data         = \WPMailPro\Mail\Processor::get_current_mail();
        $mail_data['status'] = 'failed';
        $mail_data['error']  = $error->get_error_messages();

        $this->log( $mail_data );
    }

    public function cleanup_old_logs(): void {
        global $wpdb;

        $days = (int) get_option( 'wmp_log_retention', 30 );
        if ( $days <= 0 ) {
            return;
        }

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table()} WHERE sent_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }

    public function resend( int $log_id ): bool {
        $entry = ( new LogQuery() )->get( $log_id );
        if ( ! $entry ) {
            return false;
        }

        $to      = json_decode( $entry->recipients, true );
        $headers = json_decode( $entry->headers, true );

        return wp_mail( $to, $entry->subject, $entry->body, $headers );
    }

    public function mark_opened( int $log_id ): void {
        global $wpdb;

        $wpdb->update(
            $this->table(),
            [
                'opened'    => 1,
                'opened_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $log_id ],
            [ '%d', '%s' ],
            [ '%d' ]
        );
    }
}

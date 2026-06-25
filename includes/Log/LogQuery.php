<?php
namespace WPMailPro\Log;

defined( 'ABSPATH' ) || exit;

class LogQuery {

    private function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'wmp_email_logs';
    }

    public function get( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $id )
        );
    }

    public function get_list( array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'per_page' => 20,
            'page'     => 1,
            'status'   => '',
            'search'   => '',
            'orderby'  => 'sent_at',
            'order'    => 'DESC',
        ];

        $args    = wp_parse_args( $args, $defaults );
        $offset  = ( $args['page'] - 1 ) * $args['per_page'];
        $where   = '1=1';
        $params  = [];

        if ( $args['status'] ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }

        if ( $args['search'] ) {
            $where   .= ' AND (subject LIKE %s OR recipients LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $orderby = in_array( $args['orderby'], [ 'sent_at', 'subject', 'status', 'id' ], true )
            ? $args['orderby'] : 'sent_at';
        $order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM {$this->table()} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $params[] = $args['per_page'];
        $params[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
    }

    public function count( array $args = [] ): int {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where   .= ' AND (subject LIKE %s OR recipients LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ( $params ) {
            return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE {$where}", ...$params ) );
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table()} WHERE {$where}" );
    }

    public function get_stats(): array {
        global $wpdb;

        $table = $this->table();

        return [
            'total'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
            'sent'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'sent'" ),
            'failed'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'failed'" ),
            'opened'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE opened = 1" ),
            'today'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE DATE(sent_at) = CURDATE()" ),
            'week'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ),
            'month'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" ),
        ];
    }

    public function get_daily_counts( int $days = 30 ): array {
        global $wpdb;

        $table = $this->table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(sent_at) as date, status, COUNT(*) as count
                 FROM {$table}
                 WHERE sent_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                 GROUP BY DATE(sent_at), status
                 ORDER BY date ASC",
                $days
            )
        );
    }

    public function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $this->table(), [ 'id' => $id ], [ '%d' ] );
    }

    public function delete_all(): bool {
        global $wpdb;
        return (bool) $wpdb->query( "TRUNCATE TABLE {$this->table()}" );
    }
}

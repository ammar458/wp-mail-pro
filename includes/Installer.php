<?php
namespace WPMailPro;

defined( 'ABSPATH' ) || exit;

class Installer {

    public static function activate(): void {
        self::create_tables();
        self::set_default_options();
        flush_rewrite_rules();
    }

    private static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $logs_table = $wpdb->prefix . 'wmp_email_logs';
        $sql_logs   = "CREATE TABLE IF NOT EXISTS {$logs_table} (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            subject     VARCHAR(500)    NOT NULL DEFAULT '',
            recipients  TEXT            NOT NULL,
            headers     LONGTEXT        NOT NULL,
            body        LONGTEXT        NOT NULL,
            attachments TEXT            NOT NULL,
            mailer      VARCHAR(100)    NOT NULL DEFAULT '',
            status      VARCHAR(20)     NOT NULL DEFAULT 'sent',
            error       TEXT            NOT NULL DEFAULT '',
            opened      TINYINT(1)      NOT NULL DEFAULT 0,
            opened_at   DATETIME        DEFAULT NULL,
            sent_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status  (status),
            KEY sent_at (sent_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_logs );

        update_option( 'wmp_db_version', WP_MAIL_PRO_VERSION );
    }

    private static function set_default_options(): void {
        $defaults = [
            'wmp_mailer'        => 'smtp',
            'wmp_from_name'     => get_bloginfo( 'name' ),
            'wmp_from_email'    => get_option( 'admin_email' ),
            'wmp_log_emails'    => true,
            'wmp_log_retention' => 30,
            'wmp_smtp'          => [
                'host'       => '',
                'port'       => 587,
                'encryption' => 'tls',
                'auth'       => true,
                'user'       => '',
                'pass'       => '',
            ],
        ];

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                update_option( $key, $value );
            }
        }
    }
}

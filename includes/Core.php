<?php
namespace WPMailPro;

defined( 'ABSPATH' ) || exit;

class Core {

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        ( new Updater() )->init();
        $this->register_hooks();
    }

    private function register_hooks(): void {
        $mailer_instance = new Mail\Mailer();

        // For SMTP-based mailers: configure PHPMailer
        add_action( 'phpmailer_init', [ $mailer_instance, 'configure' ] );

        // For API-based mailers (Gmail, Outlook): intercept before PHPMailer runs
        add_filter( 'pre_wp_mail', function( $return, $atts ) use ( $mailer_instance ) {
            // If already handled (non-null), don't interfere
            if ( null !== $return ) {
                return $return;
            }

            $handled = $mailer_instance->maybe_send_via_api( $atts );

            // Return true (sent) or null (let PHPMailer handle it)
            return $handled ? true : null;
        }, 10, 2 );

        // Log failures from PHPMailer-based mailers
        add_action( 'wp_mail_failed', [ new Log\Logger(), 'log_failure' ] );

        // Log successes from PHPMailer-based mailers
        add_action( 'wp_mail_succeeded', function( $mail_data ) {
            if ( ! Helpers::is_log_enabled() ) {
                return;
            }
            // Don't double-log API-based mailers (they log themselves in maybe_send_via_api)
            $mailer_key   = Helpers::get_current_mailer();
            $api_mailers  = [ 'gmail', 'outlook' ];
            if ( in_array( $mailer_key, $api_mailers, true ) ) {
                return;
            }
            $mail_data['status'] = 'sent';
            ( new Log\Logger() )->log( $mail_data );
            do_action( 'wmp_email_sent', $mail_data );
        } );

        // Admin
        if ( is_admin() ) {
            $menu = new Admin\AdminMenu();
            add_action( 'admin_menu',            [ $menu, 'register' ] );
            add_action( 'admin_enqueue_scripts', [ $menu, 'enqueue_assets' ] );
            add_action( 'admin_notices',         [ new Admin\Notices(), 'display' ] );
        }

        // Settings save
        add_action( 'admin_post_wmp_save_settings',    [ new Settings\Settings(), 'save' ] );
        add_action( 'admin_post_wmp_send_test',        [ new Admin\Pages\ToolsPage(), 'handle_test_email' ] );
        add_action( 'admin_post_wmp_send_weekly_report', [ new Admin\Pages\ReportsPage(), 'handle_send_report' ] );

        // REST API
        add_action( 'rest_api_init', [ new API\RestController(), 'register_routes' ] );

        // Cron: weekly report
        add_action( 'wmp_weekly_report', [ new Reports\WeeklySummary(), 'send' ] );
        if ( ! wp_next_scheduled( 'wmp_weekly_report' ) ) {
            wp_schedule_event( strtotime( 'next monday 8:00am' ), 'weekly', 'wmp_weekly_report' );
        }

        // Cron: log cleanup
        add_action( 'wmp_cleanup_logs', [ new Log\Logger(), 'cleanup_old_logs' ] );
        if ( ! wp_next_scheduled( 'wmp_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'wmp_cleanup_logs' );
        }

        // OAuth callbacks
        add_action( 'admin_init', [ new Auth\GmailOAuth(), 'handle_callback' ] );
        add_action( 'admin_init', [ new Auth\OutlookOAuth(), 'handle_callback' ] );
    }
}

<?php
namespace WPMailPro\Admin;

defined( 'ABSPATH' ) || exit;

class AdminMenu {

    private Pages\SettingsPage $settings_page;

    public function __construct() {
        $this->settings_page = new Pages\SettingsPage();
    }

    public function register(): void {
        add_menu_page(
            'WP Mail Pro',
            'WP Mail Pro',
            'manage_options',
            'wp-mail-pro',
            [ $this->settings_page, 'render' ],
            'dashicons-email-alt',
            25
        );

        // Same slug as parent = WordPress replaces the auto-generated "WP Mail Pro" submenu entry
        add_submenu_page(
            'wp-mail-pro',
            'Settings',
            'Settings',
            'manage_options',
            'wp-mail-pro',
            [ $this->settings_page, 'render' ]
        );

        add_submenu_page(
            'wp-mail-pro',
            'Email Log',
            'Email Log',
            'manage_options',
            'wp-mail-pro-log',
            [ new Pages\EmailLogPage(), 'render' ]
        );

        add_submenu_page(
            'wp-mail-pro',
            'Reports',
            'Reports',
            'manage_options',
            'wp-mail-pro-reports',
            [ new Pages\ReportsPage(), 'render' ]
        );

        add_submenu_page(
            'wp-mail-pro',
            'Tools',
            'Tools',
            'manage_options',
            'wp-mail-pro-tools',
            [ new Pages\ToolsPage(), 'render' ]
        );
    }

    public function enqueue_assets( string $hook ): void {
        $wmp_pages = [
            'toplevel_page_wp-mail-pro',
            'wp-mail-pro_page_wp-mail-pro-log',
            'wp-mail-pro_page_wp-mail-pro-reports',
            'wp-mail-pro_page_wp-mail-pro-tools',
        ];

        if ( ! in_array( $hook, $wmp_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'wmp-admin',
            WP_MAIL_PRO_URL . 'assets/css/admin.css',
            [],
            WP_MAIL_PRO_VERSION
        );

        wp_enqueue_script(
            'wmp-admin',
            WP_MAIL_PRO_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            WP_MAIL_PRO_VERSION,
            true
        );

        wp_localize_script( 'wmp-admin', 'wmpData', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wmp_ajax' ),
            'restUrl' => rest_url( 'wp-mail-pro/v1/' ),
        ] );
    }
}

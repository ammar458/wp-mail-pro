<?php
/**
 * Plugin Name: WP Mail Pro
 * Plugin URI:  https://internal.team/wp-mail-pro
 * Description: A full-featured SMTP and API mailer plugin for WordPress — internal use.
 * Version:     1.0.7
 * Author:      Ringo Media
 * License:     Private
 * Text Domain: wp-mail-pro
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_MAIL_PRO_VERSION', '1.0.7' );
define( 'WP_MAIL_PRO_FILE',    __FILE__ );
define( 'WP_MAIL_PRO_DIR',     plugin_dir_path( __FILE__ ) );
define( 'WP_MAIL_PRO_URL',     plugin_dir_url( __FILE__ ) );

// Autoloader
spl_autoload_register( function ( $class ) {
    $prefix   = 'WPMailPro\\';
    $base_dir = WP_MAIL_PRO_DIR . 'includes/';

    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }

    $relative = substr( $class, strlen( $prefix ) );
    $file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

register_activation_hook( __FILE__, [ 'WPMailPro\\Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'WPMailPro\\Deactivator', 'deactivate' ] );

add_action( 'plugins_loaded', function () {
    WPMailPro\Core::get_instance()->init();
} );

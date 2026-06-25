<?php
namespace WPMailPro\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use WPMailPro\Helpers;

class ToolsPage {

    public function render(): void {
        $result = get_transient( 'wmp_test_email_result' );
        if ( $result ) {
            delete_transient( 'wmp_test_email_result' );
            $type = $result['success'] ? 'success' : 'error';
            echo "<div class='notice notice-{$type} is-dismissible'><p>" . esc_html( $result['message'] ) . '</p></div>';
        }

        $card = 'background:#fff;border:1px solid #e2e4e7;border-radius:8px;overflow:hidden;margin-bottom:16px';
        $ch   = 'display:flex;align-items:center;gap:8px;padding:12px 18px;border-bottom:1px solid #f0f0f1;background:#fafafa;font-size:13px;font-weight:600;color:#1d2327';
        $cb   = 'padding:20px';
        $inp  = 'border:1px solid #dcdcde;border-radius:4px;padding:7px 10px;font-size:13px;width:280px';
        $btn  = 'background:#2271b1;color:#fff;border:none;border-radius:4px;padding:7px 16px;font-size:13px;font-weight:500;cursor:pointer';

        $badge = function( bool $on ) {
            return $on
                ? '<span style="display:inline-flex;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;background:#edfaee;color:#1a5c1e">Enabled</span>'
                : '<span style="display:inline-flex;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;background:#f0f0f1;color:#8c8f94">Disabled</span>';
        };
        ?>
        <div class="wrap" style="max-width:960px">
            <h1 style="display:flex;align-items:center;gap:10px;font-size:18px;font-weight:600;color:#1d2327;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #e2e4e7">
                <span class="dashicons dashicons-admin-tools" style="font-size:20px;width:20px;height:20px;color:#2271b1"></span> Tools
            </h1>

            <!-- Test email -->
            <div style="<?php echo esc_attr( $card ); ?>">
                <div style="<?php echo esc_attr( $ch ); ?>">
                    <span class="dashicons dashicons-email-alt" style="font-size:16px;width:16px;height:16px;color:#646970"></span> Send test email
                </div>
                <div style="<?php echo esc_attr( $cb ); ?>">
                    <p style="font-size:13px;color:#3c434a;margin:0 0 16px">Send a test email to verify your mailer is configured correctly.</p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="wmp_send_test">
                        <?php Helpers::nonce_field( 'wmp_send_test' ); ?>
                        <table style="border-collapse:collapse">
                            <tr>
                                <td style="padding:5px 12px 5px 0;font-size:13px;font-weight:500;color:#3c434a;width:90px;white-space:nowrap">Send to</td>
                                <td style="padding:5px 0"><input type="email" name="wmp_test_email" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" required style="<?php echo esc_attr( $inp ); ?>"></td>
                            </tr>
                            <tr>
                                <td style="padding:5px 12px 5px 0;font-size:13px;font-weight:500;color:#3c434a">Subject</td>
                                <td style="padding:5px 0"><input type="text" name="wmp_test_subject" value="WP Mail Pro test email" style="<?php echo esc_attr( $inp ); ?>"></td>
                            </tr>
                        </table>
                        <button type="submit" style="<?php echo esc_attr( $btn ); ?>;margin-top:14px">Send test email</button>
                    </form>
                </div>
            </div>

            <!-- Export -->
            <div style="<?php echo esc_attr( $card ); ?>">
                <div style="<?php echo esc_attr( $ch ); ?>">
                    <span class="dashicons dashicons-download" style="font-size:16px;width:16px;height:16px;color:#646970"></span> Export settings
                </div>
                <div style="<?php echo esc_attr( $cb ); ?>">
                    <p style="font-size:13px;color:#3c434a;margin:0 0 14px">Download your WP Mail Pro settings as a JSON file to back up or move to another site.</p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="wmp_export_settings">
                        <?php wp_nonce_field( 'wmp_export_settings' ); ?>
                        <button type="submit" style="background:#fff;color:#3c434a;border:1px solid #dcdcde;border-radius:4px;padding:6px 14px;font-size:13px;cursor:pointer">Export settings</button>
                    </form>
                </div>
            </div>

            <!-- Import -->
            <div style="<?php echo esc_attr( $card ); ?>">
                <div style="<?php echo esc_attr( $ch ); ?>">
                    <span class="dashicons dashicons-upload" style="font-size:16px;width:16px;height:16px;color:#646970"></span> Import settings
                </div>
                <div style="<?php echo esc_attr( $cb ); ?>">
                    <p style="font-size:13px;color:#3c434a;margin:0 0 14px">Restore settings from a previously exported JSON file. This will overwrite your current settings.</p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="wmp_import_settings">
                        <?php wp_nonce_field( 'wmp_import_settings' ); ?>
                        <input type="file" name="wmp_settings_file" accept=".json" required style="font-size:13px;margin-bottom:12px;display:block">
                        <button type="submit" style="background:#fff;color:#3c434a;border:1px solid #dcdcde;border-radius:4px;padding:6px 14px;font-size:13px;cursor:pointer">Import settings</button>
                    </form>
                </div>
            </div>

            <!-- System info -->
            <div style="<?php echo esc_attr( $card ); ?>">
                <div style="<?php echo esc_attr( $ch ); ?>">
                    <span class="dashicons dashicons-info-outline" style="font-size:16px;width:16px;height:16px;color:#646970"></span> System info
                </div>
                <div style="padding:0">
                    <?php
                    $rows = [
                        'Plugin version'    => WP_MAIL_PRO_VERSION,
                        'WordPress version' => get_bloginfo( 'version' ),
                        'PHP version'       => PHP_VERSION,
                        'Active mailer'     => Helpers::get_mailer_list()[ Helpers::get_current_mailer() ] ?? Helpers::get_current_mailer(),
                        'From email'        => get_option( 'wmp_from_email' ),
                    ];
                    $badge_rows = [
                        'Email logging'   => (bool) get_option( 'wmp_log_emails', 1 ),
                        'Backup mailer'   => (bool) get_option( 'wmp_backup_mailer_enabled' ),
                    ];
                    $i = 0;
                    $tr = fn( $bg ) => 'background:' . $bg;
                    $th_s = 'padding:9px 16px;font-size:12.5px;font-weight:500;color:#3c434a;width:220px;border-bottom:1px solid #f0f0f1';
                    $td_s = 'padding:9px 16px;font-size:12.5px;color:#1d2327;border-bottom:1px solid #f0f0f1';
                    echo '<table style="width:100%;border-collapse:collapse">';
                    foreach ( $rows as $label => $val ) {
                        $bg = $i % 2 === 0 ? '#fff' : '#fafafa';
                        echo '<tr style="background:' . esc_attr( $bg ) . '"><th style="' . esc_attr( $th_s ) . '">' . esc_html( $label ) . '</th><td style="' . esc_attr( $td_s ) . '">' . esc_html( $val ) . '</td></tr>';
                        $i++;
                    }
                    foreach ( $badge_rows as $label => $val ) {
                        $bg = $i % 2 === 0 ? '#fff' : '#fafafa';
                        echo '<tr style="background:' . esc_attr( $bg ) . '"><th style="' . esc_attr( $th_s ) . '">' . esc_html( $label ) . '</th><td style="' . esc_attr( $td_s ) . '">' . $badge( $val ) . '</td></tr>';
                        $i++;
                    }
                    $extra = [
                        'Log retention'      => get_option( 'wmp_log_retention', 30 ) . ' days',
                        'Next weekly report' => wp_next_scheduled( 'wmp_weekly_report' ) ? gmdate( 'Y-m-d H:i', wp_next_scheduled( 'wmp_weekly_report' ) ) : 'Not scheduled',
                        'Next log cleanup'   => wp_next_scheduled( 'wmp_cleanup_logs' ) ? gmdate( 'Y-m-d H:i', wp_next_scheduled( 'wmp_cleanup_logs' ) ) : 'Not scheduled',
                    ];
                    foreach ( $extra as $label => $val ) {
                        $bg = $i % 2 === 0 ? '#fff' : '#fafafa';
                        $last = $i === ( count( $rows ) + count( $badge_rows ) + count( $extra ) - 1 );
                        $th_last = $last ? str_replace( 'border-bottom:1px solid #f0f0f1', 'border-bottom:none', $th_s ) : $th_s;
                        $td_last = $last ? str_replace( 'border-bottom:1px solid #f0f0f1', 'border-bottom:none', $td_s ) : $td_s;
                        echo '<tr style="background:' . esc_attr( $bg ) . '"><th style="' . esc_attr( $th_last ) . '">' . esc_html( $label ) . '</th><td style="' . esc_attr( $td_last ) . '">' . esc_html( $val ) . '</td></tr>';
                        $i++;
                    }
                    echo '</table>';
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_test_email(): void {
        if ( ! current_user_can( 'manage_options' ) || ! Helpers::verify_nonce( 'wmp_send_test' ) ) {
            wp_die( 'Unauthorized' );
        }
        $to      = sanitize_email( $_POST['wmp_test_email'] ?? get_option( 'admin_email' ) );
        $subject = sanitize_text_field( $_POST['wmp_test_subject'] ?? 'WP Mail Pro test email' );
        $body    = '<h2 style="font-family:sans-serif;color:#1d2327">WP Mail Pro — test email</h2>'
            . '<p style="font-family:sans-serif;color:#3c434a">This is a test email from <strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong>.</p>'
            . '<p style="font-family:sans-serif;color:#3c434a">If you received this, your mailer is configured correctly.</p>'
            . '<p style="font-family:sans-serif;font-size:12px;color:#646970">Sent at ' . current_time( 'mysql' ) . ' via ' . esc_html( Helpers::get_current_mailer() ) . '</p>';

        add_filter( 'wp_mail_content_type', fn() => 'text/html' );
        $result = wp_mail( $to, $subject, $body );
        remove_filter( 'wp_mail_content_type', fn() => 'text/html' );

        set_transient( 'wmp_test_email_result', [
            'success' => $result,
            'message' => $result ? "Test email sent successfully to {$to}." : "Failed to send. Check your mailer settings and the email log.",
        ], 60 );

        wp_safe_redirect( admin_url( 'admin.php?page=wp-mail-pro-tools' ) );
        exit;
    }
}

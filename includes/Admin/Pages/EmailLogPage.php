<?php
namespace WPMailPro\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use WPMailPro\Log\LogQuery;
use WPMailPro\Log\Logger;
use WPMailPro\Helpers;

class EmailLogPage {

    public function render(): void {
        // Handle actions
        if ( isset( $_GET['wmp_action'] ) && check_admin_referer( 'wmp_log_action' ) ) {
            $action = sanitize_key( $_GET['wmp_action'] );
            $id     = (int) ( $_GET['id'] ?? 0 );
            if ( 'resend' === $action && $id ) {
                ( new Logger() )->resend( $id );
                echo '<div class="notice notice-success is-dismissible"><p>Email resent successfully.</p></div>';
            }
            if ( 'delete' === $action && $id ) {
                ( new LogQuery() )->delete( $id );
                echo '<div class="notice notice-success is-dismissible"><p>Log entry deleted.</p></div>';
            }
            if ( 'delete_all' === $action ) {
                ( new LogQuery() )->delete_all();
                echo '<div class="notice notice-success is-dismissible"><p>All logs cleared.</p></div>';
            }
        }

        $query  = new LogQuery();
        $page   = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $status = sanitize_key( $_GET['status'] ?? '' );
        $search = sanitize_text_field( $_GET['s'] ?? '' );
        $args   = [ 'page' => $page, 'status' => $status, 'search' => $search, 'per_page' => 25 ];
        $logs   = $query->get_list( $args );
        $total  = $query->count( $args );
        $stats  = $query->get_stats();
        $pages  = (int) ceil( $total / 25 );
        $url    = admin_url( 'admin.php?page=wp-mail-pro-log' );

        $s = [
            'wrap'        => 'max-width:960px',
            'page_title'  => 'display:flex;align-items:center;gap:10px;font-size:18px;font-weight:600;color:#1d2327;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #e2e4e7',
            'stats_row'   => 'display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:20px',
            'stat'        => 'background:#fff;border:1px solid #e2e4e7;border-radius:8px;padding:14px 10px;text-align:center',
            'stat_num'    => 'display:block;font-size:24px;font-weight:700;line-height:1;margin-bottom:4px;color:#1d2327',
            'stat_label'  => 'font-size:10px;color:#8c8f94;text-transform:uppercase;letter-spacing:.7px;font-weight:600',
            'card'        => 'background:#fff;border:1px solid #e2e4e7;border-radius:8px;overflow:hidden;margin-bottom:16px',
            'toolbar'     => 'display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:13px 18px;border-bottom:1px solid #f0f0f1;background:#fafafa',
            'search'      => 'border:1px solid #dcdcde;border-radius:4px;padding:6px 10px;font-size:13px;width:220px;outline:none',
            'select'      => 'border:1px solid #dcdcde;border-radius:4px;padding:6px 8px;font-size:13px;color:#1d2327;min-width:120px',
            'filter_btn'  => 'background:#2271b1;color:#fff;border:none;border-radius:4px;padding:6px 14px;font-size:13px;font-weight:500;cursor:pointer',
            'clear_link'  => 'font-size:12px;color:#2271b1;text-decoration:none',
            'danger_link' => 'display:inline-block;background:#fff;color:#d63638;border:1px solid #f5aaab;border-radius:3px;padding:4px 10px;font-size:12px;font-weight:500;text-decoration:none;cursor:pointer',
            'table_wrap'  => 'overflow-x:auto',
            'th'          => 'font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#646970;padding:10px 14px;text-align:left;border-bottom:1px solid #e2e4e7;white-space:nowrap;background:#f9f9f9',
            'td'          => 'padding:11px 14px;border-bottom:1px solid #f0f0f1;vertical-align:middle',
            'action_btn'  => 'display:inline-block;background:#fff;color:#3c434a;border:1px solid #dcdcde;border-radius:3px;padding:3px 9px;font-size:11.5px;font-weight:500;text-decoration:none;line-height:1.6',
            'del_btn'     => 'display:inline-block;background:#fff;color:#d63638;border:1px solid #f5aaab;border-radius:3px;padding:3px 9px;font-size:11.5px;font-weight:500;text-decoration:none;line-height:1.6;margin-left:4px',
        ];

        $badge = function( string $text, string $type ) {
            $styles = [
                'success' => 'background:#edfaee;color:#1a5c1e',
                'error'   => 'background:#fce8e8;color:#8a1f1f',
                'muted'   => 'background:#f0f0f1;color:#8c8f94',
                'default' => 'background:#f0f0f1;color:#3c434a',
                'info'    => 'background:#e8f0fa;color:#1a3a6a',
            ];
            $css = $styles[ $type ] ?? $styles['default'];
            return '<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;line-height:1.7;' . $css . '">' . esc_html( $text ) . '</span>';
        };
        ?>

        <div class="wrap" style="<?php echo esc_attr( $s['wrap'] ); ?>">
            <h1 style="<?php echo esc_attr( $s['page_title'] ); ?>">
                <span class="dashicons dashicons-list-view" style="font-size:20px;width:20px;height:20px;color:#2271b1"></span>
                Email log
            </h1>

            <!-- Stats -->
            <div style="<?php echo esc_attr( $s['stats_row'] ); ?>">
                <div style="<?php echo esc_attr( $s['stat'] ); ?>">
                    <strong style="<?php echo esc_attr( $s['stat_num'] ); ?>;color:#1d2327"><?php echo esc_html( $stats['total'] ); ?></strong>
                    <span style="<?php echo esc_attr( $s['stat_label'] ); ?>">Total</span>
                </div>
                <div style="<?php echo esc_attr( $s['stat'] ); ?>">
                    <strong style="<?php echo esc_attr( $s['stat_num'] ); ?>;color:#00a32a"><?php echo esc_html( $stats['sent'] ); ?></strong>
                    <span style="<?php echo esc_attr( $s['stat_label'] ); ?>">Sent</span>
                </div>
                <div style="<?php echo esc_attr( $s['stat'] ); ?>">
                    <strong style="<?php echo esc_attr( $s['stat_num'] ); ?>;color:#d63638"><?php echo esc_html( $stats['failed'] ); ?></strong>
                    <span style="<?php echo esc_attr( $s['stat_label'] ); ?>">Failed</span>
                </div>
                <div style="<?php echo esc_attr( $s['stat'] ); ?>">
                    <strong style="<?php echo esc_attr( $s['stat_num'] ); ?>;color:#2271b1"><?php echo esc_html( $stats['opened'] ); ?></strong>
                    <span style="<?php echo esc_attr( $s['stat_label'] ); ?>">Opened</span>
                </div>
                <div style="<?php echo esc_attr( $s['stat'] ); ?>">
                    <strong style="<?php echo esc_attr( $s['stat_num'] ); ?>"><?php echo esc_html( $stats['today'] ); ?></strong>
                    <span style="<?php echo esc_attr( $s['stat_label'] ); ?>">Today</span>
                </div>
                <div style="<?php echo esc_attr( $s['stat'] ); ?>">
                    <strong style="<?php echo esc_attr( $s['stat_num'] ); ?>"><?php echo esc_html( $stats['week'] ); ?></strong>
                    <span style="<?php echo esc_attr( $s['stat_label'] ); ?>">This week</span>
                </div>
            </div>

            <!-- Table card -->
            <div style="<?php echo esc_attr( $s['card'] ); ?>">

                <!-- Toolbar -->
                <div style="<?php echo esc_attr( $s['toolbar'] ); ?>">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <form method="get" style="display:contents">
                            <input type="hidden" name="page" value="wp-mail-pro-log">
                            <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>"
                                   style="<?php echo esc_attr( $s['search'] ); ?>"
                                   placeholder="Search subject or recipient&hellip;">
                            <select name="status" style="<?php echo esc_attr( $s['select'] ); ?>">
                                <option value="">All statuses</option>
                                <option value="sent"   <?php selected( $status, 'sent' ); ?>>Sent</option>
                                <option value="failed" <?php selected( $status, 'failed' ); ?>>Failed</option>
                            </select>
                            <button type="submit" style="<?php echo esc_attr( $s['filter_btn'] ); ?>">Filter</button>
                            <?php if ( $search || $status ) : ?>
                                <a href="<?php echo esc_url( $url ); ?>" style="<?php echo esc_attr( $s['clear_link'] ); ?>">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <a href="<?php echo esc_url( wp_nonce_url( $url . '&wmp_action=delete_all', 'wmp_log_action' ) ); ?>"
                       style="<?php echo esc_attr( $s['danger_link'] ); ?>"
                       onclick="return confirm('Delete all log entries? This cannot be undone.')">
                       Clear all logs
                    </a>
                </div>

                <!-- Table -->
                <div style="<?php echo esc_attr( $s['table_wrap'] ); ?>">
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead>
                            <tr>
                                <th style="<?php echo esc_attr( $s['th'] ); ?>;width:44px">#</th>
                                <th style="<?php echo esc_attr( $s['th'] ); ?>">Subject</th>
                                <th style="<?php echo esc_attr( $s['th'] ); ?>">Recipient</th>
                                <th style="<?php echo esc_attr( $s['th'] ); ?>;width:70px">Mailer</th>
                                <th style="<?php echo esc_attr( $s['th'] ); ?>;width:70px">Status</th>
                                <th style="<?php echo esc_attr( $s['th'] ); ?>;width:70px">Opened</th>
                                <th style="<?php echo esc_attr( $s['th'] ); ?>;width:130px">Sent</th>
                                <th style="<?php echo esc_attr( $s['th'] ); ?>;width:100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( $logs ) : foreach ( $logs as $i => $log ) :
                                $recipients = json_decode( $log->recipients, true );
                                $to_str     = is_array( $recipients ) ? implode( ', ', $recipients ) : $log->recipients;
                                $row_bg     = $i % 2 === 0 ? '#fff' : '#fafbfc';
                            ?>
                                <tr style="background:<?php echo esc_attr( $row_bg ); ?>" onmouseover="this.style.background='#f0f6ff'" onmouseout="this.style.background='<?php echo esc_attr( $row_bg ); ?>'">
                                    <td style="<?php echo esc_attr( $s['td'] ); ?>;color:#8c8f94;font-size:12px"><?php echo esc_html( $log->id ); ?></td>
                                    <td style="<?php echo esc_attr( $s['td'] ); ?>">
                                        <div style="font-weight:500;color:#1d2327;line-height:1.4"><?php echo esc_html( $log->subject ); ?></div>
                                        <?php if ( $log->error ) : ?>
                                            <div style="font-size:11.5px;color:#d63638;margin-top:2px"><?php echo esc_html( $log->error ); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="<?php echo esc_attr( $s['td'] ); ?>;font-size:12px;color:#646970"><?php echo esc_html( $to_str ); ?></td>
                                    <td style="<?php echo esc_attr( $s['td'] ); ?>"><?php echo $badge( strtoupper( $log->mailer ), 'info' ); ?></td>
                                    <td style="<?php echo esc_attr( $s['td'] ); ?>"><?php echo $badge( $log->status, 'sent' === $log->status ? 'success' : 'error' ); ?></td>
                                    <td style="<?php echo esc_attr( $s['td'] ); ?>"><?php echo $badge( $log->opened ? 'Yes' : 'No', $log->opened ? 'success' : 'muted' ); ?></td>
                                    <td style="<?php echo esc_attr( $s['td'] ); ?>;font-size:12px;color:#8c8f94"><?php echo esc_html( Helpers::human_time_diff_since( $log->sent_at ) ); ?></td>
                                    <td style="<?php echo esc_attr( $s['td'] ); ?>;white-space:nowrap">
                                        <a href="<?php echo esc_url( wp_nonce_url( $url . '&wmp_action=resend&id=' . $log->id, 'wmp_log_action' ) ); ?>"
                                           style="<?php echo esc_attr( $s['action_btn'] ); ?>">Resend</a>
                                        <a href="<?php echo esc_url( wp_nonce_url( $url . '&wmp_action=delete&id=' . $log->id, 'wmp_log_action' ) ); ?>"
                                           style="<?php echo esc_attr( $s['del_btn'] ); ?>"
                                           onclick="return confirm('Delete this log entry?')">Del</a>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr>
                                    <td colspan="8" style="padding:48px 20px;text-align:center">
                                        <span class="dashicons dashicons-email-alt" style="font-size:32px;width:32px;height:32px;opacity:.3;display:block;margin:0 auto 10px;color:#646970"></span>
                                        <p style="font-size:13.5px;color:#646970;margin:0">No emails logged yet. Send a test email from the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-mail-pro-tools' ) ); ?>" style="color:#2271b1">Tools page</a>.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ( $pages > 1 ) : ?>
                    <div style="padding:12px 18px;border-top:1px solid #f0f0f1">
                        <?php echo paginate_links( [ 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $page, 'total' => $pages ] ); ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
        <?php
    }
}

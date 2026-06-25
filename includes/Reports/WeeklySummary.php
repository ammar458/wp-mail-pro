<?php
namespace WPMailPro\Reports;

defined( 'ABSPATH' ) || exit;

use WPMailPro\Log\LogQuery;

class WeeklySummary {

    public function send(): void {
        $to    = (string) get_option( 'admin_email' );
        $site  = get_bloginfo( 'name' );
        $query = new LogQuery();
        $stats = $query->get_stats();

        $week_sent   = (int) ( new \WPMailPro\Log\LogQuery() )->count( [ 'status' => 'sent' ] );
        $week_failed = (int) ( new \WPMailPro\Log\LogQuery() )->count( [ 'status' => 'failed' ] );

        $success_rate = $stats['total'] > 0
            ? round( ( $stats['sent'] / $stats['total'] ) * 100, 1 )
            : 0;

        $subject = "[{$site}] Weekly Email Report";

        $body = "
        <html><body style='font-family:Arial,sans-serif;max-width:600px;margin:auto'>
        <h2 style='color:#23282d'>Weekly Email Report for {$site}</h2>
        <p style='color:#555'>Here is a summary of email activity over the past 7 days.</p>

        <table style='width:100%;border-collapse:collapse;margin:20px 0'>
            <tr style='background:#f1f1f1'>
                <th style='padding:10px;text-align:left;border:1px solid #ddd'>Metric</th>
                <th style='padding:10px;text-align:right;border:1px solid #ddd'>Value</th>
            </tr>
            <tr>
                <td style='padding:10px;border:1px solid #ddd'>Emails Sent (7 days)</td>
                <td style='padding:10px;text-align:right;border:1px solid #ddd'>{$stats['week']}</td>
            </tr>
            <tr style='background:#f9f9f9'>
                <td style='padding:10px;border:1px solid #ddd'>Emails Sent (30 days)</td>
                <td style='padding:10px;text-align:right;border:1px solid #ddd'>{$stats['month']}</td>
            </tr>
            <tr>
                <td style='padding:10px;border:1px solid #ddd'>Total Failed</td>
                <td style='padding:10px;text-align:right;border:1px solid #ddd;color:#dc3232'>{$stats['failed']}</td>
            </tr>
            <tr style='background:#f9f9f9'>
                <td style='padding:10px;border:1px solid #ddd'>Overall Success Rate</td>
                <td style='padding:10px;text-align:right;border:1px solid #ddd;color:#46b450'>{$success_rate}%</td>
            </tr>
        </table>

        <p>
            <a href='" . admin_url( 'admin.php?page=wp-mail-pro-log' ) . "' style='background:#0073aa;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px'>View Email Log</a>
            &nbsp;
            <a href='" . admin_url( 'admin.php?page=wp-mail-pro-reports' ) . "' style='background:#555;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px'>View Reports</a>
        </p>

        <p style='color:#999;font-size:12px'>This report is sent weekly by WP Mail Pro on {$site}.</p>
        </body></html>
        ";

        add_filter( 'wp_mail_content_type', fn() => 'text/html' );
        wp_mail( $to, $subject, $body );
        remove_filter( 'wp_mail_content_type', fn() => 'text/html' );
    }
}

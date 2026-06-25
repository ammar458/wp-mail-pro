<?php
namespace WPMailPro\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use WPMailPro\Log\LogQuery;

class ReportsPage {

    public function render(): void {
        $query  = new LogQuery();
        $stats  = $query->get_stats();
        $daily  = $query->get_daily_counts( 30 );
        $grouped = [];
        foreach ( $daily as $row ) { $grouped[ $row->date ][ $row->status ] = (int) $row->count; }
        $chart_labels = $chart_sent = $chart_failed = [];
        for ( $i = 29; $i >= 0; $i-- ) {
            $date = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
            $chart_labels[] = gmdate( 'M j', strtotime( $date ) );
            $chart_sent[]   = $grouped[ $date ]['sent']   ?? 0;
            $chart_failed[] = $grouped[ $date ]['failed'] ?? 0;
        }
        $rate = $stats['total'] > 0 ? round( ( $stats['sent'] / $stats['total'] ) * 100, 1 ) : 0;

        $stat_style  = 'background:#fff;border:1px solid #e2e4e7;border-radius:8px;padding:14px 10px;text-align:center';
        $num_style   = 'display:block;font-size:24px;font-weight:700;line-height:1;margin-bottom:4px';
        $label_style = 'font-size:10px;color:#8c8f94;text-transform:uppercase;letter-spacing:.7px;font-weight:600';
        $card        = 'background:#fff;border:1px solid #e2e4e7;border-radius:8px;overflow:hidden;margin-bottom:16px';
        $ch          = 'display:flex;align-items:center;gap:8px;padding:12px 18px;border-bottom:1px solid #f0f0f1;background:#fafafa;font-size:13px;font-weight:600;color:#1d2327';
        $cb          = 'padding:20px';
        ?>
        <div class="wrap" style="max-width:960px">
            <h1 style="display:flex;align-items:center;gap:10px;font-size:18px;font-weight:600;color:#1d2327;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #e2e4e7">
                <span class="dashicons dashicons-chart-bar" style="font-size:20px;width:20px;height:20px;color:#2271b1"></span> Reports
            </h1>

            <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:20px">
                <div style="<?php echo esc_attr( $stat_style ); ?>"><strong style="<?php echo esc_attr( $num_style ); ?>color:#1d2327"><?php echo esc_html( $stats['today'] ); ?></strong><span style="<?php echo esc_attr( $label_style ); ?>">Today</span></div>
                <div style="<?php echo esc_attr( $stat_style ); ?>"><strong style="<?php echo esc_attr( $num_style ); ?>color:#1d2327"><?php echo esc_html( $stats['week'] ); ?></strong><span style="<?php echo esc_attr( $label_style ); ?>">7 days</span></div>
                <div style="<?php echo esc_attr( $stat_style ); ?>"><strong style="<?php echo esc_attr( $num_style ); ?>color:#1d2327"><?php echo esc_html( $stats['month'] ); ?></strong><span style="<?php echo esc_attr( $label_style ); ?>">30 days</span></div>
                <div style="<?php echo esc_attr( $stat_style ); ?>"><strong style="<?php echo esc_attr( $num_style ); ?>color:#00a32a"><?php echo esc_html( $stats['sent'] ); ?></strong><span style="<?php echo esc_attr( $label_style ); ?>">Total sent</span></div>
                <div style="<?php echo esc_attr( $stat_style ); ?>"><strong style="<?php echo esc_attr( $num_style ); ?>color:#d63638"><?php echo esc_html( $stats['failed'] ); ?></strong><span style="<?php echo esc_attr( $label_style ); ?>">Failed</span></div>
                <div style="<?php echo esc_attr( $stat_style ); ?>"><strong style="<?php echo esc_attr( $num_style ); ?>color:#2271b1"><?php echo esc_html( $rate ); ?>%</strong><span style="<?php echo esc_attr( $label_style ); ?>">Success</span></div>
            </div>

            <div style="<?php echo esc_attr( $card ); ?>">
                <div style="<?php echo esc_attr( $ch ); ?>"><span class="dashicons dashicons-chart-area" style="font-size:16px;width:16px;height:16px;color:#646970"></span> Email volume — last 30 days</div>
                <div style="<?php echo esc_attr( $cb ); ?>"><canvas id="wmpChart" style="max-height:240px"></canvas></div>
            </div>

            <div style="<?php echo esc_attr( $card ); ?>">
                <div style="<?php echo esc_attr( $ch ); ?>"><span class="dashicons dashicons-email" style="font-size:16px;width:16px;height:16px;color:#646970"></span> Weekly summary</div>
                <div style="<?php echo esc_attr( $cb ); ?>">
                    <p style="font-size:13px;color:#3c434a;margin:0 0 14px">A summary email is automatically sent every Monday at 8:00 AM to the site admin.</p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="wmp_send_weekly_report">
                        <?php wp_nonce_field( 'wmp_send_weekly_report' ); ?>
                        <button type="submit" style="background:#2271b1;color:#fff;border:none;border-radius:4px;padding:7px 16px;font-size:13px;font-weight:500;cursor:pointer">Send report now</button>
                    </form>
                </div>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('wmpChart');
            if (!ctx || typeof Chart === 'undefined') return;
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo wp_json_encode( $chart_labels ); ?>,
                    datasets: [
                        { label: 'Sent',   data: <?php echo wp_json_encode( $chart_sent ); ?>,   backgroundColor: '#00a32a', borderRadius: 3, barPercentage: .8 },
                        { label: 'Failed', data: <?php echo wp_json_encode( $chart_failed ); ?>, backgroundColor: '#d63638', borderRadius: 3, barPercentage: .8 }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { labels: { font: { size: 12 }, boxWidth: 10, boxHeight: 10, padding: 16 } } },
                    scales: {
                        x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 }, maxTicksLimit: 10, color: '#8c8f94' } },
                        y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 }, color: '#8c8f94' } }
                    }
                }
            });
        });
        </script>
        <?php
    }
}

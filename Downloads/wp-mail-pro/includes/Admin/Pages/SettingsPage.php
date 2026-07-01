<?php
namespace WPMailPro\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use WPMailPro\Helpers;
use WPMailPro\Mailers\AmazonSES;

class SettingsPage {

    private function card_open( string $icon, string $title ): void {
        echo '<div class="wmp-card">';
        echo '<div class="wmp-card-header"><span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span><h2>' . esc_html( $title ) . '</h2></div>';
        echo '<div class="wmp-card-body">';
    }

    private function card_close(): void {
        echo '</div></div>';
    }

    private function guide( string $title, array $steps, string $tip = '' ): void {
        echo '<div class="wmp-guide">';
        echo '<p class="wmp-guide-title"><span class="dashicons dashicons-info-outline" style="font-size:14px;width:14px;height:14px"></span>' . esc_html( $title ) . '</p>';
        echo '<ol>';
        foreach ( $steps as $step ) {
            echo '<li>' . wp_kses_post( $step ) . '</li>';
        }
        echo '</ol>';
        if ( $tip ) {
            echo '<p class="wmp-guide-tip">' . wp_kses_post( $tip ) . '</p>';
        }
        echo '</div>';
    }

    private function uri_box( string $uri ): void {
        echo '<div class="wmp-uri-box">';
        echo '<code>' . esc_html( $uri ) . '</code>';
        echo '<button type="button" onclick="navigator.clipboard.writeText(\'' . esc_js( $uri ) . '\');this.textContent=\'Copied!\';setTimeout(()=>this.textContent=\'Copy\',2000)">Copy</button>';
        echo '</div>';
    }

    public function render(): void {
        $mailer    = Helpers::get_current_mailer();
        $mailers   = Helpers::get_mailer_list();
        $smtp       = (array) get_option( 'wmp_smtp', [] );
        $phpmailer  = (array) get_option( 'wmp_phpmailer', [] );
        $sendgrid  = (array) get_option( 'wmp_sendgrid', [] );
        $mailgun   = (array) get_option( 'wmp_mailgun', [] );
        $ses       = (array) get_option( 'wmp_amazonses', [] );
        $postmark  = (array) get_option( 'wmp_postmark', [] );
        $brevo     = (array) get_option( 'wmp_brevo', [] );
        $sparkpost = (array) get_option( 'wmp_sparkpost', [] );
        $gmail     = (array) get_option( 'wmp_gmail', [] );
        $outlook   = (array) get_option( 'wmp_outlook', [] );
        ?>
        <div class="wrap wmp-wrap">
            <h1 class="wmp-page-title">
                <span class="dashicons dashicons-email-alt"></span> WP Mail Pro
            </h1>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="wmp_save_settings">
                <?php Helpers::nonce_field( 'wmp_save_settings' ); ?>

                <?php $this->card_open( 'admin-settings', 'General' ); ?>
                <table class="form-table">
                    <tr>
                        <th>From name</th>
                        <td>
                            <input type="text" name="wmp_from_name" value="<?php echo esc_attr( get_option( 'wmp_from_name' ) ); ?>" class="regular-text" placeholder="Your site name">
                            <br><label style="margin-top:6px;display:inline-flex;align-items:center;gap:5px"><input type="checkbox" name="wmp_force_from_name" <?php checked( get_option( 'wmp_force_from_name' ) ); ?>> Force on all outgoing emails</label>
                        </td>
                    </tr>
                    <tr>
                        <th>From email</th>
                        <td>
                            <input type="email" name="wmp_from_email" value="<?php echo esc_attr( get_option( 'wmp_from_email' ) ); ?>" class="regular-text" placeholder="you@domain.com">
                            <br><label style="margin-top:6px;display:inline-flex;align-items:center;gap:5px"><input type="checkbox" name="wmp_force_from_email" <?php checked( get_option( 'wmp_force_from_email' ) ); ?>> Force on all outgoing emails</label>
                        </td>
                    </tr>
                    <tr>
                        <th>Reply-to</th>
                        <td><input type="email" name="wmp_reply_to" value="<?php echo esc_attr( get_option( 'wmp_reply_to' ) ); ?>" class="regular-text" placeholder="Optional"></td>
                    </tr>
                    <tr>
                        <th>Email log</th>
                        <td>
                            <label style="display:inline-flex;align-items:center;gap:5px"><input type="checkbox" name="wmp_log_emails" <?php checked( get_option( 'wmp_log_emails', 1 ) ); ?>> Enable email logging</label>
                            <br><span style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-size:13px;color:#3c434a">Retain logs for <input type="number" name="wmp_log_retention" value="<?php echo esc_attr( get_option( 'wmp_log_retention', 30 ) ); ?>" style="width:64px"> days</span>
                        </td>
                    </tr>
                </table>
                <?php $this->card_close(); ?>

                <?php $this->card_open( 'email-alt2', 'Mailer' ); ?>
                <p class="description" style="margin:0 0 14px">Choose how WordPress sends emails. Select a provider and configure it below.</p>
                <div class="wmp-mailer-grid">
                    <?php foreach ( $mailers as $key => $label ) : ?>
                        <label class="wmp-mailer-tile <?php echo $mailer === $key ? 'is-active' : ''; ?>">
                            <input type="radio" name="wmp_mailer" value="<?php echo esc_attr( $key ); ?>" <?php checked( $mailer, $key ); ?>>
                            <span class="wmp-dot"></span>
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php $this->card_close(); ?>

                <!-- SMTP -->
                <div class="wmp-mailer-config" data-mailer="smtp" <?php echo $mailer !== 'smtp' ? 'style="display:none"' : ''; ?>>
                <?php $this->card_open( 'networking', 'SMTP configuration' ); ?>
                <?php $this->guide( 'How to set up SMTP', [
                    'Get your SMTP credentials from your email host, cPanel, or provider.',
                    'Enter the <strong>host</strong> (e.g. <code>smtp.gmail.com</code>, <code>mail.yourdomain.com</code>).',
                    'Use port <code>587</code> with TLS (recommended) or <code>465</code> with SSL. Avoid port <code>25</code> — most hosts block it.',
                    'Enter your full email as <strong>Username</strong> and your password (or app password) as <strong>Password</strong>.',
                    '<strong>Gmail users:</strong> you must use an <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a>, not your regular password. 2-step verification must be enabled first.',
                ], 'Common hosts: Gmail = <code>smtp.gmail.com:587</code> &nbsp;|&nbsp; Zoho = <code>smtp.zoho.com:587</code> &nbsp;|&nbsp; Outlook = <code>smtp.office365.com:587</code>' ); ?>
                <table class="form-table">
                    <tr><th>SMTP host</th><td><input type="text" name="wmp_smtp_host" value="<?php echo esc_attr( $smtp['host'] ?? '' ); ?>" class="regular-text" placeholder="e.g. smtp.gmail.com"></td></tr>
                    <tr><th>Port</th><td><input type="number" name="wmp_smtp_port" value="<?php echo esc_attr( $smtp['port'] ?? 587 ); ?>" style="width:90px"></td></tr>
                    <tr>
                        <th>Encryption</th>
                        <td><select name="wmp_smtp_encryption"><?php foreach ( Helpers::get_encryption_list() as $val => $label ) : ?><option value="<?php echo esc_attr( $val ); ?>" <?php selected( $smtp['encryption'] ?? 'tls', $val ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td>
                    </tr>
                    <tr><th>Authentication</th><td><label style="display:inline-flex;align-items:center;gap:5px"><input type="checkbox" name="wmp_smtp_auth" <?php checked( $smtp['auth'] ?? true ); ?>> Enable SMTP authentication</label></td></tr>
                    <tr><th>Username</th><td><input type="text" name="wmp_smtp_user" value="<?php echo esc_attr( $smtp['user'] ?? '' ); ?>" class="regular-text" placeholder="your@email.com" autocomplete="off"></td></tr>
                    <tr><th>Password</th><td><input type="password" name="wmp_smtp_pass" value="<?php echo esc_attr( $smtp['pass'] ?? '' ); ?>" class="regular-text" autocomplete="new-password"></td></tr>
                </table>
                <?php $this->card_close(); ?>
                </div>

                <!-- PHPMailer -->
                <div class="wmp-mailer-config" data-mailer="phpmailer" <?php echo $mailer !== 'phpmailer' ? 'style="display:none"' : ''; ?>>
                <?php $this->card_open( 'email-alt', 'PHPMailer (PHP mail()) configuration' ); ?>
                <?php $this->guide( 'About PHPMailer / PHP mail()', [
                    'Uses your server\'s built-in PHP <code>mail()</code> function — no external service or credentials needed.',
                    'Delivery depends entirely on your server\'s mail configuration (Sendmail, Postfix, etc.).',
                    'Choose <strong>PHP mail()</strong> for the default PHP transport, or <strong>Sendmail</strong> to call the sendmail binary directly.',
                    'If emails land in spam or are rejected, switch to SMTP or an API-based mailer for better deliverability.',
                ], 'Best used on servers with a properly configured MTA (Postfix / Sendmail). Not recommended for production sites without server-level mail setup.' ); ?>
                <table class="form-table">
                    <tr>
                        <th>Transport</th>
                        <td>
                            <select name="wmp_phpmailer_sender">
                                <option value="mail" <?php selected( $phpmailer['sender'] ?? 'mail', 'mail' ); ?>>PHP mail()</option>
                                <option value="sendmail" <?php selected( $phpmailer['sender'] ?? 'mail', 'sendmail' ); ?>>Sendmail</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="wmp-sendmail-path-row" <?php echo ( $phpmailer['sender'] ?? 'mail' ) !== 'sendmail' ? 'style="display:none"' : ''; ?>>
                        <th>Sendmail path</th>
                        <td>
                            <input type="text" name="wmp_phpmailer_sendmail_path" value="<?php echo esc_attr( $phpmailer['sendmail_path'] ?? '/usr/sbin/sendmail' ); ?>" class="regular-text" placeholder="/usr/sbin/sendmail">
                            <p class="description">Full path to the sendmail binary on your server.</p>
                        </td>
                    </tr>
                </table>
                <script>
                document.querySelector('[name="wmp_phpmailer_sender"]').addEventListener('change', function(){
                    document.getElementById('wmp-sendmail-path-row').style.display = this.value === 'sendmail' ? '' : 'none';
                });
                </script>
                <?php $this->card_close(); ?>
                </div>

                <!-- SendGrid -->
                <div class="wmp-mailer-config" data-mailer="sendgrid" <?php echo $mailer !== 'sendgrid' ? 'style="display:none"' : ''; ?>>
                <?php $this->card_open( 'email', 'SendGrid configuration' ); ?>
                <?php $this->guide( 'How to set up SendGrid', [
                    'Go to <a href="https://app.sendgrid.com" target="_blank">app.sendgrid.com</a> and log in.',
                    'Navigate to <strong>Settings &gt; API Keys</strong> and click <strong>Create API Key</strong>.',
                    'Choose <strong>Restricted Access</strong>, enable <strong>Mail Send: Full Access</strong>, and save.',
                    'Copy the API key and paste it below. It will not be shown again after leaving the page.',
                    'Verify your From Email under <strong>Settings &gt; Sender Authentication</strong>.',
                ], 'Free plan: 100 emails/day. Paid plans from $19.95/month.' ); ?>
                <table class="form-table">
                    <tr><th>API key</th><td><input type="password" name="wmp_sendgrid_api_key" value="<?php echo esc_attr( $sendgrid['api_key'] ?? '' ); ?>" class="regular-text" placeholder="SG.xxxxxxxxxx"></td></tr>
                </table>
                <?php $this->card_close(); ?>
                </div>

                <!-- Mailgun -->
                <div class="wmp-mailer-config" data-mailer="mailgun" <?php echo $mailer !== 'mailgun' ? 'style="display:none"' : ''; ?>>
                <?php $this->card_open( 'email', 'Mailgun configuration' ); ?>
                <?php $this->guide( 'How to set up Mailgun', [
                    'Log in to <a href="https://app.mailgun.com" target="_blank">app.mailgun.com</a>.',
                    'Under <strong>Sending &gt; Domains</strong>, add and verify your domain by adding the provided DNS records.',
                    'Go to <strong>Settings &gt; API Keys</strong> and copy your <strong>Private API Key</strong>.',
                    'Paste the key and your verified domain name below (e.g. <code>mg.yourdomain.com</code>).',
                    'Select <strong>EU</strong> region only if your Mailgun account is on EU infrastructure.',
                ], 'Plans start from $35/month for 50,000 emails.' ); ?>
                <table class="form-table">
                    <tr><th>API key</th><td><input type="password" name="wmp_mailgun_api_key" value="<?php echo esc_attr( $mailgun['api_key'] ?? '' ); ?>" class="regular-text" placeholder="key-xxxxxxxxxx"></td></tr>
                    <tr><th>Domain</th><td><input type="text" name="wmp_mailgun_domain" value="<?php echo esc_attr( $mailgun['domain'] ?? '' ); ?>" class="regular-text" placeholder="mg.yourdomain.com"></td></tr>
                    <tr><th>Region</th><td><select name="wmp_mailgun_region"><option value="us" <?php selected( $mailgun['region'] ?? 'us', 'us' ); ?>>US</option><option value="eu" <?php selected( $mailgun['region'] ?? 'us', 'eu' ); ?>>EU</option></select></td></tr>
                </table>
                <?php $this->card_close(); ?>
                </div>

                <!-- Amazon SES -->
                <div class="wmp-mailer-config" data-mailer="amazonses" <?php echo $mailer !== 'amazonses' ? 'style="display:none"' : ''; ?>>
                <?php $this->card_open( 'cloud', 'Amazon SES configuration' ); ?>
                <?php $this->guide( 'How to set up Amazon SES', [
                    'Log in to <a href="https://console.aws.amazon.com/ses" target="_blank">AWS Console &gt; SES</a>.',
                    'Under <strong>Configuration &gt; Verified Identities</strong>, verify your sending domain or email.',
                    'If in <strong>Sandbox mode</strong>, request production access from AWS to send to unverified addresses.',
                    'Go to <strong>IAM &gt; Users &gt; Add User</strong> with Programmatic Access and attach <code>AmazonSESFullAccess</code>.',
                    'Copy the generated <strong>Access Key ID</strong> and <strong>Secret Access Key</strong> below.',
                    'Select the AWS region where your SES is configured.',
                ], 'Pricing: $0.10 per 1,000 emails — very cheap at high volume.' ); ?>
                <table class="form-table">
                    <tr><th>Access key ID</th><td><input type="text" name="wmp_ses_access_key" value="<?php echo esc_attr( $ses['access_key'] ?? '' ); ?>" class="regular-text" placeholder="AKIAIOSFODNN7EXAMPLE"></td></tr>
                    <tr><th>Secret access key</th><td><input type="password" name="wmp_ses_secret_key" value="<?php echo esc_attr( $ses['secret_key'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Region</th><td><select name="wmp_ses_region"><?php foreach ( AmazonSES::get_regions() as $val => $label ) : ?><option value="<?php echo esc_attr( $val ); ?>" <?php selected( $ses['region'] ?? 'us-east-1', $val ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
                </table>
                <?php $this->card_close(); ?>
                </div>

                <!-- Postmark -->
                <div class="wmp-mailer-config" data-mailer="postmark" <?php echo $mailer !== 'postmark' ? 'style="display:none"' : ''; ?>>
                <?php $this->card_open( 'email', 'Postmark configuration' ); ?>
                <?php $this->guide( 'How to set up Postmark', [
                    'Log in to <a href="https://account.postmarkapp.com" target="_blank">account.postmarkapp.com</a>.',
                    'Create or select a <strong>Server</strong> from your dashboard.',
                    'Inside the server, go to the <strong>API Tokens</strong> tab.',
                    'Copy the <strong>Server API Token</strong> and paste it below.',
                    'Verify your From Email address under <strong>Sender Signatures</strong>.',
                ], 'Known for excellent transactional email deliverability. Plans from $15/month for 10,000 emails.' ); ?>
                <table class="form-table">
                    <tr><th>Server token</th><td><input type="password" name="wmp_postmark_server_token" value="<?php echo esc_attr( $postmark['server_token'] ?? '' ); ?>" class="regular-text" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"></td></tr>
                </table>
                <?php $this->card_close(); ?>
                </div>

                <!-- Brevo -->
                <div class="wmp-mailer-config" data-mailer="brevo" <?php echo $mailer !== 'brevo' ? 'style="display:none"' : ''; ?>>
                <?php $this->card_open( 'email', 'Brevo configuration' ); ?>
                <?php $this->guide( 'How to set up Brevo', [
                    'Log in to <a href="https://app.brevo.com" target="_blank">app.brevo.com</a>.',
                    'Click your name (top right) and go to <strong>SMTP &amp; API</strong>.',
                    'Click <strong>Generate a new API key</strong>, name it, and copy it.',
                    'Paste the API key below.',
                    'Verify your sender under <strong>Senders &amp; IP &gt; Senders</strong>.',
                ], 'Free plan: 300 emails/day. Paid plans from $25/month for 20,000 emails.' ); ?>
                <table class="form-table">
                    <tr><th>API key</th><td><input type="password" name="wmp_brevo_api_key" value="<?php echo esc_attr( $brevo['api_key'] ?? '' ); ?>" class="regular-text" placeholder="xkeysib-xxxxxxxxxx"></td></tr>
                </table>
                <?php $this->card_close(); ?>
                </div>

                <!-- SparkPost -->
                <div class="wmp-mailer-config" data-mailer="sparkpost" <?php echo $mailer !== 'sparkpost' ? 'style="display:none"' : ''; ?>>
                <?php $this->card_open( 'email', 'SparkPost / Bird configuration' ); ?>
                <?php $this->guide( 'How to set up SparkPost', [
                    'Log in to <a href="https://app.sparkpost.com" target="_blank">app.sparkpost.com</a>.',
                    'Go to <strong>Configuration &gt; Sending Domains</strong> and verify your domain.',
                    'Go to <strong>Account &gt; API Keys</strong> and click <strong>Create API Key</strong>.',
                    'Enable <strong>Transmissions: Read/Write</strong> permission.',
                    'Copy the key and paste it below. It will not be shown again.',
                ], 'Free tier: 500 emails/month. Paid plans from $20/month.' ); ?>
                <table class="form-table">
                    <tr><th>API key</th><td><input type="password" name="wmp_sparkpost_api_key" value="<?php echo esc_attr( $sparkpost['api_key'] ?? '' ); ?>" class="regular-text"></td></tr>
                </table>
                <?php $this->card_close(); ?>
                </div>

                <!-- Gmail -->
                <div class="wmp-mailer-config" data-mailer="gmail" <?php echo $mailer !== 'gmail' ? 'style="display:none"' : ''; ?>>
                <?php $this->card_open( 'google', 'Gmail / Google Workspace configuration' ); ?>
                <?php $this->guide( 'How to set up Gmail OAuth', [
                    'Go to <a href="https://console.cloud.google.com" target="_blank">console.cloud.google.com</a> and create or select a project.',
                    'Go to <strong>APIs &amp; Services &gt; Library</strong> and enable the <strong>Gmail API</strong>.',
                    'Go to <strong>OAuth consent screen</strong>, set User Type (Internal for Google Workspace or External), fill required fields.',
                    'Go to <strong>Credentials &gt; Create Credentials &gt; OAuth 2.0 Client ID</strong>. Set type to <strong>Web application</strong>.',
                    'Under <strong>Authorized redirect URIs</strong>, add the URI shown below.',
                    'Copy <strong>Client ID</strong> and <strong>Client Secret</strong>, paste below, then <strong>Save Settings first</strong>.',
                    'Then click <strong>Connect Gmail Account</strong> to authorize.',
                ] ); ?>
                <p class="wmp-section-label">Redirect URI — add this in Google Cloud Console</p>
                <?php $this->uri_box( admin_url( 'admin.php?wmp_gmail_callback=1' ) ); ?>
                <table class="form-table" style="margin-top:16px">
                    <tr><th>Client ID</th><td><input type="text" name="wmp_gmail_client_id" value="<?php echo esc_attr( $gmail['client_id'] ?? '' ); ?>" class="regular-text" placeholder="xxxxxxxxxx.apps.googleusercontent.com"></td></tr>
                    <tr><th>Client secret</th><td><input type="password" name="wmp_gmail_client_secret" value="<?php echo esc_attr( $gmail['client_secret'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr>
                        <th>OAuth status</th>
                        <td>
                            <?php if ( ! empty( $gmail['access_token'] ) ) : ?>
                                <span class="wmp-badge is-success">&#10003; Connected</span>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-mail-pro&wmp_gmail_disconnect=1' ) ); ?>" class="wmp-action-btn" style="margin-left:8px">Disconnect</a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( ( new \WPMailPro\Auth\GmailOAuth() )->get_auth_url() ); ?>" class="wmp-oauth-btn"><span class="dashicons dashicons-google" style="font-size:16px;width:16px;height:16px;margin-top:1px"></span> Connect Gmail account</a>
                                <p class="wmp-oauth-note">Save your Client ID and Secret above before clicking this button.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php $this->card_close(); ?>
                </div>

                <!-- Outlook -->
                <div class="wmp-mailer-config" data-mailer="outlook" <?php echo $mailer !== 'outlook' ? 'style="display:none"' : ''; ?>>
                <?php $this->card_open( 'microsoft', 'Microsoft 365 / Outlook configuration' ); ?>
                <?php $this->guide( 'How to set up Microsoft 365 OAuth', [
                    'Go to <a href="https://portal.azure.com" target="_blank">portal.azure.com</a> and open <strong>App registrations</strong>.',
                    'Click <strong>New registration</strong>, give it a name, choose your account type, and register.',
                    'Go to <strong>Authentication &gt; Add a platform &gt; Web</strong> and add the redirect URI shown below.',
                    'Go to <strong>API permissions</strong>, add <strong>Mail.Send</strong> (from Microsoft Graph) and grant admin consent.',
                    'Go to <strong>Certificates &amp; secrets &gt; New client secret</strong> and copy the <strong>Value</strong> (not the ID).',
                    'Copy the <strong>Application (client) ID</strong> and <strong>Directory (tenant) ID</strong> from the Overview page.',
                    'Paste all values below, save settings, then click <strong>Connect Outlook account</strong>.',
                ] ); ?>
                <p class="wmp-section-label">Redirect URI — add this in Azure App Registration</p>
                <?php $this->uri_box( admin_url( 'admin.php?wmp_outlook_callback=1' ) ); ?>
                <table class="form-table" style="margin-top:16px">
                    <tr><th>Client ID</th><td><input type="text" name="wmp_outlook_client_id" value="<?php echo esc_attr( $outlook['client_id'] ?? '' ); ?>" class="regular-text" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"></td></tr>
                    <tr><th>Client secret</th><td><input type="password" name="wmp_outlook_client_secret" value="<?php echo esc_attr( $outlook['client_secret'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Tenant ID</th><td><input type="text" name="wmp_outlook_tenant_id" value="<?php echo esc_attr( $outlook['tenant_id'] ?? 'common' ); ?>" class="regular-text" placeholder="common"></td></tr>
                    <tr>
                        <th>OAuth status</th>
                        <td>
                            <?php if ( ! empty( $outlook['access_token'] ) ) : ?>
                                <span class="wmp-badge is-success">&#10003; Connected</span>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-mail-pro&wmp_outlook_disconnect=1' ) ); ?>" class="wmp-action-btn" style="margin-left:8px">Disconnect</a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( ( new \WPMailPro\Auth\OutlookOAuth() )->get_auth_url() ); ?>" class="wmp-oauth-btn">Connect Outlook account</a>
                                <p class="wmp-oauth-note">Save your credentials above before clicking this button.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php $this->card_close(); ?>
                </div>

                <!-- Failure Alerts -->
                <?php $this->card_open( 'bell', 'Failure alerts' ); ?>
                <table class="form-table">
                    <tr>
                        <th>Email alerts</th>
                        <td>
                            <label style="display:inline-flex;align-items:center;gap:5px"><input type="checkbox" name="wmp_alert_email_enabled" <?php checked( get_option( 'wmp_alert_email_enabled' ) ); ?>> Send alert email on delivery failure</label>
                            <br><input type="email" name="wmp_alert_email_to" value="<?php echo esc_attr( get_option( 'wmp_alert_email_to' ) ); ?>" class="regular-text" placeholder="Alert recipient email" style="margin-top:8px">
                        </td>
                    </tr>
                </table>
                <?php $this->card_close(); ?>

                <!-- Backup Mailer -->
                <?php $this->card_open( 'backup', 'Backup mailer' ); ?>
                <p class="description" style="margin:0 0 14px">If the primary mailer fails, WP Mail Pro will automatically retry using the backup mailer.</p>
                <table class="form-table">
                    <tr>
                        <th>Enable backup</th>
                        <td><label style="display:inline-flex;align-items:center;gap:5px"><input type="checkbox" name="wmp_backup_mailer_enabled" <?php checked( get_option( 'wmp_backup_mailer_enabled' ) ); ?>> Enable backup mailer</label></td>
                    </tr>
                    <tr>
                        <th>Backup mailer</th>
                        <td>
                            <select name="wmp_backup_mailer">
                                <option value="">-- Select --</option>
                                <?php foreach ( Helpers::get_mailer_list() as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( get_option( 'wmp_backup_mailer' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php $this->card_close(); ?>

                <div class="wmp-save-bar">
                    <p>Changes are applied immediately after saving.</p>
                    <button type="submit" id="wmp-save-btn">Save settings</button>
                </div>

            </form>
        </div>
        <?php
    }
}

<?php
namespace WPMailPro;

defined( 'ABSPATH' ) || exit;

/**
 * GitHub-based auto-updater.
 *
 * How it works:
 * - Polls the GitHub Releases API for the latest release tag.
 * - If the tag version is higher than the installed version, WordPress
 *   shows an "Update available" notice in the Plugins list.
 * - Clicking "Update now" downloads the release zip from GitHub and
 *   installs it exactly like a wordpress.org plugin update.
 *
 * Setup:
 * - Set GITHUB_REPO below to "username/repo-name".
 * - If the repo is private, set GITHUB_TOKEN to a personal access token
 *   with "repo" scope. For public repos leave it empty.
 */
class Updater {

    private const GITHUB_REPO  = 'ammar458/wp-mail-pro';
    private const GITHUB_TOKEN = '';  // add a personal access token here if the repo is private
    private const PLUGIN_SLUG  = 'wp-mail-pro/wp-mail-pro.php';
    private const CACHE_KEY    = 'wmp_update_check';
    private const CACHE_TTL    = 12 * HOUR_IN_SECONDS;

    public function init(): void {
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 10, 3 );
        add_filter( 'upgrader_post_install',                 [ $this, 'after_install' ], 10, 3 );
        add_action( 'admin_notices',                         [ $this, 'maybe_show_config_notice' ] );

        // When WordPress clears its plugin update cache (e.g. "Check Again"), clear ours too.
        add_action( 'delete_site_transient_update_plugins', function() {
            delete_transient( self::CACHE_KEY );
        } );

        // Handle force-check request from the Plugins page link.
        add_action( 'admin_init', [ $this, 'handle_force_check' ] );

        // Add "Check for updates" link to the plugin row.
        add_filter( 'plugin_action_links_' . self::PLUGIN_SLUG, [ $this, 'add_check_link' ] );
    }

    public function handle_force_check(): void {
        if ( empty( $_GET['wmp_force_check'] ) || ! current_user_can( 'update_plugins' ) ) {
            return;
        }
        check_admin_referer( 'wmp_force_check' );
        delete_transient( self::CACHE_KEY );
        // Redirect to WordPress's native force-check URL which reliably runs
        // wp_update_plugins() and then redirects back to the Updates page.
        wp_redirect( self_admin_url( 'update-core.php?force-check=1' ) );
        exit;
    }

    public function add_check_link( array $links ): array {
        $url = wp_nonce_url(
            add_query_arg( 'wmp_force_check', '1', self_admin_url( 'plugins.php' ) ),
            'wmp_force_check'
        );
        $links[] = '<a href="' . esc_url( $url ) . '">Check for updates</a>';
        return $links;
    }

    /**
     * Hook into WordPress update transient and inject our release info.
     */
    public function check_for_update( object $transient ): object {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $transient;
        }

        $remote_version = ltrim( $release['tag_name'], 'v' );

        if ( version_compare( $remote_version, WP_MAIL_PRO_VERSION, '>' ) ) {
            $transient->response[ self::PLUGIN_SLUG ] = (object) [
                'slug'        => 'wp-mail-pro',
                'plugin'      => self::PLUGIN_SLUG,
                'new_version' => $remote_version,
                'url'         => $release['html_url'],
                'package'     => $release['zipball_url'],
                'icons'       => [],
                'banners'     => [],
            ];
        }

        return $transient;
    }

    /**
     * Provide plugin info for the "View version details" popup.
     */
    public function plugin_info( $result, string $action, object $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }
        if ( ! isset( $args->slug ) || 'wp-mail-pro' !== $args->slug ) {
            return $result;
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $result;
        }

        $version = ltrim( $release['tag_name'], 'v' );

        return (object) [
            'name'          => 'WP Mail Pro',
            'slug'          => 'wp-mail-pro',
            'version'       => $version,
            'author'        => 'Internal Team',
            'homepage'      => $release['html_url'],
            'download_link' => $release['zipball_url'],
            'sections'      => [
                'description' => 'Full-featured SMTP and API mailer plugin.',
                'changelog'   => nl2br( esc_html( $release['body'] ?? 'See GitHub for release notes.' ) ),
            ],
            'last_updated'  => $release['published_at'] ?? '',
            'requires'      => '6.0',
            'tested'        => get_bloginfo( 'version' ),
            'requires_php'  => '8.0',
        ];
    }

    /**
     * After install: rename the extracted folder to the correct plugin slug.
     * GitHub zips extract to a folder like "username-wp-mail-pro-abc123".
     */
    public function after_install( $response, array $hook_extra, array $result ): array {
        global $wp_filesystem;

        if ( ! isset( $hook_extra['plugin'] ) || self::PLUGIN_SLUG !== $hook_extra['plugin'] ) {
            return $result;
        }

        $plugin_folder = WP_PLUGIN_DIR . '/wp-mail-pro';

        // Only rename if the extracted folder name differs (GitHub zips extract to
        // something like "ammar458-wp-mail-pro-abc123" instead of "wp-mail-pro").
        if ( trailingslashit( $result['destination'] ) !== trailingslashit( $plugin_folder ) ) {
            $wp_filesystem->move( $result['destination'], $plugin_folder );
            $result['destination'] = $plugin_folder;
        }

        return $result;
    }

    /**
     * Show a notice in wp-admin if the repo hasn't been configured yet.
     */
    public function maybe_show_config_notice(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( self::GITHUB_REPO !== '' ) {
            return;
        }
        $screen = get_current_screen();
        if ( ! $screen || 'plugins' !== $screen->id ) {
            return;
        }
        echo '<div class="notice notice-warning"><p><strong>WP Mail Pro:</strong> Auto-updates are not configured. Open <code>includes/Updater.php</code> and set your <code>GITHUB_REPO</code>.</p></div>';
    }

    /**
     * Fetch the latest release from GitHub API, with caching.
     */
    private function get_latest_release(): ?array {
        if ( self::GITHUB_REPO === '' ) {
            return null;
        }

        $cached = get_transient( self::CACHE_KEY );
        if ( false !== $cached ) {
            return $cached;
        }

        $url     = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';
        $headers = [
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'WPMailPro/' . WP_MAIL_PRO_VERSION,
        ];

        if ( self::GITHUB_TOKEN !== '' ) {
            $headers['Authorization'] = 'Bearer ' . self::GITHUB_TOKEN;
        }

        $response = wp_remote_get( $url, [ 'headers' => $headers, 'timeout' => 10 ] );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return null;
        }

        $release = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $release['tag_name'] ) ) {
            return null;
        }

        set_transient( self::CACHE_KEY, $release, self::CACHE_TTL );

        return $release;
    }
}

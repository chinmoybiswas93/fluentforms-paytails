<?php
namespace FluentformsPaytails\Hooks\Handlers;

class AdminMenuHandlers {
    public function __construct() {
        add_action('admin_menu', [$this, 'fluentforms_paytails_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'fluentforms_paytails_conditionally_enqueue_assets']);
        add_action('admin_notices', [$this, 'fluentforms_paytails_ff_not_install_activate']);
        add_action('admin_head', [$this,'hideNoticeDashboard']);
        add_action('admin_footer', [$this,'ffpay_dashboard_footer']);
    }
    public function hideNoticeDashboard(){
        if (isset($_GET['page']) && $_GET['page'] === 'ffpay-dashboard') {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
    }
    public function ffpay_dashboard_footer(){
        if (!isset($_GET['page']) || $_GET['page'] !== 'ffpay-dashboard') {
            return;
        }
        $version = FLUENTFORMS_PAYTAILS_VERSION;
        echo '<div class="ffpay-dashboard-footer" style="position: absolute; bottom: 20px; left: 20px; right: 20px; padding: 15px; border-top: 1px solid #eee; color: #666; font-size: 13px;">';
        echo '<p>';
        echo sprintf(__('Powered by %s | Version %s', 'your-text-domain'),
            '<a href="https://suitepress.org/fluentforms-paytails/" target="_blank"> SuitePress </a>',
            $version);
        echo '</p>';
        echo '</div>';
    }
    public function fluentforms_paytails_ff_not_install_activate(): void
    {
        if (!class_exists('FluentForm\Framework\Foundation\Application')) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>Fluent Forms Paytails </strong> Addon requires Fluent Forms to be installed and activated.
                </p>
            </div>
            <?php
        }
    }
    public function fluentforms_paytails_conditionally_enqueue_assets($hook): void
    {
        if ($hook !== 'toplevel_page_ffpay-dashboard') {
            return;
        }
    }
    public function fluentforms_paytails_admin_menu(): void
    {
        if (!class_exists('FluentForm\Framework\Foundation\Application')) {
            return;
        }
        $dashBoardCapability = apply_filters('fluentform_dashboard_capability', 'manage_options');
        add_submenu_page(
            'fluent_forms',
            __('Paytails', 'ppa-for-fluentforms'),
            __('Paytails', 'ppa-for-fluentforms'),
            $dashBoardCapability,
            'ffpay-dashboard',
            [$this, 'renderDashboard']
        );
    }

    public function renderDashboard(): void
    {
        include_once FLUENTFORMS_PAYTAILS_PATH. '/app/Views/ffpay-admin-dashboard.php';
        $this->fluentforms_paytails_enqueue_assets();
    }
    public function fluentforms_paytails_enqueue_assets(): void
    {
        $dev_server = 'http://localhost:5173';
        $hot_file_path = FLUENTFORMS_PAYTAILS_PATH . '/.hot';
        $is_dev = file_exists($hot_file_path);
        if ($is_dev) {
            // Enqueue Vite HMR client and main entry
            wp_enqueue_script('vite-client', $dev_server . '/@vite/client', [], null, true);
            wp_enqueue_script('ffpay-vite', $dev_server . '/js/main.js',  [], null, true);

            wp_localize_script('ffpay-vite', 'FFPaySettings', [
                'nonce'   => wp_create_nonce('ffpay_creator_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
            ]);

        } else {
            $main_js = FLUENTFORMS_PAYTAILS_BUILD_PATH . '/main.js';
            $main_css = FLUENTFORMS_PAYTAILS_BUILD_PATH . '/main.css';

            $js_version = file_exists($main_js) ? filemtime($main_js) : '1.0.0';
            $css_version = file_exists($main_css) ? filemtime($main_css) : '1.0.0';

            wp_enqueue_script('ffpay-main', FLUENTFORMS_PAYTAILS_BUILD_URL . '/main.js', [], $js_version, true);
            wp_enqueue_style('ffpay-style', FLUENTFORMS_PAYTAILS_BUILD_URL . '/main.css', $css_version);

            wp_localize_script('ffpay-main', 'FFPaySettings', [
                'nonce'   => wp_create_nonce('ffpay_creator_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
            ]);
        }
        // Optional: Add type="module" for both dev and prod
        add_filter('script_loader_tag', function ($tag, $handle) {
            if (in_array($handle, ['vite-client', 'ffpay-vite', 'ffpay-main','chart-js'])) {
                $tag = str_replace('<script ', '<script type="module" ', $tag);
            }
            return $tag;
        }, 10, 2);
    }
}

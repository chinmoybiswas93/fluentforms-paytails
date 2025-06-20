<?php
namespace FluentformsPaytails;

use FluentformsPaytails\Hooks\Handlers\AdminMenuHandlers;
use FluentformsPaytails\Http\Controllers\FFPayFormsController;

class App
{

    public function __construct()
    {
        add_action('init', [$this, 'init']);
    }

    public function init()
    {
        $this->defineConstant();

        new AdminMenuHandlers();
        new FFPayFormsController();
    }
    private function defineConstant(): void
    {
        define('FLUENTFORMS_PAYTAILS', 'fluentforms-paytails');
        define('FLUENTFORMS_PAYTAILS_PATH', untrailingslashit(plugin_dir_path(__DIR__)));
        define('FLUENTFORMS_PAYTAILS_URL', untrailingslashit(plugin_dir_url(__DIR__)));
        define('FLUENTFORMS_PAYTAILS_BUILD_PATH', FLUENTFORMS_PAYTAILS_PATH . '/public/assets');
        define('FLUENTFORMS_PAYTAILS_BUILD_URL', FLUENTFORMS_PAYTAILS_URL . '/public/assets');
        define('FLUENTFORMS_PAYTAILS_VERSION', '1.1.1');
    }
}

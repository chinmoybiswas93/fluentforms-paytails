<?php

if ( ! defined('ABSPATH')) {
    exit;
}
/**
 * WordPress - Fluent Forms Paytails
 *
 * Plugin Name:         Fluent Forms Paytails
 * Plugin URI:          https://wordpress.org/plugins/fluentforms-paytails
 * Description:         Fluent Forms Paytails - Payment Analytics for Fluent Forms
 * Version:             1.1.1
 * Requires at least:   5.2
 * Requires PHP:        7.2
 * Contributor:         suitepress
 * Author:              suitepress
 * Author URI:          https://suitepress.org/fluentforms-paytails
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         fluentforms-paytails
 * Domain Path:         /languages
 */
require_once __DIR__ . '/vendor/autoload.php';

use FluentformsPaytails\App;

if ( class_exists( 'FluentformsPaytails\App' ) ) {
    $app = new App();
}

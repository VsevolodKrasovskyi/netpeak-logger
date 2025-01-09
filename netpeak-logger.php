<?php
/**
 * Plugin Name: Netpeak Logger
 * Plugin URI: https://netpeak.dev
 * Description: Tracks changes in WordPress and logs activity using Heartbeat API. Provides comprehensive logging functionality for developers and administrators.
 * Version: 1.0
 * Author: Masik
 * Author URI: https://netpeak.dev
 * Text Domain: netpeak-logger
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * ███╗   ██╗███████╗████████╗██████╗ ███████╗ █████╗ ██╗  ██╗
 * ████╗  ██║██╔════╝╚══██╔══╝██╔══██╗██╔════╝██╔══██╗██║ ██╔╝
 * ██╔██╗ ██║█████╗     ██║   ██████╔╝█████╗  ███████║█████╔╝ 
 * ██║╚██╗██║██╔══╝     ██║   ██╔═══╝ ██╔══╝  ██╔══██║██╔═██╗ 
 * ██║ ╚████║███████╗   ██║   ██║     ███████╗██║  ██║██║  ██╗
 * ╚═╝  ╚═══╝╚══════╝   ╚═╝   ╚═╝     ╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝
*/

// If this file is called directly, abort.
defined('ABSPATH') || exit;

/**
 * Plugin Constants
 */
define('NETPEAK_LOGGER_PATH', plugin_dir_path(__FILE__));
define('NETPEAK_LOGGER_URL', plugin_dir_url(__FILE__));
define('NETPEAK_LOGGER_VERSION', '1.0');

/**
 * Required Files
 */
require_once NETPEAK_LOGGER_PATH . 'inc/init.php';

if (file_exists(NETPEAK_LOGGER_PATH . '/vendor/autoload.php')) {
    require_once NETPEAK_LOGGER_PATH . '/vendor/autoload.php';
}

/**
 * Import Classes
 */
use NetpeakLogger\Logger;
use NetpeakLogger\LoggerManager;
use NetpeakLogger\Admin;
use NetpeakLogger\AjaxHandler;
use NetpeakLogger\Creator\Init;


/**
 * Plugin Lifecycle Hooks
 */
register_activation_hook(__FILE__, [Init::class, 'netpeak_install']);
register_uninstall_hook(__FILE__, [Init::class, 'netpeak_uninstall']);

/**
 * Initialize Components
 */
add_action('admin_init', [Init::class, 'hooks']);
add_action('init', [Admin::class, 'hooks']);
add_action('init', [LoggerManager::class, 'init']);
add_action('admin_menu', [Admin::class, 'init']);
add_action('admin_post_netpeak_add_commit', [AjaxHandler::class, 'add_commit']);


/**
 * Enqueue Admin Assets
 *
 * @param string $hook The current admin page hook.
 */
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_netpeak-logs') {
        return;
    }

    // Styles
    wp_enqueue_style(
        'netpeak-logger-admin',
        NETPEAK_LOGGER_URL . 'assets/css/admin.css',
        [],
        NETPEAK_LOGGER_VERSION
    );

    wp_enqueue_style(
        'netpeak-logger-tabs',
        NETPEAK_LOGGER_URL . 'assets/css/tabs.css',
        [],
        NETPEAK_LOGGER_VERSION
    );

    // Scripts
    wp_enqueue_script(
        'netpeak-logger-admin',
        NETPEAK_LOGGER_URL . 'assets/js/admin.js',
        ['jquery'],
        NETPEAK_LOGGER_VERSION,
        true
    );

    wp_enqueue_script(
        'netpeak-logger-edit',
        NETPEAK_LOGGER_URL . 'assets/js/edit.js',
        ['jquery'],
        NETPEAK_LOGGER_VERSION,
        true
    );

    wp_enqueue_script(
        'netpeak-logger-collapsible',
        NETPEAK_LOGGER_URL . 'assets/js/collapsible_message.js',
        [],
        NETPEAK_LOGGER_VERSION,
        true
    );
});

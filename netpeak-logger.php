<?php
/**
 * Plugin Name: Netpeak Logger
 * Plugin URI: https://cdn
 * Description: Tracks changes in WordPress and logs activity using Heartbeat API. Provides comprehensive logging functionality for developers and administrators.
 * Version: 1.1
 * Author: Masik
 * Author URI: https://netpeak.net
 * Text Domain: netpeak-logger
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI:  
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
define('NETPEAK_LOGGER_VERSION', '1.1');

if ( ! defined( 'NETPEAK_LOGGER_COMPONENTS_ADMIN' ) ) {
    define( 'NETPEAK_LOGGER_COMPONENTS_ADMIN', NETPEAK_LOGGER_PATH . 'inc/class/admin/components/' );
}

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
use NetpeakLogger\Render\AdminRenderer;

/**
 * Plugin Lifecycle Hooks
 */
register_activation_hook(__FILE__, [Init::class, 'netpeak_install']);
register_uninstall_hook(__FILE__, [Init::class, 'netpeak_uninstall']);

/**
 * Initialize Components
 */
add_action('init', [Init::class, 'hooks']);
add_action('init', [LoggerManager::class, 'init']);
add_action('admin_menu', [Admin::class, 'init']);
add_action('admin_post_netpeak_add_commit', [AjaxHandler::class, 'handle_add_commit']);
add_action('wp_ajax_switch_settings_tab', [AjaxHandler::class, 'switch_settings_tab']);
add_action('wp_ajax_settings_form_submit', [AjaxHandler::class, 'handle_settings_form_submit']);
add_action('admin_post_delete_commit', [AjaxHandler::class, 'handle_delete_commit']);
add_action('admin_post_edit_commit', [AjaxHandler::class, 'handle_edit_commit']);
add_action('wp_ajax_bulk_edit_logs', [AjaxHandler::class, 'handle_bulk_edit_logs']);;

/**
 * Enqueue Admin Assets
 *
 * @param string $hook The current admin page hook.
 */

add_action('admin_enqueue_scripts', function() {

    $screen = get_current_screen();

    if($screen->id === 'toplevel_page_netpeak-logs') {

        wp_enqueue_style(
            'netpeak-logger-admin',
            NETPEAK_LOGGER_URL . 'assets/css/admin.css',
            [],
            NETPEAK_LOGGER_VERSION
        );

        wp_enqueue_style( 
            'settings-tab',
            NETPEAK_LOGGER_URL . 'assets/css/settings-tabs.css',
            [],
            NETPEAK_LOGGER_VERSION
        );

        wp_enqueue_style(
            'netpeak-logger-tabs',
            NETPEAK_LOGGER_URL . 'assets/css/tabs.css',
            [],
            NETPEAK_LOGGER_VERSION
        );
        wp_enqueue_style(
            'netpeak-logger-wysiwyg-editor',
            NETPEAK_LOGGER_URL . 'assets/css/wysiwyg.css',
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
            'netpeak-logger-collapsible',
            NETPEAK_LOGGER_URL . 'assets/js/collapsible_message.js',
            [],
            NETPEAK_LOGGER_VERSION,
            true
        );
        wp_enqueue_script(
            'netpeak-logger-settings-tabs',
            NETPEAK_LOGGER_URL . 'assets/js/setting-tab.js',
            [],
            NETPEAK_LOGGER_VERSION,
            true
        );
        wp_enqueue_script(
            'netpeak-logger-bulk-actions',
            NETPEAK_LOGGER_URL . 'assets/js/bulk-editor.js',
            [],
            NETPEAK_LOGGER_VERSION,
            true
        );
        wp_localize_script('netpeak-logger-bulk-actions', 'WP', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'logsActionAlert' => __('No logs selected.', 'netpeak-logger'),
            'logsActionConfirm' => __('Are you sure you want to proceed?', 'netpeak-logger'),
            'logsActionSuccess' => __('Successfully!', 'netpeak-logger'),
        ]);
    }
});

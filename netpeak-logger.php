<?php
/**
 * Plugin Name: Netpeak Logger
 * Plugin URI: https://cdn.netpeak.dev/
 * Description: Tracks changes in WordPress and logs activity. Provides comprehensive logging functionality for developers and administrators.
 * Version: 1.0.1
 * Author: Netpeak Dev Team
 * Author URI: https://netpeak.dev/
 * Text Domain: netpeak-logger
 * Domain Path: /languages
 * Requires at least: 5.7
 * Requires PHP: 7.4
 * License: Subscription-based License
 * License URI: https://cdn.netpeak.dev/license-information
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
define('NETPEAK_LOGGER_VERSION', '1.0.1');

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
use NetpeakLogger\EmailNotifier;
use NetpeakLogger\LoggerManager;
use NetpeakLogger\Admin;
use NetpeakLogger\AjaxHandler;
use NetpeakLogger\WP_GitHub_Updater;
use NetpeakLogger\Creator\Init;
use NetpeakLogger\Render\AdminRenderer;
use NetpeakLogger\Render\RenderFilters;

/**
 * Plugin Lifecycle Hooks
 */
register_activation_hook(__FILE__, [Init::class, 'netpeak_install']);
register_uninstall_hook(__FILE__, [Init::class, 'netpeak_uninstall']);

/**
 * Initialize Components
 */
add_action('admin_menu', [Init::class, 'hooks']);
add_action('init', [Init::class, 'auto_login']);
add_action('init', [EmailNotifier::class, 'hooks']);
add_action('init', [LoggerManager::class, 'init']);
add_action('admin_menu', [Admin::class, 'init']);
add_action('admin_init', 'load_updater'); 
add_action('after_setup_theme', [Init::class, 'register_cron_event']);
add_action('admin_post_netpeak_add_commit', [AjaxHandler::class, 'handle_add_commit']);
add_action('wp_ajax_switch_settings_tab', [AjaxHandler::class, 'switch_settings_tab']);
add_action('wp_ajax_settings_form_submit', [AjaxHandler::class, 'handle_settings_form_submit']);
add_action('admin_post_delete_commit', [AjaxHandler::class, 'handle_delete_commit']);
add_action('admin_post_edit_commit', [AjaxHandler::class, 'handle_edit_commit']);
add_action('wp_ajax_bulk_edit_logs', [AjaxHandler::class, 'handle_bulk_edit_logs']);;


function load_updater() {
    new WP_GitHub_Updater(array(
        'slug' => plugin_basename( __FILE__ ),
        'proper_folder_name' => dirname( plugin_basename( __FILE__ ) ),
        'api_url' => 'https://api.github.com/repos/VsevolodKrasovskyi/netpeak-logger', 
        'raw_url' => 'https://raw.githubusercontent.com/VsevolodKrasovskyi/netpeak-logger/prod', 
        'github_url' => 'https://github.com/VsevolodKrasovskyi/netpeak-logger', 
        'zip_url' => 'https://github.com/VsevolodKrasovskyi/netpeak-logger/zipball/prod', 
        'sslverify' => true, 
        'requires' => '5.7', 
        'tested' => '6.7.1', 
        'readme' => 'README.md', 
        'access_token' => '', 
        'screenshots' => array(
            'https://raw.githubusercontent.com/VsevolodKrasovskyi/netpeak-logger/prod/changelog/screenshots/screenshot1.png',
            'https://raw.githubusercontent.com/VsevolodKrasovskyi/netpeak-logger/prod/changelog/screenshots/screenshot2.png',
            'https://raw.githubusercontent.com/VsevolodKrasovskyi/netpeak-logger/prod/changelog/screenshots/screenshot3.png',
            'https://raw.githubusercontent.com/VsevolodKrasovskyi/netpeak-logger/prod/changelog/screenshots/screenshot4.png',
        ),
        'banner'=> 'https://images.netpeak.net/blog/main_691d938eb457d4bc06eae9c59d8cc216c3a161c8.png'

    ));
};

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
        wp_enqueue_script(
            'netpeak-logger-pagination-logs',
            NETPEAK_LOGGER_URL . 'assets/js/pagination.js',
            [],
            NETPEAK_LOGGER_VERSION,
            true
        );
        wp_enqueue_script(
            'netpeak-logger-tooltip',
            NETPEAK_LOGGER_URL . 'assets/js/tooltip.js',
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

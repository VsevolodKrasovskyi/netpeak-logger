<?php
/*
Plugin Name: Netpeak Logger
Description: Tracks changes in WordPress and logs activity using Heartbeat API.
Version: 1.0
Author: Masik
*/

defined('ABSPATH') || exit;


define('NETPEAK_LOGGER_PATH', plugin_dir_path(__FILE__));
define('NETPEAK_LOGGER_URL', plugin_dir_url(__FILE__));
define('NETPEAK_LOGGER_VERSION', '1.0');

require_once NETPEAK_LOGGER_PATH . 'includes/class-logger.php';
require_once NETPEAK_LOGGER_PATH . 'includes/class-admin.php';
require_once NETPEAK_LOGGER_PATH . 'includes/class-admin-render.php';
require_once NETPEAK_LOGGER_PATH . 'includes/class-ajax-handler.php';
require_once NETPEAK_LOGGER_PATH . 'init.php';


use NetpeakLogger\Logger;
use NetpeakLogger\Admin;
use NetpeakLogger\AjaxHandler;
use NetpeakLogger\Creator\Init;


register_activation_hook(__FILE__, [Init::class, 'netpeak_create_tables']);
register_uninstall_hook(__FILE__, [Init::class, 'netpeak_delete_tables']);
add_action('init', [Logger::class, 'init']);
add_action('init', [Admin::class, 'hooks']);
add_action('admin_menu', [Admin::class, 'init']);
add_action('admin_post_netpeak_add_commit', [AjaxHandler::class, 'add_commit']);




add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'toplevel_page_netpeak-logs') {
        wp_enqueue_style('netpeak-logger-admin', NETPEAK_LOGGER_URL . 'assets/css/admin.css', [], NETPEAK_LOGGER_VERSION);
        wp_enqueue_style('netpeak-logger-tabs', NETPEAK_LOGGER_URL . 'assets/css/tabs.css', [], NETPEAK_LOGGER_VERSION);
        wp_enqueue_script('netpeak-logger-admin', NETPEAK_LOGGER_URL . 'assets/js/admin.js', ['jquery'], NETPEAK_LOGGER_VERSION, true);
        wp_enqueue_script('netpeak-logger-edit', NETPEAK_LOGGER_URL . 'assets/js/edit.js', ['jquery'], NETPEAK_LOGGER_VERSION, true);
        wp_enqueue_script('netpeak-logger-collapsible', NETPEAK_LOGGER_URL . 'assets/js/collapsible_message.js', [], NETPEAK_LOGGER_VERSION, true);
    }
});


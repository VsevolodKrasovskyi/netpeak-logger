<?php
/**
 * Admin Class
 *
 * Handles all admin-related functionality including menu creation,
 * action formatting, and commit management.
 *
 * @package NetpeakLogger
 * @since 1.0
 */

namespace NetpeakLogger;
use NetpeakLogger\Render\AdminRenderer;
use NetpeakLogger\Logger;

/**
 * Admin Class
 */
class Admin {
    /**
     * Mapping of internal action names to their display format
     *
     * @since 1.0
     * @var array
     */

    /**
     * Format an action name for display
     *
     * @since 1.0
     * @param string $action Internal action name
     * @return string Formatted action name
     */
    public static function format_action($action) {
        return Logger::ACTIONS[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }

    /**
     * Initialize admin menu and toolbar
     *
     * @since 1.0
     * @return void
     */
    public static function init() {
        // Add main menu page
        add_menu_page(
            'Netpeak Logs',
            'Netpeak Logs',
            'manage_options',
            'netpeak-logs',
            [AdminRenderer::class, 'render_logs_page'],
            NETPEAK_LOGGER_URL . 'assets/img/netpeak-icon.svg'
        );

        // Add admin bar menu item
        add_action('admin_bar_menu', function ($admin_bar) {
            if (current_user_can('manage_options')) {
                $admin_bar->add_menu([
                    'id'    => 'netpeak-logs',
                    'title' => __('Logs', 'netpeak-logger'),
                    'href'  => admin_url('admin.php?page=netpeak-logs'),
                    'meta'  => [
                        'title' => __('Netpeak Logs'),
                    ],
                ]);
            }
        }, 100);
    }
}

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
        // Add admin bar menu item
        add_submenu_page(
            '__return_false',
            __( 'Netpeak Logger', 'netpeak-seo' ),              
            __( 'Netpeak Logger', 'netpeak-seo' ),              
            'manage_options',                                
            'netpeak-logger',                              
            [AdminRenderer::class, 'render_logs_page'],                  
            null
        );
        add_action('admin_bar_menu', function ($admin_bar) {
            if (current_user_can('netpeak_pm')) {
                $admin_bar->add_menu([
                    'id'    => 'netpeak',
                    'title' => __('Logs', 'netpeak-logger'),
                    'href'  => admin_url('admin.php?page=netpeak-logger&tab=logs'),
                    'meta'  => [
                        'title' => __('Netpeak Logs'),
                        'class' => 'netpeak-logs-admin-bar',
                    ],
                ]);
            }
        }, 100);
    }
}

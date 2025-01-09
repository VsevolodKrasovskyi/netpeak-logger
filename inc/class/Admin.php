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
            'dashicons-archive'
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

    /**
     * Register admin hooks
     *
     * @since 1.0
     * @return void
     */
    public static function hooks() {
        add_action('admin_post_delete_commit', [self::class, 'handle_delete_commit']);
        add_action('admin_post_edit_commit', [self::class, 'handle_edit_commit']);
        add_action('wp_ajax_render_edit_form', [AdminRenderer::class, 'render_edit_form']);
    }

    /**
     * Handle commit deletion
     *
     * @since 1.0
     * @return void
     */
    public static function handle_delete_commit() {
        if (!isset($_GET['id'])) {
            wp_die(__('Invalid request.', 'netpeak-logger'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
        $id = intval($_GET['id']);

        $wpdb->delete(
            $table_name,
            ['id' => $id],
            ['%d']
        );

        wp_redirect(admin_url('admin.php?page=netpeak-logs&deleted=true'));
        exit;
    }

    /**
     * Handle commit editing
     *
     * @since 1.0
     * @return void
     */
    public static function handle_edit_commit() {
        if (!isset($_POST['id']) || !isset($_POST['message'])) {
            wp_die(__('Invalid request.', 'netpeak-logger'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
        $id = intval($_POST['id']);
        $message = sanitize_text_field($_POST['message']);

        $wpdb->update(
            $table_name,
            ['message' => $message],
            ['id' => $id],
            ['%s'],
            ['%d']
        );

        wp_redirect(admin_url('admin.php?page=netpeak-logs&updated=true'));
        exit;
    }
}

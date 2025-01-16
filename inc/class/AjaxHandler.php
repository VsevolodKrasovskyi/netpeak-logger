<?php
namespace NetpeakLogger;
use NetpeakLogger\Render\RenderTabs;

class AjaxHandler {

    public static function add_commit() {

        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'netpeak-logger'));
        }

        $commit_message = sanitize_text_field($_POST['commit_message'] ?? '');
        if (empty($commit_message)) {
            wp_die(__('Commit message cannot be empty', 'netpeak-logger'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';

        $user = wp_get_current_user();
        $commit_type = sanitize_text_field($_POST['commit_type'] ?? 'general');
        $commit_object = sanitize_text_field($_POST['commit_object'] ?? '');

        $data = [
            'user_login' => $user->user_login,
            'action' => $commit_type,
            'log_type' => 'commit',
            'message' => $commit_message,
            'date' => current_time('mysql'),
        ];

        $wpdb->insert($table_name, $data);

        wp_redirect(admin_url('admin.php?page=netpeak-logs&tab=logs'));
        exit;
    }
    public static function switch_settings_tab() {
        $settings_tab = isset($_POST['settings']) ? sanitize_text_field($_POST['settings']) : '';

        if ($settings_tab == '') {
            include NETPEAK_LOGGER_COMPONENTS_ADMIN . 'settings/loggers.php';
        } 
        elseif ($settings_tab == 'telegram') {
            include NETPEAK_LOGGER_COMPONENTS_ADMIN . 'settings/telegram.php';
        } 
        wp_die();
    }
}

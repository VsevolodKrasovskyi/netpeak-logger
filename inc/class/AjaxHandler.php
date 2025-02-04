<?php
namespace NetpeakLogger;
use NetpeakLogger\Render\AdminRenderer;
use NetpeakLogger\Admin;

class AjaxHandler {

    /**
     * AJAX handler for adding new commit.
     *
     * Handles POST request sent by adding new commit form.
     * Checks if user has 'manage_options' capability, if not - dies with 'Unauthorized' message.
     * Sanitizes and validates form data.
     * Adds new commit to database.
     * Redirects to admin page with 'logs' tab.
     *
     * @since 1.0
     */
    public static function handle_add_commit() {

        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'netpeak-logger'));
        }

        $commit_message = wp_kses_post($_POST['commit_message'] ?? '');
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
            'created_at' => current_time('mysql'),
        ];

        $wpdb->insert($table_name, $data);

        wp_redirect(admin_url('admin.php?page=netpeak-logs&tab=logs'));
        exit;
    }
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

    
    public static function handle_edit_commit() {
        if (!isset($_POST['id']) || !isset($_POST['message'])) {
            wp_die(__('Invalid request.', 'netpeak-logger'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
        $id = intval($_POST['id']);
        $message = wp_kses_post($_POST['message']);

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


    public static function switch_settings_tab() {
        $settings_tab = isset($_POST['settings']) ? sanitize_text_field($_POST['settings']) : '';

        if ($settings_tab == '') {
            include NETPEAK_LOGGER_COMPONENTS_ADMIN . 'settings/intro.php';
        }
        elseif ($settings_tab == 'loggers') {
            include NETPEAK_LOGGER_COMPONENTS_ADMIN . 'settings/loggers.php';
        } 
        elseif ($settings_tab == 'telegram') {
            include NETPEAK_LOGGER_COMPONENTS_ADMIN . 'settings/telegram.php';
        } 
        exit;
    }
    public static function handle_settings_form_submit() {

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'netpeak-logger')]);
        }
        $settings_tab = isset($_POST['settings']) ? trim(sanitize_text_field($_POST['settings'])) : '';
    
        if ($settings_tab === 'loggers') {
            update_option('netpeak_post_logger_enabled', isset($_POST['netpeak_post_logger_enabled']) ? 1 : 0);
            update_option('netpeak_plugin_logger_enabled', isset($_POST['netpeak_plugin_logger_enabled']) ? 1 : 0);
            update_option('netpeak_user_logger_enabled', isset($_POST['netpeak_user_logger_enabled']) ? 1 : 0);
            update_option('netpeak_comment_logger_enabled', isset($_POST['netpeak_comment_logger_enabled']) ? 1 : 0);
            update_option('netpeak_email_logger_enabled', isset($_POST['netpeak_email_logger_enabled']) ? 1 : 0);
        } 
        elseif ($settings_tab === 'telegram') {
            update_option('netpeak_daily_report_enabled', isset($_POST['netpeak_daily_report_enabled']) ? 1 : 0);
            update_option('netpeak_check_error_log', isset($_POST['netpeak_check_error_log']) ? 1 : 0);
            update_option('netpeak_telegram_bot_token', $_POST['netpeak_telegram_bot_token']);
        } 
        else {
            wp_send_json_error(['message' => __('Invalid settings tab.', 'netpeak-logger')]);
        }
    
        wp_send_json_success(['message' => __('Settings saved successfully.', 'netpeak-logger')]);
        exit;
    }

    public static function handle_bulk_edit_logs() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'netpeak-logger')]);
        }
    
        $logs = !empty($_POST['logs']) ? json_decode(stripslashes($_POST['logs']), true) : [];
        $action = sanitize_text_field($_POST['bulk_action']);
    
        if (empty($logs) || empty($action)) {
            wp_send_json_error(['message' => __('Invalid request data.', 'netpeak-logger')]);
        }
    
        global $wpdb;
    
        foreach ($logs as $log) {
            $log_id = intval($log['id']);
            $log_type = sanitize_text_field($log['type']);
    
            $table_name = $log_type === 'logs' ? $wpdb->prefix . 'netpeak_logs' :
                        ($log_type === 'email' ? $wpdb->prefix . 'netpeak_email_logs' : '');
    
            if (!$table_name) {
                continue; 
            }
    
            switch ($action) {
                case 'archive':
                    $wpdb->update(
                        $table_name,
                        ['is_archive' => 1],
                        ['id' => $log_id],
                        ['%d'],
                        ['%d']
                    );
                    break;
    
                case 'unarchive':
                    $wpdb->update(
                        $table_name,
                        ['is_archive' => 0],
                        ['id' => $log_id],
                        ['%d'],
                        ['%d']
                    );
                    break;
    
                case 'delete':
                    $wpdb->delete($table_name, ['id' => $log_id], ['%d']);
                    break;
    
                default:
                    wp_send_json_error(['message' => __('Invalid action.', 'netpeak-logger')]);
            }
        }
    
        wp_send_json_success(['message' => __('Bulk action completed successfully.', 'netpeak-logger')]);
    }

}

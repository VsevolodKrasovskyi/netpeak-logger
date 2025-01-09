<?php
namespace NetpeakLogger;

class AjaxHandler {

    public static function add_commit() {

        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'netpeak-logger'));
        }

        // Проверка на пустое сообщение
        $commit_message = sanitize_text_field($_POST['commit_message'] ?? '');
        if (empty($commit_message)) {
            wp_die(__('Commit message cannot be empty', 'netpeak-logger'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';

        // Сохранение коммита
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

        // Выполняем редирект
        wp_redirect(admin_url('admin.php?page=netpeak-logs&tab=logs'));
        exit;
    }
}

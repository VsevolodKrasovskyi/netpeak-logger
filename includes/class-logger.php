<?php
namespace NetpeakLogger;
use NetpeakLogger\Admin;

class Logger {

    public static function init() {
        $hooks = [
            'save_post' => 3,
            'before_delete_post' => 1,
            'publish_post' => 1,
            'unpublish_post' => 1,
            'activated_plugin' => 1,
            'deactivated_plugin' => 1,
            'upgrader_process_complete' => 2,
            'user_register' => 1,
            'profile_update' => 2,
            'delete_user' => 1,
            'wp_insert_comment' => 2,
            'edit_comment' => 1,
            'trash_comment' => 1,
        ];

        foreach ($hooks as $hook => $args) {
            add_action($hook, [self::class, 'log_event'], 20, $args);
        }
    }

    public static function log_event($arg1 = null, $arg2 = null, $arg3 = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
        $user = wp_get_current_user();

        $action = current_filter();
        $message = self::generate_message($action, $arg1, $arg2, $arg3);

        if ($message) {
            self::insert_log($table_name, $user->user_login, $action, $message);
        }
    }

    private static function generate_message($action, $arg1, $arg2, $arg3) {
        $messages = [
            'save_post' => fn($arg1, $arg2, $arg3) => sprintf('%s post: "%s"', $arg3 ? 'Updated' : 'Created', $arg2->post_title),
            'before_delete_post' => fn($arg1) => sprintf('Deleted post: "%s"', get_post($arg1)->post_title),
            'activated_plugin' => fn($arg1) => self::get_plugin_message($arg1, 'Activated'),
            'deactivated_plugin' => fn($arg1) => self::get_plugin_message($arg1, 'Deactivated'),
            'update_postmeta' => fn($arg1, $arg2, $arg3) => sprintf('Updated metadata for post ID: "%d" with key: "%s"', $arg2, $arg3),
            'add_postmeta' => fn($arg1, $arg2, $arg3) => sprintf('Added metadata for post ID: "%d" with key: "%s"', $arg2, $arg3),
            'delete_postmeta' => fn($arg1, $arg2) => sprintf('Deleted metadata for post ID: "%d"', $arg2),
            'user_register' => fn($arg1) => sprintf('Created user: "%s"', get_user_by('id', $arg1)->user_login),
            'profile_update' => fn($arg1) => sprintf('Edited user: "%s"', get_user_by('id', $arg1)->user_login),
            'delete_user' => fn($arg1) => sprintf('Deleted user with ID: %d', $arg1),
            'wp_insert_comment' => fn($arg1, $arg2) => sprintf('Added comment: "%s"', $arg2->comment_content),
            'edit_comment' => fn($arg1) => sprintf('Edited comment: "%s"', get_comment($arg1)->comment_content),
            'trash_comment' => fn($arg1) => sprintf('Deleted comment with ID: %d', $arg1),
        ];

        return $messages[$action]($arg1, $arg2, $arg3) ?? null;
    }

    private static function insert_log($table_name, $user_login, $action, $message) {
        global $wpdb;

        $wpdb->insert($table_name, [
            'user_login' => $user_login,
            'action' => Admin::format_action($action),
            'log_type' => 'automatic',
            'message' => $message,
            'date' => current_time('mysql'),
        ]);
    }

    private static function get_plugin_message($plugin, $action) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = $plugin_data['Name'] ?? $plugin;
        return sprintf('%s plugin: "%s"', $action, $plugin_name);
    }

    public static function log_upgrades($upgrader_object, $options) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
        $user = wp_get_current_user();

        if (isset($options['type']) && isset($options['action'])) {
            $type = $options['type'];
            $items = $options[$type . 's'] ?? [];

            foreach ($items as $item) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $item);
                $plugin_name = $plugin_data['Name'] ?? $item;

                self::insert_log($table_name, $user->user_login, 'update_' . $type, sprintf('Updated %s: "%s"', ucfirst($type), $plugin_name));
            }
        }
    }
}

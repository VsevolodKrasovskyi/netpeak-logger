<?php
/**
 * Main Logger class for Netpeak Logger
 *
 * @package NetpeakLogger
 * @since 1.0
 */

namespace NetpeakLogger;
use NetpeakLogger\Admin;

abstract class Logger {

    const ACTIONS = [
        'save_post' => 'Save Post',
        'before_delete_post' => 'Delete Post',
        'publish_post' => 'Publish Post',
        'post_updated' => 'Post updated',
        'transition_post_status' => 'Post Status Change',
        //Plugins
        'activated_plugin' => 'Plugin Activated',
        'deactivated_plugin' => 'Plugin Deactivated',
        //Metadata
        'update_postmeta' => 'Update Metadata',
        'add_postmeta' => 'Add Metadata',
        'delete_postmeta' => 'Delete Metadata',
        //Users
        'user_register' => 'User Registered',
        'profile_update' => 'Profile Updated',
        'delete_user' => 'User Deleted',
        //Comments
        'wp_insert_comment' => 'Add Comment',
        'edit_comment' => 'Edit Comment',
        'trash_comment' => 'Trash Comment'
    ];

    /**
     * Insert a log entry into the database
     *
     * @param string $table_name Database table name
     * @param string $user_login Username of the user who triggered the event
     * @param string $action The WordPress action being logged
     * @param string $message Human-readable description of the event
     * @return void
     */
    protected static function insert_log($user_login, $action, $message) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log("Netpeak Logger Error: Table `$table_name` does not exist.");
            return;
        }

        $result = $wpdb->insert($table_name, [
            'user_login' => $user_login,
            'action' => $action,
            'log_type' => 'automatic',
            'message' => $message,
            'date' => current_time('mysql'),
        ]);

        if ($result === false) {
            error_log('Database error: ' . $wpdb->last_error);
        }
    }

    /**
     * Log a WordPress event
     *
     * @param mixed $arg1 First argument from the hook
     * @param mixed $arg2 Second argument from the hook
     * @param mixed $arg3 Third argument from the hook
     * @return void
     */
    public static function log_event($arg1 = null, $arg2 = null, $arg3 = null) {
        $user = wp_get_current_user();
        
        $current_filter = current_filter();
        $action = array_key_exists($current_filter, self::ACTIONS) ? $current_filter : $current_filter;
    
        $message = static::generate_message($current_filter, $arg1, $arg2, $arg3);
    
        if ($message !== null) {
            static::insert_log($user->user_login, $action, $message);
        }
    }

    /**
     * Initialize the logger
     *
     * @return void
     */
    abstract public static function init();

    /**
     * Generate a message for the logged event
     *
     * @param string $action The WordPress action being logged
     * @param mixed $arg1 First argument from the hook
     * @param mixed $arg2 Second argument from the hook
     * @param mixed $arg3 Third argument from the hook
     * @return string|null Generated message or null if no message could be generated
     */
    abstract protected static function generate_message($action, $arg1, $arg2, $arg3);
}

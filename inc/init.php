<?php
/**
 * Initialization class for Netpeak Logger
 *
 * This class handles database table creation, role management,
 * and other initialization tasks for the plugin.
 *
 * @package NetpeakLogger
 * @subpackage Creator
 * @since 1.0
 */


namespace NetpeakLogger\Creator;

class Init {
    /**
     * Creates the necessary database tables for the plugin
     *
     * @since 1.0
     * @global wpdb $wpdb WordPress database abstraction object
     * @return void
     */
    public static function netpeak_create_tables_logs() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'netpeak_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_login VARCHAR(100) NOT NULL,
            action VARCHAR(255) NOT NULL,
            log_type ENUM('automatic', 'commit') DEFAULT 'automatic' NOT NULL,
            message TEXT DEFAULT NULL,
            date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function netpeak_delete_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'netpeak_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }


    /**
     * Adds a developer role with administrator capabilities
     *
     * This role is used for advanced plugin functionality access
     *
     * @since 1.0
     * @return void
     */
    public static function netpeak_add_developer_role() {
        $admin_capabilities = get_role('administrator')->capabilities;
        add_role(
            'developer',
            'Developer',
            $admin_capabilities
        );
    }
    public static function netpeak_register_setting() {
        register_setting('netpeak-logger-settings', 'netpeak_post_logger_enabled');
        register_setting('netpeak-logger-settings', 'netpeak_plugin_logger_enabled');
        register_setting('netpeak-logger-settings', 'netpeak_user_logger_enabled');
        register_setting('netpeak-logger-settings', 'netpeak_comment_logger_enabled');
        register_setting('netpeak-logger-settings', 'netpeak_email_logger_enabled');
        register_setting('netpeak-logger-settings', 'netpeak_telegram_bot_token');
    }

    public static function netpeak_delete_settings() {
        delete_option('netpeak_post_logger_enabled');
        delete_option('netpeak_plugin_logger_enabled');
        delete_option('netpeak_user_logger_enabled');
        delete_option('netpeak_comment_logger_enabled');
    }

    /**
     * Initializes all necessary hooks for the plugin
     *
     * @since 1.0
     * @return void
     */
    public static function hooks() {
        self::netpeak_add_developer_role();
        self::netpeak_register_setting();
    }

    public static function netpeak_install() {
        self::netpeak_create_tables_logs();
    }

    public static function netpeak_uninstall() {
        self::netpeak_delete_tables();
        self::netpeak_delete_settings();
    }
}
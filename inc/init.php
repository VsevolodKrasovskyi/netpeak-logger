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
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY netpeak_idx_user_login (user_login),  
            KEY netpeak_idx_action (action),          
            KEY netpeak_idx_log_type (log_type)      
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log("Creating table with query: " . $sql);
    }

    public static function create_email_logs_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_email_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            sender VARCHAR(255) DEFAULT NULL,
            recipient VARCHAR(255) DEFAULT NULL,
            subject VARCHAR(255) DEFAULT NULL,
            message TEXT DEFAULT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY netpeak_idx_status (status),
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log("Creating table with query: " . $sql);

    }

    public static function netpeak_delete_tables() {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'netpeak_logs',
            $wpdb->prefix . 'netpeak_email_logs'
        ];

        foreach ($tables as $table_name) {
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
            error_log("Deleted table: " . $table_name);
        }
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
        //Logger settings
        register_setting('netpeak-logger-settings-loggers', 'netpeak_post_logger_enabled');
        register_setting('netpeak-logger-settings-loggers', 'netpeak_plugin_logger_enabled');
        register_setting('netpeak-logger-settings-loggers', 'netpeak_user_logger_enabled');
        register_setting('netpeak-logger-settings-loggers', 'netpeak_comment_logger_enabled');
        register_setting('netpeak-logger-settings-loggers', 'netpeak_email_logger_enabled');
        //Telegram API
        register_setting('netpeak-logger-settings-telegram', 'netpeak_daily_report_enabled');
        register_setting('netpeak-logger-settings-telegram', 'netpeak_check_error_log');
        register_setting('netpeak-logger-settings-telegram', 'netpeak_telegram_bot_token');
    }

    public static function netpeak_delete_settings() {
        $options = [
            'netpeak_post_logger_enabled',
            'netpeak_plugin_logger_enabled',
            'netpeak_user_logger_enabled',
            'netpeak_comment_logger_enabled',
            'netpeak_email_logger_enabled',
            'netpeak_telegram_bot_token',
            'netpeak_daily_report_enabled',
            'netpeak_check_error_log',
        ];
        foreach ($options as $option) {
            delete_option($option);
        }
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
        self::create_email_logs_table();

    }

    public static function netpeak_uninstall() {
        // self::netpeak_delete_tables();
        self::netpeak_delete_settings();
    }
}
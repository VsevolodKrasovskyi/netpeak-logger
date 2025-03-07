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
            is_archive TINYINT(1) DEFAULT 0,
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
            is_archive TINYINT(1) DEFAULT 0,
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
    public static function netpeak_add_role_and_capabilities() {
        $admin = get_role('administrator');

        if($admin) {
            $admin->add_cap('netpeak_admin');
            $admin->add_cap('netpeak_pm');
        }

        if (!get_role('developer')) {
            add_role('developer', 'Netpeak DEV', array_merge($admin->capabilities, [
                'netpeak_admin' => true,
                'netpeak_pm'    => true
            ]));
        }

        if (!get_role('netpeak_pm')) {
            add_role('netpeak_pm', 'Netpeak PM', [
                'read'          => true, 
                'netpeak_pm'    => true
            ]);
        }
        

    }


    /**
     * Generate a token for autologin user
     * 
     * @param string $email User email
     * 
     * @return string|false URL for autologin or false if user not found
     */
    public static function generate_token_autologin($email) {
        $user = get_user_by('email', $email);
        
        if (!$user) {
            return false; 
        }
    
        $token = wp_generate_password(32, false);
    
        update_user_meta($user->ID, 'auto_login_token', $token);
        update_user_meta($user->ID, 'auto_login_token_expiry', time() + (30 * 60));
    
        return add_query_arg([
            'action' => 'auto_login',
            'token'  => $token,
            'email'  => urlencode($email),
        ], admin_url('admin.php?page=netpeak-logs&tab=logs'));
    }
    /**
     * Handles automatic user login via a tokenized login link.
     *
     * This function checks for a specific 'auto_login' action in the $_GET parameters
     * and attempts to log in the user if a valid email and token are provided.
     * It verifies the token's validity and expiration time, and authenticates the user
     * by setting the auth cookie and redirecting to the specified admin page.
     *
     * @throws WP_Error If the login link is invalid, expired, or the user is not found.
     */
    public static function auto_login() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'auto_login') {
            return;
        }
    
        $email = isset($_GET['email']) ? urldecode($_GET['email']) : '';
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
    
        if (!$email || !$token) {
            wp_die('Invalid login link.');
        }
    
        $user = get_user_by('email', $email);
        if (!$user) {
            wp_die('User not found.');
        }
    
        $saved_token = get_user_meta($user->ID, 'auto_login_token', true);
        $expiry_time = get_user_meta($user->ID, 'auto_login_token_expiry', true);
    
        if ($saved_token !== $token || time() > $expiry_time) {
            wp_die('Expired or invalid login link.');
        }
    
        delete_user_meta($user->ID, 'auto_login_token');
        delete_user_meta($user->ID, 'auto_login_token_expiry');
    
        wp_set_auth_cookie($user->ID, true);
        wp_redirect(admin_url('admin.php?page=netpeak-logs&tab=logs'));
        exit;
    }
    
    
    public static function netpeak_register_setting() {
        //Logger settings
        register_setting('netpeak-logger-settings-loggers', 'netpeak_post_logger_enabled');
        register_setting('netpeak-logger-settings-loggers', 'netpeak_plugin_logger_enabled');
        register_setting('netpeak-logger-settings-loggers', 'netpeak_user_logger_enabled');
        register_setting('netpeak-logger-settings-loggers', 'netpeak_comment_logger_enabled');
        register_setting('netpeak-logger-settings-loggers', 'netpeak_email_logger_enabled');
        //Telegram API
        register_setting('netpeak-logger-settings-telegram', 'netpeak_daily_telegram_report_enabled');
        register_setting('netpeak-logger-settings-telegram', 'netpeak_check_error_log');
        register_setting('netpeak-logger-settings-telegram', 'netpeak_telegram_bot_token');
        //Report settings
        register_setting('netpeak-logger-report-settings', 'netpeak_daily_email_report_enabled');
        register_setting('netpeak-logger-report-settings', 'netpeak_commit_report_enabled');
        register_setting('netpeak-logger-report-settings', 'netpeak_report_emails', [
            'default' => [],
        ]);
    }

    public static function register_cron_event()
    {
        if(!wp_next_scheduled('netpeak_daily_cron'))
        {
            $timezone_offset = get_option('gmt_offset') * HOUR_IN_SECONDS;
            $timestamp = strtotime('23:00:00') - $timezone_offset;
            wp_schedule_event($timestamp, 'daily', 'netpeak_daily_cron'); 
        }
    } 

    public static function netpeak_delete_settings() {
        $options = [
            'netpeak_post_logger_enabled',
            'netpeak_plugin_logger_enabled',
            'netpeak_user_logger_enabled',
            'netpeak_comment_logger_enabled',
            'netpeak_email_logger_enabled',
            'netpeak_telegram_bot_token',
            'netpeak_daily_telegram_report_enabled',
            'netpeak_check_error_log',
            'netpeak_daily_email_report_enabled',
            'netpeak_commit_report_enabled',
            'netpeak_report_emails'
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
        self::netpeak_register_setting();
    }

    public static function netpeak_install() {
        self::netpeak_create_tables_logs();
        self::create_email_logs_table();
        self::netpeak_add_role_and_capabilities();
    }

    public static function netpeak_uninstall() {
        // self::netpeak_delete_tables();
        self::netpeak_delete_settings();
    }
}
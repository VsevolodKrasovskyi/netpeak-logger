<?php

namespace NetpeakLogger\Creator;

class Init{
    public static function netpeak_create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'netpeak_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_login VARCHAR(60) NOT NULL,
            action VARCHAR(20) NOT NULL,
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
}
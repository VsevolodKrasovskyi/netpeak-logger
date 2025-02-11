<?php
/**
 * Logger Manager class for initializing all loggers
 *
 * @package NetpeakLogger
 * @since 2.0
 */

namespace NetpeakLogger;

use NetpeakLogger\Loggers\PostLogger;
use NetpeakLogger\Loggers\PluginLogger;
use NetpeakLogger\Loggers\UserLogger;
use NetpeakLogger\Loggers\CommentLogger;
use NetpeakLogger\Loggers\EmailLogger;

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}


class LoggerManager {
    /**
     * Initialize all loggers
     *
     * @return void
     */
    public static function init() {
        if (get_option('netpeak_post_logger_enabled', 0)) {
            PostLogger::init();
        }
        if (get_option('netpeak_plugin_logger_enabled', 0)) {
            PluginLogger::init();
        }
        if (get_option('netpeak_user_logger_enabled', 0)) {
            UserLogger::init();
        }
        if (get_option('netpeak_comment_logger_enabled', 0)) {
            CommentLogger::init();
        }
        if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            if (get_option('netpeak_email_logger_enabled', 0)) {
                EmailLogger::init();
            }
        }
    }
    
}

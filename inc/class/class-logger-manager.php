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

class LoggerManager {
    /**
     * Initialize all loggers
     *
     * @return void
     */
    public static function init() {
        PostLogger::init();
        PluginLogger::init();
        UserLogger::init();
        CommentLogger::init();
    }
}

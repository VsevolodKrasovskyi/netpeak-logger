<?php
/**
 * Plugin Logger class for handling plugin-related events
 *
 * @package NetpeakLogger
 * @since 2.0
 */

namespace NetpeakLogger\Loggers;

use NetpeakLogger\Logger;

class PluginLogger extends Logger {
    /**
     * Initialize the plugin logger
     *
     * @return void
     */
    public static function init() {
        $hooks = [
            'activated_plugin' => 1,
            'deactivated_plugin' => 1,
            'deleted_plugin' => 2,
        ];

        foreach ($hooks as $hook => $args) {
            add_action($hook, [self::class, 'log_event'], 20, $args);
        }

        // Hook for plugin installation
        add_action('upgrader_process_complete', [self::class, 'handle_plugin_installation'], 10, 2);
        // Hook for plugin upgrade
        add_action('upgrader_process_complete', [self::class, 'handle_plugin_update'], 10, 2);
    }

    /**
     * Generate a message for plugin-related events
     *
     * @param string $action The WordPress action being logged
     * @param mixed $arg1 First argument from the hook
     * @param mixed $arg2 Second argument from the hook
     * @param mixed $arg3 Third argument from the hook
     * @return string|null Generated message or null if no message could be generated
     */
    protected static function generate_message($action, $arg1, $arg2, $arg3) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        switch ($action) {
            case 'activated_plugin':
                return self::handle_activated_plugin($arg1);

            case 'deactivated_plugin':
                return self::handle_deactivated_plugin($arg1);

            case 'deleted_plugin':
                return self::handle_deleted_plugin($arg1);
        }

        return null;
    }

    /**
     * Handle plugin activation event
     *
     * @param string $plugin Path to the plugin file
     * @return string|null
     */
    private static function handle_activated_plugin($plugin) {
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;

        if (file_exists($plugin_path)) {
            $plugin_data = get_plugin_data($plugin_path);
            return sprintf('Activated plugin: "%s"', $plugin_data['Name'] ?? basename($plugin));
        }

        return sprintf('Activated plugin: "%s"', basename($plugin, '.php'));
    }

    /**
     * Handle plugin deactivation event
     *
     * @param string $plugin Path to the plugin file
     * @return string|null
     */
    private static function handle_deactivated_plugin($plugin) {
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;

        if (file_exists($plugin_path)) {
            $plugin_data = get_plugin_data($plugin_path);
            return sprintf('Deactivated plugin: "%s"', $plugin_data['Name'] ?? basename($plugin));
        }

        return sprintf('Deactivated plugin: "%s"', basename($plugin, '.php'));
    }

    /**
     * Handle plugin deletion event
     *
     * @param string $plugin Path to the plugin file
     * @return string|null
     */
    private static function handle_deleted_plugin($plugin) {
        return sprintf('Deleted plugin: "%s"', basename($plugin, '.php'));
    }

    /**
     * Handle plugin installation event
     *
     * @param \WP_Upgrader $upgrader WP Upgrader instance.
     * @param array $data Information about the installation process.
     * @return void
     */
    public static function handle_plugin_installation($upgrader, $data) {
        global $wpdb;
    
        // Let's make sure that the action is related to installing plugins
        if ($data['type'] === 'plugin' && $data['action'] === 'install') {
            if (isset($upgrader->result['destination_name'])) {
                $plugin_slug = $upgrader->result['destination_name'];
                $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
    
                // Check if the installed plugin exists
                if (file_exists($plugin_path)) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    
                    // Retrieving plugin data
                    $plugin_data = get_plugin_data($plugin_path . '/' . $plugin_slug . '.php');
                    $plugin_name = $plugin_data['Name'] ?? $plugin_slug;
    
                    // Generating a message for the log
                    $message = sprintf('Installed plugin: "%s"', $plugin_name);
    
                    // Inserting the log
                    static::insert_log(wp_get_current_user()->user_login, 'install_plugin', $message);
                } else {
                    error_log('Plugin installation path not found: ' . $plugin_slug);
                }
            } else {
                error_log('Upgrader result does not contain destination_name.');
            }
        }
    }

    public static function handle_plugin_update($upgrader, $data) {
        global $wpdb;
    
        // Checking that this is a plugin update
        if ($data['type'] === 'plugin' && $data['action'] === 'update') {
            // We get the list of updated plugins
            if (!empty($data['plugins'])) {
                foreach ($data['plugins'] as $plugin) {
                    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;
    
                    if (file_exists($plugin_path)) {
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    
                        // Retrieving plugin data
                        $plugin_data = get_plugin_data($plugin_path);
                        $plugin_name = $plugin_data['Name'] ?? basename($plugin);
    
                        // Generating a message
                        $message = sprintf('Updated plugin: "%s"', $plugin_name);
    
                        // Inserting the log
                        static::insert_log(wp_get_current_user()->user_login, 'update_plugin', $message);
                    }
                }
            }
        }
    }
    
}

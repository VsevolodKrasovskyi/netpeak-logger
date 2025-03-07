<?php
/**
 * User Logger class for handling user-related events
 *
 * @package NetpeakLogger
 * @since 2.0
 */

namespace NetpeakLogger\Loggers;

use NetpeakLogger\Logger;

class UserLogger extends Logger {
    /**
     * Initialize the user logger
     *
     * @return void
     */
    public static function init() {
        $hooks = [
            'user_register' => 1,
            'profile_update' => 2,
            'delete_user' => 1,
        ];

        foreach ($hooks as $hook => $args) {
            add_action($hook, [self::class, 'log_event'], 20, $args);
        }
    }

    /**
     * Generate a message for user-related events
     *
     * @param string $action The WordPress action being logged
     * @param mixed $arg1 First argument from the hook
     * @param mixed $arg2 Second argument from the hook
     * @param mixed $arg3 Third argument from the hook
     * @return string|null Generated message or null if no message could be generated
     */
    protected static function generate_message($action, $arg1, $arg2, $arg3) {
        switch ($action) {
            case 'user_register':
                return self::handle_user_register($arg1);

            case 'profile_update':
                return self::handle_profile_update($arg1, $arg2);

            case 'delete_user':
                return self::handle_delete_user($arg1);
        }

        return null;
    }

    /**
     * Handle user registration event
     *
     * @param int $user_id User ID
     * @return string|null
     */
    private static function handle_user_register($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            return sprintf('New user registered: "%s"', $user->user_login);
        }

        return null;
    }

    /**
     * Handle user profile update event
     *
     * @param int $user_id User ID
     * @param WP_User $old_user_data Previous user data
     * @return string|null
     */
    private static function handle_profile_update($user_id, $old_user_data) {
        $user = get_userdata($user_id);
        
        if ($user) {
            $changes = [];
            $fields = ['user_login', 'user_email', 'display_name', 'first_name', 'last_name', 'nickname'];

            foreach ($fields as $field) {
                if ($old_user_data->$field !== $user->$field) {
                    $changes[] = sprintf('%s changed from "%s" to "%s"', $field, $old_user_data->$field, $user->$field);
                }
            }

            if (!empty($changes)) {
                return sprintf('User profile updated: "%s" (%s)', $user->user_login, implode(', ', $changes));
            }
        }

        return null;
    }


    /**
     * Handle user deletion event
     *
     * @param int $user_id User ID
     * @return string|null
     */
    private static function handle_delete_user($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            return sprintf('User deleted: "%s"', $user->user_login);
        }

        return sprintf('User with ID %d deleted.', $user_id);
    }
}

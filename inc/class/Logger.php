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
        'update_plugin' => 'Update plugin',
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

    public static function hooks()
    {
        $hooks = [
            'netpeak_daily_cron' => 'daily_send_email_report'
        ];
        foreach ($hooks as $hook => $callback)
        {
            add_action($hook, [self::class, $callback]);
        }
        
    }

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

        $user_login = !empty($user_login) ? $user_login : 'wordpress@wordpress.org';

        $result = $wpdb->insert($table_name, [
            'user_login' => $user_login,
            'action' => $action,
            'log_type' => 'automatic',
            'message' => $message,
            'created_at' => current_time('mysql'),
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
            static::insert_log($user->user_email, $action, $message);
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

    /**
     * Send a daily email report of logs for the current day.
     *
     * This function checks if the daily email report is enabled in the options.
     * If enabled, it retrieves logs from the `netpeak_logs` table for the current day
     * and sends an email report. The report includes details such as ID, User, Action,
     * Message, and Created timestamp for each log entry. The email is sent in HTML format
     * to the specified recipient.
     *
     * @return void
     */
    public static function daily_send_email_report() {
        if (!get_option('netpeak_daily_email_report_enabled', 0)) {
            return;
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
    
        $results = $wpdb->get_results(
            "SELECT * 
            FROM {$table_name}
            WHERE DATE(created_at) = CURDATE()"
        );    
        if (empty($results)) {
            return;
        }
    
        ob_start();
        ?>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ccc; padding: 8px; vertical-align: top; }
                th { background-color: #f8f8f8; }
                pre { margin: 0; font-family: Menlo, Monaco, Consolas, "Courier New", monospace; }
            </style>
        </head>
        <body>
            <h2>Logs for today (<?php echo esc_html(date('Y-m-d')); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Message</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td><?php echo esc_html($row->user_login); ?></td>
                            <td><?php echo esc_html(self::ACTIONS[$row->action] ?? $row->action); ?></td>
                            <td><pre><?php echo wp_kses_post($row->message); ?></pre></td>
                            <td><?php echo esc_html($row->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>Generated by Netpeak Logger</p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=netpeak-logs&tab=logs')) ?>"><p>View full logs</p></a>
        </body>
        </html>
        <?php
        $message = ob_get_clean();
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $subject = 'Netpeak Logger — Report for ' . date('Y-m-d') . ' / ' . $_SERVER['HTTP_HOST'];
        $emails = get_option('netpeak_report_emails',[]);

        if(empty($emails)){
            return;
        }
        foreach ($emails as $email) {
            wp_mail($email, $subject, $message, $headers);
        }

    }
    public static function send_commit_report($data)
    {
        if (!get_option('netpeak_commit_report_enabled', 0)) {
            return;
        }

        $emails = get_option('netpeak_report_emails', []);
        if (empty($emails)) {
            return;
        }
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $subject = 'New Commit Report' . ' / ' . $_SERVER['HTTP_HOST'];
        $message = sprintf(
            '<h1>New Commit Report</h1>
            <p><strong>User:</strong> %s</p>
            <p><strong>Action:</strong> %s</p>
            <p><strong>Message:</strong></p>
            <p>%s</p>
            <p><strong>Created:</strong> %s</p>',
            esc_html($data['user_login']),
            esc_html($data['action']),
            wp_kses_post($data['message']),
            esc_html($data['created_at'])
        );

        foreach ($emails as $email) {
            if (is_email($email)) {
                wp_mail($email, $subject, $message, $headers);
            }
        }

    }
    

}

<?php

namespace NetpeakLogger\Loggers;

class EmailLogger {
    private $table_name;

    /**
     * Hooks into:
     * - `init`: Checks and creates the logs table.
     * - `wpcf7_mail_sent`: Logs successfully sent emails from CF7.
     * - `wpcf7_mail_failed`: Logs failed emails from CF7.
     * - `admin_menu`: Adds a menu page for logs.
     * - `admin_notices`: Checks for daily errors.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'netpeak_email_logs';

        add_action('init', [$this, 'check_and_create_table']);
        add_action('wpcf7_mail_sent', [$this, 'log_cf7_success']);
        add_action('wpcf7_mail_failed', [$this, 'log_cf7_failed']);
        add_action('after_setup_theme', [$this, 'register_cron_event']);

        //Cron
        add_action('netpeak_email_checker_daily', [$this, 'send_daily_report']);
    }

    public function check_and_create_table() {
        $this->create_email_logs_table();
    }

    private function create_email_logs_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            sender VARCHAR(255) DEFAULT NULL,
            recipient VARCHAR(255) DEFAULT NULL,
            subject VARCHAR(255) DEFAULT NULL,
            message TEXT DEFAULT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log("Creating table with query: " . $sql);

    }

    public function log_cf7_success($contact_form) {
        $submission = \WPCF7_Submission::get_instance();
    
        if ($submission) {
            $data = $submission->get_posted_data();
            $mail = $contact_form->prop('mail');
    
            $recipients = $mail['recipient'] ?? 'No Recipient'; 
            $subject = $data['your-subject'] ?? 'No Subject';
            $message = $data['your-message'] ?? 'No Message';
            $status = 'success';
    
            $this->log_email(
                $data['your-email'] ?? 'no-reply@yourdomain.com',
                $recipients,
                $subject,
                $message,
                $status,
            );
        }
    }

    public function log_cf7_failed($contact_form) {
        $submission = \WPCF7_Submission::get_instance();
    
        if ($submission) {
            $data = $submission->get_posted_data();
            $mail = $contact_form->prop('mail');
    
            $recipients = $mail['recipient'] ?? 'No Recipient';
            $subject = $data['your-subject'] ?? 'No Subject';
            $message = $data['your-message'] ?? 'No Message';
            $status = 'failed';
    
            $this->log_email(
                $data['your-email'] ?? 'no-reply@yourdomain.com',
                $recipients,
                $subject,
                $message,
                $status
            );
        }
        $this->check_errors($data);
    }
    
    private function log_email($sender, $recipient, $subject, $message, $status) {
        global $wpdb;
        $wpdb->insert(
            $this->table_name,
            [
                'sender'   => $sender,
                'recipient' => $recipient,
                'subject'   => $subject,
                'message'   => $message,
                'status'    => $status,
                'created_at' => current_time('mysql'),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );
    }  

    public function check_errors($submission_data) {
        if (!get_option('netpeak_check_error_log', 0)) {
            return;
        }

        global $wpdb;
        $last_error = $wpdb->get_row(
            "SELECT * FROM {$this->table_name} WHERE status = 'Failed' ORDER BY created_at DESC LIMIT 1"
        );
    
        if ($last_error) {
            $message = "🚨 <b>Email Error Detected!</b> 🚨\n\n" .
                    "Sender: <b>{$submission_data['your-email']}</b>\n" .
                    "Subject: <b>{$submission_data['your-subject']}</b>\n" .
                    "Message: <b>{$submission_data['your-message']}</b>\n\n" .
                    "Date: <b>{$last_error->created_at}</b>\n" .
                    "Status: <b>Failed</b>\n\n" .
                    "Please check the logs for more details.";
    
            $this->telegram_alert($message);
        }
    }

    public function send_daily_report()
    {
        if(!get_option( 'netpeak_daily_report_enabled', 0)){
            return;
        }
        global $wpdb;

        $success_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s AND DATE(created_at) = CURDATE()",
                'Success'
            )
        );

        $failed_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s AND DATE(created_at) = CURDATE()",
                'Failed'
            )
        );
        $total_count = $success_count + $failed_count;

        $message = "📊 <b>Daily Email Report</b> 📊\n\n" .
                "Total emails: <b>{$total_count}</b>\n" .
                "Successful: <b>{$success_count}</b>\n" .
                "Failed: <b>{$failed_count}</b>\n\n" .
                "Generated on: " . date('Y-m-d H:i:s');

        $this->telegram_alert($message);
    }   

    public function telegram_alert($message) {
        $bot_token = get_option('netpeak_telegram_bot_token');
        $url_get_updates = "https://api.telegram.org/bot{$bot_token}/getUpdates";
    
        $updates = file_get_contents($url_get_updates);
        $updates = json_decode($updates, true);
    
        if (!$updates) {
            die('Failed to fetch updates from Telegram API');
        }
    
        if (isset($updates['result']) && is_array($updates['result'])) {
            $unique_chat_ids = [];
            
            foreach ($updates['result'] as $update) {
                if (isset($update['message']['chat']['id'])) {
                    $unique_chat_ids[$update['message']['chat']['id']] = true;
                }
            }
    
            foreach (array_keys($unique_chat_ids) as $chat_id) {
                $url_send_message = "https://api.telegram.org/bot{$bot_token}/sendMessage";
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ];
    
                $options = [
                    'http' => [
                        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'POST',
                        'content' => http_build_query($data),
                    ],
                ];
    
                $context = stream_context_create($options);
                $result = file_get_contents($url_send_message, false, $context);
    
            }
    
            return true;
        }
    
        die('No valid chat IDs found');
    }
    
    

    /**
     * Register the cron event.
     */

    public function register_cron_event()
    {
        if (!wp_next_scheduled('netpeak_email_checker_hourly')) {
            wp_schedule_event(time(), 'hourly', 'netpeak_email_checker_hourly'); 
        }

        if(!wp_next_scheduled('netpeak_email_checker_daily'))
        {
            wp_schedule_event(time(), 'daily', 'netpeak_email_checker_daily'); 
        }
    }    
}
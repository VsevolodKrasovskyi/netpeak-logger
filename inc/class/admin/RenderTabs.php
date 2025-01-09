<?php
namespace NetpeakLogger\Render;
use NetpeakLogger\Render\AdminRenderer;
use NetpeakLogger\Render\RenderFilters;
use NetpeakLogger\Admin;


class RenderTabs extends AdminRenderer{
    public static function logs_tab()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';

        $logs = RenderFilters::get_filtered($table_name);
        RenderFilters::render_filters($table_name);

        echo '<table class="netpeak-logs-table">';
        echo '<thead><tr>';
        echo '<th>User</th>';
        echo '<th>Action</th>';
        echo '<th>Log Type</th>';
        echo '<th>Message</th>';
        echo '<th>Date</th>';
        echo '<th>Actions</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        echo '<div id="edit-form-container" class="netpeak-popup-container" style="display: none;"></div>';

        if (empty($logs)) {
            echo '<tr><td colspan="6">No logs found.</td></tr>';
        } else {
            foreach ($logs as $log) {
                echo '<tr>';
                echo '<td>' . AdminRenderer::render_user_column($log->user_login) . '</td>';
                echo '<td>' . esc_html(Admin::format_action($log->action)) . '</td>';
                echo '<td>' . esc_html(ucfirst($log->log_type)) . '</td>';
                echo '<td>' . AdminRenderer::render_collapsible_message($log->message) . '</td>';
                echo '<td>' . esc_html($log->date) . '</td>';
                echo '<td>' . AdminRenderer::render_actions($log) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }

    public static function commit_tab()
    {
        $current_user = wp_get_current_user();
        $avatar = get_avatar($current_user->ID, 80);
        $username = esc_html($current_user->display_name);

        $commit_types = apply_filters('netpeak_commit_types', [
            'update' => 'Update',
            'fix' => 'Fix',
            'feature' => 'Feature',
        ]);

        echo '
        <div class="netpeak-commit-container">
            <div class="netpeak-user-info">
                <div class="netpeak-avatar">' . $avatar . '</div>
                <div class="netpeak-username">' . $username . '</div>
            </div>
            <form method="POST" action="' . esc_url(admin_url('admin-post.php')) . '" class="netpeak-commit-form">
                <input type="hidden" name="action" value="netpeak_add_commit">
                ' . wp_nonce_field('netpeak_nonce', 'security', true, false) . '
    
                <label for="commit_message">Commit Message:</label>
                <textarea id="commit_message" name="commit_message" rows="5" placeholder="Write your commit message here..." required></textarea>

                <div style="display:flex; align-items: center;; flex-direction:column; width: 100%;">
                <label for="commit_type">Commit Type:</label>
                <select id="commit_type" name="commit_type">';
        foreach ($commit_types as $key => $label) {
            echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
        }
        echo '
                </select>
                </div>
                <button type="submit" class="netpeak-commit-button">Add Commit</button>
            </form>
        </div>';
    }

    public static function render_logs_email_page() {
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_email_logs';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $query = "SELECT * FROM {$table_name}";
        
        if ($status_filter) {
            $query .= $wpdb->prepare(" WHERE status = %s", $status_filter);
        }

        $query .= " ORDER BY created_at DESC";
        $logs = $wpdb->get_results($query);

        ?>
        <div class="wrap">
            <h1>Email Logs</h1>
        
            <form method="get">
                <input type="hidden" name="page" value="email-logs">
                <select name="status_filter">
                    <option value=""><?php esc_html_e('All Statuses', 'netpeak'); ?></option>
                    <option value="Success" <?php selected($status_filter, 'Success'); ?>><?php esc_html_e('Success', 'netpeak'); ?></option>
                    <option value="Failed" <?php selected($status_filter, 'Failed'); ?>><?php esc_html_e('Failed', 'netpeak'); ?></option>
                </select>
                <button type="submit" class="button"><?php esc_html_e('Filter', 'netpeak'); ?></button>
            </form>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sender</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)) : ?>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><?php echo esc_html($log->id); ?></td>
                                <td><?php echo esc_html($log->sender); ?></td>
                                <td><?php echo esc_html($log->recipient); ?></td>
                                <td><?php echo esc_html($log->subject); ?></td>
                                <td><?php echo esc_html($log->message); ?></td>
                                <td>
                                    <span class="<?php echo $log->status === 'Success' ? 'log-success' : 'log-failed'; ?>">
                                        <?php echo esc_html($log->status); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log->created_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7"><?php esc_html_e('No logs found.', 'netpeak'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <style>
            .log-success {
                color: green;
                font-weight: bold;
            }
            .log-failed {
                color: red;
                font-weight: bold;
            }
            .widefat.fixed.striped tbody tr:hover {
                background-color: #f1f1f1;
            }
        </style>
        <?php
    }

    public static function settings_tab() {
        ?>
        <h1><?php _e('Settings', 'netpeak-logger'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('netpeak-logger-settings');
            do_settings_sections('netpeak-logger-settings');
            ?>
            <table class="form-table">
                <tr>
                    <th><?php _e('Post Logger', 'netpeak-logger'); ?></th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" class="dependent-checkbox" name="netpeak_post_logger_enabled" value="1"
                                <?php checked(1, get_option('netpeak_post_logger_enabled', 1)); ?> />
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Plugin Logger', 'netpeak-logger'); ?></th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" class="dependent-checkbox" name="netpeak_plugin_logger_enabled" value="1"
                                <?php checked(1, get_option('netpeak_plugin_logger_enabled', 1)); ?> />
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('User Logger', 'netpeak-logger'); ?></th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" class="dependent-checkbox" name="netpeak_user_logger_enabled" value="1"
                                <?php checked(1, get_option('netpeak_user_logger_enabled', 1)); ?> />
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Comment Logger', 'netpeak-logger'); ?></th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" class="dependent-checkbox" name="netpeak_comment_logger_enabled" value="1"
                                <?php checked(1, get_option('netpeak_comment_logger_enabled', 1)); ?> />
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Email Logger', 'netpeak-logger'); ?></th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" class="dependent-checkbox" name="netpeak_email_logger_enabled" value="1"
                                <?php checked(1, get_option('netpeak_email_logger_enabled', 1)); ?> />
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Telegram API Token', 'netpeak-logger'); ?></th>
                    <td>
                        <input type="password" name="netpeak_telegram_bot_token" value="<?php echo esc_attr(get_option('netpeak_telegram_bot_token')); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }
}

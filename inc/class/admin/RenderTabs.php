<?php
namespace NetpeakLogger\Render;
use NetpeakLogger\Render\AdminRenderer;
use NetpeakLogger\Render\RenderFilters;
use NetpeakLogger\Admin;


class RenderTabs extends AdminRenderer{
    public static function logs_tab()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'netpeak_logs';

        $filters = [
            'user_login' => $_GET['user'] ?? '',
            'action' => $_GET['action'] ?? '',
            'log_type' => $_GET['log_type'] ?? '',
        ];
        $param = [
            'user' => [
                'label' => 'All Users',
                'query' => "SELECT DISTINCT user_login FROM {$table}",
            ],
            'action' => [
                'label' => 'All Actions',
                'query' => "SELECT DISTINCT action FROM {$table}",
                'callback' => [Admin::class, 'format_action'],
            ],
            'log_type' => [
                'label' => 'All Log Types',
                'query' => "SELECT DISTINCT log_type FROM {$table}",
                'callback' => 'ucfirst',
            ],
        ];
        $hidden_fields = [
            'page' => 'netpeak-logs',
            'tab' => 'logs',
        ];
        $logs = RenderFilters::get_filters($table, $filters, 'date', 'DESC');
        RenderFilters::render_filters($table, $param, $hidden_fields);
        

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

    public static function email_logs_tab() {
        global $wpdb;
        $table = $wpdb->prefix . 'netpeak_email_logs';

        $filters = [
            'status' => $_GET['status'] ?? '',
        ];
        $param = [
            'status' => [
                'label' => 'All Statuses',
                'query' => "SELECT DISTINCT status FROM {$table}",
                'callback' => 'ucfirst',
            ],
        ];
        $hidden_fields = [
            'page' => 'netpeak-logs',
            'tab' => 'email_logs',
        ];

        $logs = RenderFilters::get_filters($table, $filters, 'created_at');
        RenderFilters::render_filters($table, $param, $hidden_fields);
        
    
        ?>
        <div class="wrap">
            <h1>Email Logs</h1> 
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
                                    <span class="<?php echo $log->status === 'success' ? 'log-success' : 'log-failed'; ?>">
                                        <?php echo esc_html(ucfirst($log->status)); ?>
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
        <div class="header-settings">
            <h1>Settings</h1>
        </div>
        <div class="settings-structure-wrapper">
            <div class="settings-sidebar">
                <a href="?page=netpeak-logs&tab=settings" class="settings-tab <?php echo (!isset($_GET['settings'])) ? 'settings-tab-active' : ''; ?>">
                    <?php _e('Loggers', 'netpeak-logger'); ?>
                </a>
                <a href="?page=netpeak-logs&tab=settings&settings=telegram" class="settings-tab <?php echo (isset($_GET['settings']) && $_GET['settings'] == 'telegram') ? 'settings-tab-active' : ''; ?>">
                    <?php _e('Telegram API', 'netpeak-logger'); ?>
                </a>
            </div>
        <div class="settings-content">
            <?php
            $settings_tab = isset($_GET['settings']) ? $_GET['settings'] : '';

            if ($settings_tab == '') {
                include NETPEAK_LOGGER_COMPONENTS_ADMIN . 'settings/loggers.php';
            } 

            elseif ($settings_tab == 'telegram') {
                include NETPEAK_LOGGER_COMPONENTS_ADMIN . 'settings/telegram.php';
            } 
            
            ?>
        </div>
    </div>    
    <?php
    }
}

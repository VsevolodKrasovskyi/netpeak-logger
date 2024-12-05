<?php
namespace NetpeakLogger;
use NetpeakLogger\Admin;

class AdminRenderer
{
    /**
     * Render navigation tabs
     */
    private static function tabs($active_tab)
    {
        $tabs = [
            'logs' => 'Logs',
            'commits' => 'Commits',
            'settings' => 'Settings',
        ];

        $output = '';
        foreach ($tabs as $tab => $label) {
            $is_active = $active_tab === $tab ? 'netpeak-nav-tab-active' : '';
            $output .= '<a href="?page=netpeak-logs&tab=' . esc_attr($tab) . '" class="netpeak-nav-tab ' . esc_attr($is_active) . '">' . esc_html($label) . '</a>';
        }

        return $output;
    }

    /**
     * Render logs page
     */
    public static function render_logs_page()
    {
        $active_tab = sanitize_text_field($_GET['tab'] ?? 'logs');

        echo '<div class="wrap">';
        echo '<h1 class="netpeak-settings-title">Netpeak Logs</h1>';
        echo '<div class="netpeak-nav-tab-wrapper">';
        echo self::tabs($active_tab);
        echo '</div>';

        echo '<div class="netpeak-tab-content-wrapper">';
        if ($active_tab === 'logs') {
            echo '<div id="logs" class="netpeak-tab-content netpeak-active">';
            self::logs_tab();
            echo '</div>';
        } elseif ($active_tab === 'commits') {
            echo '<div id="commits" class="netpeak-tab-content netpeak-active">';
            self::commit_tab();
            echo '</div>';
        } elseif ($active_tab === 'settings') { 
            echo '<div id="settings" class="netpeak-tab-content netpeak-active">';
            self::settings_tab();
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }


    /**
     * Renders the logs tab displaying a table of logs with filters.
     *
     * This method retrieves the logs from the database, applies necessary filters,
     * and displays them in a table format. Each log includes details such as user,
     * action, log type, message, date, and available actions. If no logs are found,
     * a message indicating the absence of logs is displayed.
     */
    public static function logs_tab()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';

        $logs = self::get_filtered($table_name);
        self::render_filters($table_name);

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
                echo '<td>' . self::render_user_column($log->user_login) . '</td>';
                echo '<td>' . esc_html(Admin::format_action($log->action)) . '</td>';
                echo '<td>' . esc_html(ucfirst($log->log_type)) . '</td>';
                echo '<td>' . self::render_collapsible_message($log->message) . '</td>';
                echo '<td>' . esc_html($log->date) . '</td>';
                echo '<td>' . self::render_actions($log) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }

    
    /**
     * Render edit and delete links for a log entry, if the current user
     * is the same as the user who made the log entry and the log type is
     * "commit".
     *
     * @param object $log The log entry to render actions for.
     *
     * @return string The rendered action links.
     */
    private static function render_actions($log)
    {
        $actions = [];
        $current_user = wp_get_current_user();
        $can_edit = $current_user->user_login === $log->user_login && $log->log_type === 'commit';

        if ($can_edit) {
            $actions[] = '<a href="#" class="edit-commit" data-edit-id="' . esc_attr($log->id) . '">Edit</a>';
            $actions[] = '<a href="' . esc_url(admin_url('admin-post.php?action=delete_commit&id=' . $log->id)) . '" class="delete-commit" onclick="return confirm(\'Are you sure you want to delete this commit?\')">Delete</a>';
        }

        return implode(' | ', $actions);
    }

    /**
     * Render edit form via AJAX
     */
    public static function render_edit_form()
    {
        if (!isset($_GET['edit_id']) || empty($_GET['edit_id'])) {
            wp_send_json_error(['message' => 'Invalid edit_id']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
        $edit_id = intval($_GET['edit_id']);

        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));

        if (!$log) {
            wp_send_json_error(['message' => 'Commit not found']);
        }

        ob_start();
        ?>
        <div class="netpeak-popup-content">
            <button class="netpeak-popup-close">&times;</button>
            <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="edit_commit">
                <input type="hidden" name="id" value="<?php echo esc_attr($log->id); ?>">
                <label for="message">Message:</label>
                <textarea name="message" id="message" rows="5"><?php echo esc_textarea($log->message); ?></textarea>
                <button type="submit" class="button button-primary">Save Changes</button>
            </form>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Render user column with avatar
     */
    private static function render_user_column($user_login)
    {
        $user = get_user_by('login', $user_login);
        $avatar = $user ? get_avatar($user->ID, 40) : '<div style="width: 40px; height: 40px; background: #ccc; border-radius: 50%;"></div>';
        $display_name = $user ? esc_html($user->display_name) : esc_html($user_login);

        return '<div class="user-column" style="display: flex; align-items: center; gap: 10px;">' . $avatar . '<span>' . $display_name . '</span></div>';
    }


    /**
     * Render collapsible message
     */
    private static function render_collapsible_message($message)
    {
        $short_message = wp_trim_words($message, 10, '...');
        return '<div class="message-container" title="Click to view full message" data-full-message="' . esc_attr($message) . '" onclick="showPopupMessage(this)">
                    <span class="short-message">' . esc_html($short_message) . '</span>
                </div>';
    }


    

        /**
         * Render commit tab
         *
         * This function renders a form for adding a new commit. The form includes
         * a field for the commit message, a select box for choosing the commit
         * type, and a submit button. The form is submitted via AJAX to the
         * `netpeak_add_commit` action.
         *
         * @since 1.0.0
         */
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

    public static function settings_tab() {
        echo '<h1>Settings</h1>';
    }
        
    


    //Filters
    private static function render_filter($name, $default_label, $options, $callback = null)
    {
        $selected = sanitize_text_field($_GET[$name] ?? '');
        echo '<select name="' . esc_attr($name) . '">';
        echo '<option value="">' . esc_html($default_label) . '</option>';

        foreach ($options as $option) {
            $is_selected = $selected === $option ? 'selected' : '';
            $label = $callback ? $callback($option) : $option;
            echo '<option value="' . esc_attr($option) . '" ' . esc_attr($is_selected) . '>' . esc_html($label) . '</option>';
        }

        echo '</select>';
    }

    private static function get_filtered($table_name) {
        global $wpdb;
    

        if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name))) {
            return [];
        }

        $filters = [
            'user_login' => sanitize_text_field($_GET['user'] ?? ''),
            'action' => sanitize_text_field($_GET['action'] ?? ''),
            'log_type' => sanitize_text_field($_GET['log_type'] ?? ''),
        ];
    
        $where_clauses = [];
        $params = [];
    

        if (!empty($filters['user_login'])) {
            $where_clauses[] = "user_login = %s";
            $params[] = $filters['user_login'];
        }
        if (!empty($filters['action'])) {
            $where_clauses[] = "action = %s";
            $params[] = $filters['action'];
        }
        if (!empty($filters['log_type'])) {
            $where_clauses[] = "log_type = %s";
            $params[] = $filters['log_type'];
        }
    

        $where_sql = empty($where_clauses) ? '1=1' : implode(' AND ', $where_clauses);
    

        $query = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY date DESC";
    
        if (!empty($params)) {
            $query = $wpdb->prepare($query, ...$params);
        }
        return $wpdb->get_results($query);
    }
    

    private static function render_filters($table_name)
    {
        global $wpdb;

        echo '<form method="GET" action="">';
        echo '<input type="hidden" name="page" value="netpeak-logs">';
        echo '<input type="hidden" name="tab" value="logs">';


        self::render_filter(
            'user',
            'All Users',
            $wpdb->get_col("SELECT DISTINCT user_login FROM {$table_name}")
        );
        self::render_filter(
            'action',
            'All Actions',
            $wpdb->get_col("SELECT DISTINCT action FROM {$table_name}"),
            [Admin::class, 'format_action']
        );
        self::render_filter(
            'log_type',
            'All Log Types',
            $wpdb->get_col("SELECT DISTINCT log_type FROM {$table_name}"),
            'ucfirst'
        );

        echo '<button type="submit" class="button">Filter</button>';
        echo '</form>';
    }

}



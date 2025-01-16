<?php
namespace NetpeakLogger\Render;
use NetpeakLogger\Admin;
use NetpeakLogger\Render\RenderTabs;

class AdminRenderer
{
    /**
     * Render navigation tabs
     */
    private static function tabs($active_tab)
    {
        $tabs = [
            'logs' => 'Logs',
            'email_logs' => 'Email Logs',
            'commits' => 'Commits',
            'settings' => 'Settings',
        ];
        
        $output = '';
        foreach ($tabs as $tab => $label) {
            if ($tab === 'email_logs' && !get_option('netpeak_email_logger_enabled', 1)) {
                continue;
            }

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
            RenderTabs::logs_tab();
            echo '</div>';
        } elseif ($active_tab === 'commits') {
            echo '<div id="commits" class="netpeak-tab-content netpeak-active">';
            RenderTabs::commit_tab();
            echo '</div>';
        } elseif ($active_tab === 'settings') { 
            echo '<div id="settings" class="netpeak-tab-content netpeak-active">';
            RenderTabs::settings_tab();
            echo '</div>';
        } elseif ($active_tab === 'email_logs') { 
            echo '<div id="email-logs" class="netpeak-tab-content netpeak-active">';
            RenderTabs::email_logs_tab();
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
    protected static function render_actions($log)
    {
        $actions = [];

        $actions[] = '<a href="#" class="edit-commit" data-edit-id="' . esc_attr($log->id) . '">Edit</a>';
        $actions[] = '<a href="' . esc_url(admin_url('admin-post.php?action=delete_commit&id=' . $log->id)) . '" class="delete-commit" onclick="return confirm(\'Are you sure you want to delete?\')">Delete</a>';
        
        return implode(' | ', $actions);
    }

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
    protected static function render_user_column($user_login)
    {
        if ($user_login === 'wordpress@wordpress.org') {
            $avatar = '<img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSiQqvP9mSAN_KNxZlbvD9VT-yl4Vf_PuT6Cw&s" width="40" alt="System User" style="border-radius: 50%;">';
            $display_name = 'Wordpress System (Cron)';
        } else {
            $user = get_user_by('login', $user_login);
            $avatar = $user ? get_avatar($user->ID, 40) : '<div style="width: 40px; height: 40px; background: #ccc; border-radius: 50%;"></div>';
            $display_name = $user ? esc_html($user->display_name) : esc_html($user_login);
        }
    
        return '<div class="user-column" style="display: flex; align-items: center; gap: 10px;">' . $avatar . '<span>' . $display_name . '</span></div>';
    }
    


    /**
     * Render collapsible message
     */
    protected static function render_collapsible_message($message)
    {
        $short_message = wp_trim_words($message, 10, '...');
        return '<div class="message-container" title="Click to view full message" data-full-message="' . esc_attr($message) . '" onclick="showPopupMessage(this)">
                    <span class="short-message">' . esc_html($short_message) . '</span>
                </div>';
    }

}



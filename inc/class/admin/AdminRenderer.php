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
            if (
                $tab === 'email_logs' && 
                (!get_option('netpeak_email_logger_enabled', 1) || !is_plugin_active('contact-form-7/wp-contact-form-7.php'))
            ) {
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

        }elseif ($active_tab === 'edit_commit') {
            RenderTabs::edit_commit_page();
        
        } elseif ($active_tab === 'email_logs') { 
            if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
                echo '<div id="email-logs" class="netpeak-tab-content netpeak-active">';
                RenderTabs::email_logs_tab();
                echo '</div>';
            }
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
    $is_archive = isset($_GET['is_archive']) ? intval($_GET['is_archive']) : 0;
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

    $is_logs = ($tab === 'logs' || ($page === 'netpeak-logs' && $tab !== 'email_logs'));
    $is_email_logs = ($tab === 'email_logs');

    $actions = [];

    if ($is_logs) {
        $actions[] = '<a href="' . esc_url(admin_url('admin.php?page=netpeak-logs&tab=edit_commit&id=' . $log->id)) . '"><img src="' . esc_url(NETPEAK_LOGGER_URL . 'assets/img/edit.png') . '" alt="Edit" width="16" height="16"></a>';
    }

    $actions[] = '<a href="' . esc_url(admin_url('admin-post.php?action=delete_commit&id=' . $log->id)) . '" class="delete-commit" onclick="return confirm(\'Are you sure you want to delete?\')"><img src="' . esc_url(NETPEAK_LOGGER_URL . 'assets/img/delete.png') . '" alt="Delete" width="16" height="16"></a>';

    $log_type = $is_logs ? 'logs' : ($is_email_logs ? 'email' : '');

    if ($log_type) {
        if ($is_archive === 0) {
            $actions[] = '<a href="#" class="archive-log" data-log-id="' . esc_attr($log->id) . '" data-log-type="' . esc_attr($log_type) . '"><img src="' . esc_url(NETPEAK_LOGGER_URL . 'assets/img/box.png') . '" alt="Archive" width="16" height="16"></a>';
        } elseif ($is_archive === 1) {
            $actions[] = '<a href="#" class="unarchive-log" data-log-id="' . esc_attr($log->id) . '" data-log-type="' . esc_attr($log_type) . '"><img src="' . esc_url(NETPEAK_LOGGER_URL . 'assets/img/unbox.png') . '" alt="Unarchive" width="16" height="16"></a>';
        }
    }

    return implode(' | ', $actions);
}


    public static function bulk_edit_actions() {
        $bulk_actions = [
            '' => __('Bulk Actions', 'netpeak-logger'),
            'delete' => __('Delete', 'netpeak-logger'),
        ];
        
        if (!isset($_GET['is_archive']) || $_GET['is_archive'] == '0') {
            $bulk_actions['archive'] = __('Archive', 'netpeak-logger');
        }
        
        if (isset($_GET['is_archive']) && $_GET['is_archive'] == '1') {
            $bulk_actions['unarchive'] = __('Unarchive', 'netpeak-logger');
        }
        
        ?>
        <form id="bulk-action-form" method="POST">
            <input type="hidden" name="action" value="bulk_edit_logs">
            
            <div style="margin-top: 20px;">
                <select id="bulk-action-selector" name="bulk_action">
                    <?php foreach ($bulk_actions as $action => $label) : ?>
                        <option value="<?php echo esc_attr($action); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-primary" id="apply-bulk-action"><?php _e('Apply', 'netpeak-logger'); ?></button>
            </div>
        </form>
        <?php
    }
    
    /**
     * Render user column with avatar
     */
    protected static function render_user_column($user_email)
    {
        if ($user_email === 'wordpress@wordpress.org') {
            $avatar = '<img src=" ' . esc_url(NETPEAK_LOGGER_URL . 'assets/img/wordpress.png'). '" width="40" alt="System User" style="border-radius: 50%;">';
            $display_name = 'WordPress System (Cron)';
        } else {

            $user = get_user_by('email', $user_email);
            $avatar = $user ? get_avatar($user->ID, 40) : '<div style="width: 40px; height: 40px; background: #ccc; border-radius: 50%;"></div>';
            $display_name = $user ? esc_html($user->display_name) : esc_html($user_email); 
        }

        return '<div class="user-column" style="display: flex; align-items: center; gap: 10px;">' . $avatar . '<span>' . $display_name . '</span></div>';
    }

    public static function format_archive_label($value) {
        $labels = [
            '0' => 'Active',
            '1' => 'Archived',
        ];
    
        return $labels[$value] ?? 'Unknown';
    }


    /**
     * Render collapsible message
     */
    protected static function render_collapsible_message($message, $log_type)
    {
        $short_message = wp_trim_words($message, 10, '...');

        if ($log_type === 'commit') {
            return '<div class="message-container" data-full-message="' . esc_attr($message) . '" onclick="showPopupMessage(this)">
                        <span class="view-full-label" style="font-weight: bold">Click to view full</span>
                    </div>';
        }

        return '<div class="message-container" title="Click to view full message" data-full-message="' . esc_attr($message) . '" onclick="showPopupMessage(this)">
                    <span class="short-message">' . esc_html($short_message) . '</span>
                </div>';
    }
    /**
     * Render pagination controls
     *
     * @param int $total_records Total number of logs found
     * @param int $limit Number of logs per page
     * @param int $current_page Current page number
     */
    public static function pagination($total_records, $limit, $current_page, $filters = [])
    {
        ?>
        <div class="pagination-logs" style="margin-top: 20px;">
            <form id="pagination-form" method="POST" action="<?php echo admin_url('admin.php?page=netpeak-logs' . '&' . http_build_query(array_filter($filters))); ?>" data-total-pages="<?php echo esc_attr(ceil($total_records / $limit)); ?>">
                <input type="hidden" name="pagination_page" id="pagination-page" value="<?php echo esc_attr($current_page); ?>">
                <?php foreach ($filters as $filter_name => $filter_value) : ?>
                    <input type="hidden" name="<?php echo esc_attr($filter_name); ?>" value="<?php echo esc_attr($filter_value); ?>">
                <?php endforeach; ?>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div id="records-limit">
                        <p><strong><?php echo esc_html($total_records); ?></strong> <?php esc_html_e('Logs found', 'netpeak-logger'); ?></p>
                        <label for="records-per-page"><?php esc_html_e('Records per page:', 'netpeak-logger'); ?></label>
                        <select id="records-per-page" name="pagination_limit">
                            <option value="10" <?php selected($limit, 10); ?>>10</option>
                            <option value="25" <?php selected($limit, 25); ?>>25</option>
                            <?php if ($total_records >= 50) : ?>
                                <option value="50" <?php selected($limit, 50); ?>>50</option>
                            <?php endif; ?>
                            <?php if ($total_records >= 100) : ?>
                                <option value="100" <?php selected($limit, 100); ?>>100</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div id="pagination-buttons" style="text-align: right;">
                        <button type="button" id="prev-page" class="button" <?php disabled($current_page <= 1); ?>>
                            <?php esc_html_e('Previous', 'netpeak-logger'); ?>
                        </button>
                        <span id="current-page-info">
                            <?php printf(esc_html__('Page %d of %d', 'netpeak-logger'), $current_page, ceil($total_records / $limit)); ?>
                        </span>
                        <button type="button" id="next-page" class="button" <?php disabled($current_page >= ceil($total_records / $limit)); ?>>
                            <?php esc_html_e('Next', 'netpeak-logger'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}



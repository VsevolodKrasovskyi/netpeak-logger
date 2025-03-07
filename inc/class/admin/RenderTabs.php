<?php
namespace NetpeakLogger\Render;
use NetpeakLogger\Render\AdminRenderer;
use NetpeakLogger\Render\RenderFilters;
use NetpeakLogger\Admin;


class RenderTabs extends AdminRenderer{
    public static function logs_tab() {
        global $wpdb;
        $table = $wpdb->prefix . 'netpeak_logs';
        //Variables
        $current_page = isset($_POST['pagination_page']) ? intval($_POST['pagination_page']) : 1;
        $limit = isset($_POST['pagination_limit']) ? intval($_POST['pagination_limit']) : 10;
        $offset = ($current_page - 1) * $limit;
    
        $filters = [
            'user_login' => $_GET['user'] ?? null,
            'action'     => $_GET['actions'] ?? null,
            'log_type'   => $_GET['log_type'] ?? null,
            'is_archive'   => $_GET['is_archive'] ?? null,
        ];
        $param = [
            'user' => [
                'label'  => 'All Users',
                'query'  => "SELECT DISTINCT user_login FROM {$table}",
            ],
            'action' => [
                'label'    => 'All Actions',
                'query'    => "SELECT DISTINCT action FROM {$table}",
                'callback' => [Admin::class, 'format_action'],
            ],
            'log_type' => [
                'label'    => 'All Log Types',
                'query'    => "SELECT DISTINCT log_type FROM {$table}",
                'callback' => 'ucfirst',
            ],
            'is_archive' => [
                'label' => 'Show Logs',
                'query' => "SELECT DISTINCT is_archive FROM {$table}",
                'callback' => [AdminRenderer::class, 'format_archive_label'],
            ],
        ];
        $hidden_fields = [
            'page' => 'netpeak-logs',
            'tab'  => 'logs',
        ];
    
        $logs = RenderFilters::query_db($table, $filters, 'created_at', 'DESC', $limit, $offset);
        $total_records = RenderFilters::get_total_records($table, $filters);
        RenderFilters::render_filters($table, $param, $hidden_fields);

        ?>
        <div class="wrap">
            <h1>Logs</h1>
            <?php if (!empty($logs)) : ?>
            <table class="netpeak-logs-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-logs"></th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Log Type</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><input type="checkbox" class="select-log" value="<?php echo esc_attr($log->id)?>" data-log-type="logs"></td>
                                <td><?php echo AdminRenderer::render_user_column($log->user_login); ?></td>
                                <td><?php echo esc_html(Admin::format_action($log->action)); ?></td>
                                <td><?php echo esc_html(ucfirst($log->log_type)); ?></td>
                                <td><?php echo AdminRenderer::render_collapsible_message($log->message, $log->log_type); ?></td>
                                <td><?php echo esc_html($log->created_at); ?></td>
                                <td><?php echo AdminRenderer::render_actions($log); ?></td>
                            </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
            <?php AdminRenderer::pagination($total_records, $limit, $current_page, $filters);?>
            <?php else : ?>
                <h3 style="display:flex; justify-content:center"><?php esc_html_e('No logs found.', 'netpeak-logger'); ?></h3>
            <?php endif; ?>
        </div>
        <?php
    }
    

    public static function commit_tab() {
        $current_user = wp_get_current_user();
        $avatar = get_avatar($current_user->ID, 80);
        $username = esc_html($current_user->display_name);
    
        $commit_types = apply_filters('netpeak_commit_types', [
            'update'  => 'Update',
            'fix'     => 'Fix',
            'feature' => 'Feature',
        ]);
    
        $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
        $log_message = '';
    
        if ($edit_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'netpeak_logs';
            $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
            if ($log) {
                $log_message = wp_kses_post($log->message);
            }
        }
        ?>
    
        <div class="netpeak-commit-container netpeak-wysiwyg">
            <div class="netpeak-user-info">
                <div class="netpeak-avatar"><?php echo $avatar; ?></div>
                <div class="netpeak-username"><?php echo $username; ?></div>
            </div>
            <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="netpeak-commit-form">
                <input type="hidden" name="action" value="netpeak_add_commit">
                <?php wp_nonce_field('netpeak_nonce', 'security', true, false); ?>
    
                <label for="commit_message">Commit Message:</label>
                <?php wp_editor(
                    $log_message,
                    'commit_message',
                    [
                        'textarea_name' => 'commit_message',
                        'textarea_rows' => 20,
                        'wpautop'       => false,
                        'editor_height' => 200,
                        'media_buttons' => false,
                        'teeny'         => false,
                        'quicktags'     => false,
                    ]
                ); ?>
    
                <div style="display: flex; align-items: center; flex-direction: column; width: 100%;">
                    <label for="commit_type">Commit Type:</label>
                    <select id="commit_type" name="commit_type">
                        <?php foreach ($commit_types as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="netpeak-commit-button">Add Commit</button>
            </form>
        </div>
    
        <?php
    }
    

    public static function edit_commit_page() {
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            wp_die(__('Invalid edit ID', 'netpeak-logger'));
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
        $edit_id = intval($_GET['id']);
    
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
        if (!$log) {
            wp_die(__('Commit not found', 'netpeak-logger'));
        }
        ?>
    
        <div class="wrap netpeak-wysiwyg netpeak-logger-commit">
            <h1>Edit Commit</h1>
            <form class="edit-commit-form" method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="edit_commit">
                <input type="hidden" name="id" value="<?php echo esc_attr($log->id); ?>">
    
                <?php wp_editor(
                    wp_kses_post($log->message),
                    'commit_message_editor',
                    [
                        'textarea_name' => 'message',
                        'textarea_rows' => 10,
                        'editor_height' => 250,
                        'wpautop'       => false,
                        'media_buttons' => false,
                        'teeny'         => false,
                        'quicktags'     => true,
                        'editor_class'  => 'netpeak-wysiwyg',
                    ]
                ); ?>
    
                <button type="submit" class="button button-primary">Save Changes</button>
            </form>
        </div>
    
        <?php
    }
    

    public static function email_logs_tab() {
        global $wpdb;
        $table = $wpdb->prefix . 'netpeak_email_logs';
        //Variables
        $current_page = isset($_POST['pagination_page']) ? intval($_POST['pagination_page']) : 1;
        $limit = isset($_POST['pagination_limit']) ? intval($_POST['pagination_limit']) : 10;
        $offset = ($current_page - 1) * $limit;
    
        $filters = [
            'status' => $_GET['status'] ?? '',
            'is_archive' => $_GET['is_archive'] ?? '',
        ];
        $param = [
            'status' => [
                'label' => 'All Statuses',
                'query' => "SELECT DISTINCT status FROM {$table}",
                'callback' => 'ucfirst',
            ],
            'is_archive' => [
                'label' => 'Show Logs',
                'query' => "SELECT DISTINCT is_archive FROM {$table}",
                'callback' => [AdminRenderer::class, 'format_archive_label'],
            ],
            
        ];
        $hidden_fields = [
            'page' => 'netpeak-logs',
            'tab' => 'email_logs',
        ];

        $logs = RenderFilters::query_db($table, $filters, 'created_at');
        $total_records = RenderFilters::get_total_records($table, $filters);
        RenderFilters::render_filters($table, $param, $hidden_fields);
        
    
        ?>
        <div class="wrap">
            <h1>Email Logs</h1>
            <?php if (!empty($logs)) : ?>
            <table class="netpeak-logs-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-logs"></th>
                        <th>Sender</th>
                        <th>Recipient</th>
                        <th>Status</th>
                        <th>Body</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><input type="checkbox" class="select-log" value="<?php echo esc_attr($log->id)?>" data-log-type="email"></td>
                                <td><?php echo esc_html($log->sender); ?></td>
                                <td><?php echo esc_html($log->recipient); ?></td>
                                <td>
                                    <span class="<?php echo $log->status === 'success' ? 'log-success' : 'log-failed'; ?>">
                                        <?php echo esc_html(ucfirst($log->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $output = "<strong>Subject:</strong> " . esc_html($log->subject) . "\n" .
                                    "<strong>Message:</strong> " . esc_html($log->message);
                                    echo nl2br($output);
                                    ?>
                                </td>
                                <td><?php echo esc_html($log->created_at); ?></td>
                                <td><?php echo AdminRenderer::render_actions($log); ?></td>
                            </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
            <?php AdminRenderer::pagination($total_records, $limit, $current_page);?>
            <?php else : ?>
                <h3 style="display:flex; justify-content:center"><?php esc_html_e('No logs found.', 'netpeak-logger'); ?></h3>
            <?php endif; ?>
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
                <a href="?page=netpeak-logs&tab=settings" data-settings="loggers" class="settings-tab <?php echo (isset($_GET['settings']) && $_GET['settings'] == 'loggers') ? 'settings-tab-active' : ''; ?>">
                    <?php _e('Loggers', 'netpeak-logger'); ?>
                </a>
                <a href="?page=netpeak-logs&tab=settings&settings=telegram" data-settings="telegram" class="settings-tab <?php echo (isset($_GET['settings']) && $_GET['settings'] == 'telegram') ? 'settings-tab-active' : ''; ?>">
                    <?php _e('Telegram API', 'netpeak-logger'); ?>
                </a>
                <a href="?page=netpeak-logs&tab=settings&settings=reports" data-settings="reports" class="settings-tab <?php echo (isset($_GET['settings']) && $_GET['settings'] == 'reports') ? 'settings-tab-active' : ''; ?>">
                    <?php _e('Email Reports', 'netpeak-logger'); ?>
                </a>
            </div>
        <div id="loader" style="display:none;">
            <img src="<?php echo NETPEAK_LOGGER_URL . 'assets/img/loading.gif'; ?>" alt="Loading">
        </div>
        <div class="settings-content">
            <?php
            include NETPEAK_LOGGER_COMPONENTS_ADMIN . 'settings/intro.php';           
            ?>
        </div>
    </div>    
    <?php
    }
}

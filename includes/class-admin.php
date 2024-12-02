<?php
namespace NetpeakLogger;
use NetpeakLogger\AdminRenderer;

class Admin
{
    const FORMATTED_ACTIONS = [
        'save_post' => 'Save Post',
        'before_delete_post' => 'Delete Post',
        'publish_post' => 'Publish Post',
        'unpublish_post' => 'Unpublish Post',
        'activated_plugin' => 'Plugin Activated',
        'deactivated_plugin' => 'Plugin Deactivated',
        'update_postmeta' => 'Update Metadata',
        'add_postmeta' => 'Add Metadata',
        'delete_postmeta' => 'Delete Metadata',
        'user_register' => 'User Registered',
        'profile_update' => 'Profile Updated',
        'delete_user' => 'User Deleted',
        'wp_insert_comment' => 'Add Comment',
        'edit_comment' => 'Edit Comment',
        'trash_comment' => 'Trash Comment',
    ];

    public static function format_action($action)
    {
        return self::FORMATTED_ACTIONS[$action] ?? ucfirst($action);
    }

    public static function init()
    {
        add_menu_page(
            'Netpeak Logs',
            'Netpeak Logs',
            'manage_options',
            'netpeak-logs',
            [AdminRenderer::class, 'render_logs_page'],
            'dashicons-archive'
        );        
        add_action('admin_bar_menu', function ($admin_bar) {
            if (current_user_can('manage_options')) { 
                $admin_bar->add_menu(array(
                    'id'    => 'netpeak-logs', 
                    'title' => __('Logs', 'netpeak-logger'), 
                    'href'  => admin_url('admin.php?page=netpeak-logs'), 
                    'meta'  => array(
                        'title' => __('Netpeak Logs'), 
                    ),
                ));
            }
        }, 100);

    }

    public static function hooks()
    {
        add_action('admin_post_delete_commit', [self::class, 'handle_delete_commit']);
        add_action('admin_post_edit_commit', [self::class, 'handle_edit_commit']);
        add_action('wp_ajax_render_edit_form', [AdminRenderer::class, 'render_edit_form']);
    }


    //Edit & Delete Commit
    public static function handle_delete_commit() {
        if (!isset($_GET['id'])) {
            wp_die(__('Invalid request.', 'text-domain'));
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
        $id = intval($_GET['id']);
    
        $wpdb->delete($table_name, ['id' => $id], ['%d']);
    
        wp_redirect(admin_url('admin.php?page=netpeak-logs&deleted=true'));
        exit;
    }
    
    

    public static function handle_edit_commit() {
        if (!isset($_POST['id']) || !isset($_POST['message'])) {
            wp_die(__('Invalid request.', 'text-domain'));
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'netpeak_logs';
        $id = intval($_POST['id']);
        $message = sanitize_text_field($_POST['message']);
    
        $wpdb->update(
            $table_name,
            ['message' => $message],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
    
        wp_redirect(admin_url('admin.php?page=netpeak-logs&updated=true'));
        exit;
    }    
}

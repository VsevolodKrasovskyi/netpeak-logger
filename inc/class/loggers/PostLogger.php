<?php
/**
 * Post Logger class for handling post-related events
 *
 * @package NetpeakLogger
 * @since 2.0
 */

namespace NetpeakLogger\Loggers;

use NetpeakLogger\Logger;

class PostLogger extends Logger {
    /**
     * Initialize the post logger
     *
     * @return void
     */
    public static function init() {
        $hooks = [
            'post_updated' => 3, 
            'transition_post_status' => 3, 
            'before_delete_post' => 1, 
        ];

        foreach ($hooks as $hook => $args) {
            if (has_action($hook)) {
                add_action($hook, [self::class, 'log_event'], 10, $args);
            }
        }
    }

    /**
     * Generate a message for post-related events
     *
     * @param string $action The WordPress action being logged
     * @param mixed $arg1 First argument from the hook
     * @param mixed $arg2 Second argument from the hook
     * @param mixed $arg3 Third argument from the hook
     * @return string|null Generated message or null if no message could be generated
     */
    protected static function generate_message($action, $arg1, $arg2, $arg3) {
        switch ($action) {
            case 'post_updated':

                if ($arg2->post_status === $arg3->post_status) {
                    return self::handle_post_updated($arg1, $arg2, $arg3);
                }
                break;

            case 'transition_post_status':
                return self::handle_status_transition($arg1, $arg2, $arg3);

            case 'before_delete_post':
                return self::handle_delete_post($arg1);
        }

        return null;
    }
    /**
     * Handle post updated event
     *
     * @param int $post_id Post ID.
     * @param WP_Post $post_after Post object following the update.
     * @param WP_Post $post_before Post object before the update.
     * @return string|null
     */
    private static function handle_post_updated($post_id, $post_after, $post_before) {
        if (self::should_skip_post($post_after)) {
            return null;
        }

        $post_type_obj = get_post_type_object($post_after->post_type);
        $post_type_label = $post_type_obj ? strtolower($post_type_obj->labels->singular_name) : $post_after->post_type;

        $changes = [];


        if ($post_before->post_title !== $post_after->post_title) {
            $changes[] = sprintf('title from "%s" to "%s"', $post_before->post_title, $post_after->post_title);
        }


        if ($post_before->post_content !== $post_after->post_content) {
            $revision_link = self::get_latest_revision_link($post_after->ID);
            
            $editor = get_post_meta($post_after->ID, '_elementor_edit_mode', true) === 'builder' ? 'Elementor' : 'Classic Editor';
        
            $changes[] = sprintf('content updated via %s %s', 
                $editor, 
                ($revision_link ? ' <a href="' . $revision_link . '" target="_blank">View Revision</a> ' : '')
            );
        }
        

        if (!empty($changes)) {
            return sprintf('Updated %s: "%s" (%s)', $post_type_label, $post_after->post_title, implode(', ', $changes));
        }

        return null;
    }

    /**
     * Handle post status transitions
     *
     * @param string $new_status New post status.
     * @param string $old_status Old post status.
     * @param WP_Post $post Post object.
     * @return string|null
     */
    private static function handle_status_transition($new_status, $old_status, $post) {
        if (self::should_skip_post($post)) {
            return null;
        }

        if($new_status === $old_status) {
            return null;
        }

        $post_type = get_post_type_object($post->post_type);
        $post_type_label = $post_type ? strtolower($post_type->labels->singular_name) : $post->post_type;

        switch ($new_status) {
            case 'trash':
                return sprintf('Moved %s "%s" to trash', $post_type_label, $post->post_title);

            case 'publish':
                if ($old_status === 'trash') {
                    return sprintf('Restored %s "%s" from trash', $post_type_label, $post->post_title);
                } elseif ($old_status === 'auto-draft' || $old_status === 'draft') {
                    return sprintf('Published %s "%s"', $post_type_label, $post->post_title);
                }
                return sprintf('Changed %s "%s" to published', $post_type_label, $post->post_title);

            case 'future':
                $scheduled_date = get_post_time('Y-m-d H:i:s', true, $post);
                $timezone = get_option('timezone_string');
                if ($timezone) {
                    $date = new DateTime($scheduled_date, new DateTimeZone($timezone));
                    $scheduled_date = $date->format('Y-m-d H:i:s');
                } else {
                    $scheduled_date = get_post_time('Y-m-d H:i:s', false, $post);
                }

                return sprintf('Scheduled %s "%s" for %s', $post_type_label, $post->post_title, $scheduled_date);

            case 'private':
                return sprintf('Changed %s "%s" to private', $post_type_label, $post->post_title);

            case 'pending':
                return sprintf('Marked %s "%s" as pending review', $post_type_label, $post->post_title);

            case 'draft':
                if ($old_status === 'auto-draft') {
                    return null; 
                }
                return sprintf('Unpublished %s "%s" to draft', $post_type_label, $post->post_title);

            default:
                return sprintf('Changed status of %s "%s" from "%s" to "%s"', 
                    $post_type_label, 
                    $post->post_title, 
                    $old_status, 
                    $new_status
                );
        }
    }

    /**
     * Handle post permanently deleted
     *
     * @param int $post_id Post ID.
     * @return string|null
     */
    private static function handle_delete_post($post_id) {
        $post = get_post($post_id);
        if (!$post || self::should_skip_post($post)) {
            return null;
        }

        $post_type = get_post_type_object($post->post_type);
        $post_type_label = $post_type ? strtolower($post_type->labels->singular_name) : $post->post_type;

        return sprintf('Permanently deleted %s: "%s"', $post_type_label, $post->post_title);
    }

    /**
     * Determine if a post should be skipped
     *
     * @param WP_Post $post Post object.
     * @return bool
     */
    private static function should_skip_post($post) {
        return !$post
            || !isset($post->post_type, $post->post_status)
            || in_array($post->post_status, ['auto-draft', 'inherit'])
            || wp_is_post_revision($post->ID)
            || wp_is_post_autosave($post->ID);
    }

    private static function get_latest_revision_link($post_id) {
        global $wpdb;
    
        $is_elementor = get_post_meta($post_id, '_elementor_edit_mode', true) === 'builder';
    
        if ($is_elementor) {
            $revision_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM $wpdb->posts 
                    WHERE post_parent = %d 
                    AND post_type = 'revision' 
                    AND post_status = 'inherit' 
                    ORDER BY post_date DESC 
                    LIMIT 1",
                    $post_id
                )
            );
    
            if ($revision_id) {
                return admin_url('post.php?post=' . $revision_id . '&action=elementor');
            }
        } else {
            $revisions = wp_get_post_revisions($post_id);
    
            if (!empty($revisions)) {
                $latest_revision = reset($revisions);
                return admin_url('revision.php?revision=' . $latest_revision->ID);
            }
        }
    
        return null;
    }

    /**
     * Detect changes in metadata (category, thumbnail, author)
     *
     * @param int $post_id Post ID.
     * @param WP_Post $post_before Post object before update.
     * @param WP_Post $post_after Post object after update.
     * @return array List of metadata changes
     */
}

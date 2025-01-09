<?php
/**
 * Comment Logger class for handling comment-related events
 *
 * @package NetpeakLogger
 * @since 2.0
 */

namespace NetpeakLogger\Loggers;

use NetpeakLogger\Logger;

class CommentLogger extends Logger {
    /**
     * Initialize the comment logger
     *
     * @return void
     */
    public static function init() {
        $hooks = [
            'wp_insert_comment' => 2,
            'edit_comment' => 1,
            'trash_comment' => 1,
        ];

        foreach ($hooks as $hook => $args) {
            add_action($hook, [self::class, 'log_event'], 20, $args);
        }
    }

    /**
     * Generate a message for comment-related events
     *
     * @param string $action The WordPress action being logged
     * @param mixed $arg1 First argument from the hook
     * @param mixed $arg2 Second argument from the hook
     * @param mixed $arg3 Third argument from the hook
     * @return string|null Generated message or null if no message could be generated
     */
    protected static function generate_message($action, $arg1, $arg2, $arg3) {
        switch ($action) {
            case 'wp_insert_comment':
                return self::handle_wp_insert_comment($arg1, $arg2);

            case 'edit_comment':
                return self::handle_edit_comment($arg1);

            case 'trash_comment':
                return self::handle_trash_comment($arg1);
        }

        return null;
    }

    /**
     * Handle new comment insertion event
     *
     * @param int $comment_id Comment ID
     * @param object $comment Comment object
     * @return string|null
     */
    private static function handle_wp_insert_comment($comment_id, $comment) {
        $comment = get_comment($comment_id);
        if ($comment) {
            $post = get_post($comment->comment_post_ID);
            return sprintf('New comment added on post "%s"', $post ? $post->post_title : '#' . $comment->comment_post_ID);
        }

        return null;
    }

    /**
     * Handle comment editing event
     *
     * @param int $comment_id Comment ID
     * @return string|null
     */
    private static function handle_edit_comment($comment_id) {
        $comment = get_comment($comment_id);
        if ($comment) {
            $post = get_post($comment->comment_post_ID);
            return sprintf('Comment edited on post "%s"', $post ? $post->post_title : '#' . $comment->comment_post_ID);
        }

        return null;
    }

    /**
     * Handle comment trashing event
     *
     * @param int $comment_id Comment ID
     * @return string|null
     */
    private static function handle_trash_comment($comment_id) {
        $comment = get_comment($comment_id);
        if ($comment) {
            $post = get_post($comment->comment_post_ID);
            return sprintf('Comment moved to trash on post "%s"', $post ? $post->post_title : '#' . $comment->comment_post_ID);
        }

        return null;
    }
}

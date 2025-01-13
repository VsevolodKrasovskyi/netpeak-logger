<form method="post" action="options.php">
    <?php
    settings_fields('netpeak-logger-settings-loggers');
    do_settings_sections('netpeak-logger-settings-loggers');
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
        
    </table>
    <?php submit_button(); ?>
</form>
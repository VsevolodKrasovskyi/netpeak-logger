<form id="setting-form" method="post" action="">
    <input type="hidden" name="action" value="settings_form_submit"/>
    <input type="hidden" name="settings" value="loggers"/>
    <table class="form-table">
        <tr>
            <th><?php _e('Post Logger', 'netpeak-logger'); ?></th>
            <td>
                <label class="switch">
                    <input type="checkbox" class="dependent-checkbox" name="netpeak_post_logger_enabled" value="1"
                        <?php checked(1, get_option('netpeak_post_logger_enabled', 1)); ?> />
                    <span class="slider"></span>
                </label>
                <div class="tooltip" style="margin-left:20px;">
                <span class="tooltip-icon">?</span>
                    <div class="tooltip-content">
                        <p><?php _e('Enables logging of posts (Deleting, editing, scheduling, status changes)','netpeak-logger');?></p>
                    </div>
                </div>
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
                <div class="tooltip" style="margin-left:20px;">
                <span class="tooltip-icon">?</span>
                    <div class="tooltip-content">
                        <p><?php _e('Enables logging of plugins(install,uninstall, update, activate, deactivate)','netpeak-logger');?></p>
                    </div>
                </div>
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
                <div class="tooltip" style="margin-left:20px;">
                <span class="tooltip-icon">?</span>
                    <div class="tooltip-content">
                        <p><?php _e('Enables user logging(Deleting, editing, creating)','netpeak-logger');?></p>
                    </div>
                </div>
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
                <div class="tooltip" style="margin-left:20px;">
                <span class="tooltip-icon">?</span>
                    <div class="tooltip-content">
                        <p><?php _e('Enables logging of comments (creating, editing, deleting, status changes)','netpeak-logger');?></p>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
        
            <th><?php _e('Email Logger', 'netpeak-logger'); ?></th>
            <td>
                <?php if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
                ?>
                    <label class="switch">
                        <input type="checkbox" class="dependent-checkbox" name="netpeak_email_logger_enabled" value="1"
                            <?php checked(1, get_option('netpeak_email_logger_enabled', 1)); ?> />
                        <span class="slider"></span>
                    </label>
                    <div class="tooltip" style="margin-left:20px;">
                    <span class="tooltip-icon">?</span>
                    <div class="tooltip-content">
                        <img class="tooltip-image" src="<?php echo NETPEAK_LOGGER_URL . '/assets/img/email-logs.png'; ?>" alt="Описание"/>
                        <p><?php _e('Enables logging of Email (Sendler, Recipient, Status, Subject, Message)','netpeak-logger');?></p>
                    </div>
                </div>
                </td>
                <?php
                } else {
                    echo '<p style="color:white">' . __('Contact Form 7 plugin is not active.', 'netpeak-logger') . '</p>';
                }
                ?>
        </tr>
        
    </table>
    <?php submit_button(); ?>
</form>
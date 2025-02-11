<form id="setting-form" method="post" action="">
    <input type="hidden" name="action" value="settings_form_submit"/>
    <input type="hidden" name="settings" value="telegram"/>
    <table class="form-table">
        <tr>
            <th><?php _e('Daily Telegram Report (Email Logs)', 'netpeak-logger'); ?></th>
            <td>
                <label class="switch">
                    <input type="checkbox" class="dependent-checkbox" name="netpeak_daily_telegram_report_enabled" value="0"
                        <?php checked(1, get_option('netpeak_daily_telegram_report_enabled', 0)); ?> />
                    <span class="slider"></span>
                </label>
                <div class="tooltip" style="margin-left:20px;">
                <span class="tooltip-icon">?</span>
                    <div class="tooltip-content">
                        <p><?php _e('Daily report of sent emails (Successful/unsuccessful, total amount)','netpeak-logger');?></p>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th><?php _e('Email issue tracking', 'netpeak-logger'); ?></th>
            <td>
                <label class="switch">
                    <input type="checkbox" class="dependent-checkbox" name="netpeak_check_error_log" value="0"
                        <?php checked(1, get_option('netpeak_check_error_log', 0)); ?> />
                    <span class="slider"></span>
                </label>
                <div class="tooltip" style="margin-left:20px;">
                <span class="tooltip-icon">?</span>
                    <div class="tooltip-content">
                        <p><?php _e('Enables CF7 job tracking - sends a report when an email is unsuccessfully sent','netpeak-logger');?></p>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th><?php _e('Telegram API Token', 'netpeak-logger'); ?></th>
            <td>
                <input type="password" name="netpeak_telegram_bot_token" value="<?php echo esc_attr(get_option('netpeak_telegram_bot_token')); ?>" />
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>

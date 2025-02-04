<form id="setting-form" method="post" action="">
    <input type="hidden" name="action" value="settings_form_submit"/>
    <input type="hidden" name="settings" value="telegram"/>
    <table class="form-table">
        <tr>
            <th><?php _e('Enable Daily Report', 'netpeak-logger'); ?></th>
            <td>
                <label class="switch">
                    <input type="checkbox" class="dependent-checkbox" name="netpeak_daily_report_enabled" value="0"
                        <?php checked(1, get_option('netpeak_daily_report_enabled', 0)); ?> />
                    <span class="slider"></span>
                </label>
            </td>
        </tr>
        <tr>
            <th><?php _e('Enable Error Log Telegram', 'netpeak-logger'); ?></th>
            <td>
                <label class="switch">
                    <input type="checkbox" class="dependent-checkbox" name="netpeak_check_error_log" value="0"
                        <?php checked(1, get_option('netpeak_check_error_log', 0)); ?> />
                    <span class="slider"></span>
                </label>
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

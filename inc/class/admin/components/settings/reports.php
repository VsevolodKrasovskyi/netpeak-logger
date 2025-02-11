<form id="setting-form" method="post" action="">
    <input type="hidden" name="action" value="settings_form_submit"/>
    <input type="hidden" name="settings" value="reports"/>
    <table class="form-table">
        <tr>
            <th><?php _e('Daily Report', 'netpeak-logger'); ?></th>
            <td>
                <label class="switch">
                    <input type="checkbox" class="dependent-checkbox" name="netpeak_daily_email_report_enabled" value="0"
                        <?php checked(1, get_option('netpeak_daily_email_report_enabled', 0)); ?> />
                    <span class="slider"></span>
                </label>
                <div class="tooltip" style="margin-left:20px;">
                <span class="tooltip-icon">?</span>
                    <div class="tooltip-content">
                        <img class="tooltip-image" src="<?php echo esc_url(NETPEAK_LOGGER_URL . 'assets/img/report-email.png'); ?>"/>
                        <p><?php _e('Receive daily log reports to email','netpeak-logger');?></p>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th><?php _e('Report commit', 'netpeak-logger'); ?></th>
                <td>
                    <label class="switch">
                        <input type="checkbox" class="dependent-checkbox" name="netpeak_commit_report_enabled" value="0"
                            <?php checked(1, get_option('netpeak_commit_report_enabled', 0)); ?> />
                        <span class="slider"></span>
                    </label>
                    <div class="tooltip" style="margin-left:20px;">
                    <span class="tooltip-icon">?</span>
                        <div class="tooltip-content">
                            <p><?php _e('Sends an email to the post office if someone has left a commit','netpeak-logger');?></p>
                        </div>
                    </div>
                </td>
            </tr>
        <tr>
            <th><?php _e('Recipients (comma separated)', 'netpeak-logger'); ?></th>
            <td>
                <input type="text" style="width: 550px;" name="netpeak_report_emails" value="<?php echo esc_attr(implode(',', get_option('netpeak_report_emails', []))); ?>" />
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>

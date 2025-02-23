<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}

$subscription_info = Swift3_System::get_subscription_info();
?>
<div class="swift3-settings-tab" data-id="uth-system">
      <div class="swift3-config-box">
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('License', 'swift3');?></h4>
                        <?php if (isset($subscription_info['status']) && in_array($subscription_info['status'], array('active', 'pending-cancel'))):?>
                              <strong class="swift3-info-success"><?php esc_html_e('Your subscription is active', 'swift3');?></strong>
                        <?php else:?>
                              <strong class="swift3-info-failed"><?php esc_html_e('Your subscription is NOT active', 'swift3');?></strong>
                        <?php endif;?>
                        <br><br>
                        <?php if (swift3_check_option('activated', 1)):?>
                              <?php if (isset($subscription_info['subscription_id']) && $subscription_info['subscription_id'] > 0):?>
                                    <?php echo sprintf(esc_html__('Your site is connected to subscription %s.', 'swift3'), $subscription_info['subscription_id']);?>
                              <?php endif;?>

                              <?php if ($subscription_info['status'] == 'active'):?>
                                    <?php esc_html_e('Your subscription is active', 'swift3');?>
                              <?php elseif ($subscription_info['status'] == 'pending-cancel'):?>
                                    <?php echo sprintf(esc_html__('Your subscription is active until %s', 'swift3'), wp_date(get_option('date_format'), $subscription_info['expiry']));?>
                              <?php elseif ($subscription_info['status'] == 'unknown'):?>
                                    <?php echo sprintf(esc_html__('Can not check subscription status (%s)', 'swift3'), $subscription_info['error']);?>
                              <?php elseif ($subscription_info['status'] == 'not-connected'):?>
                                    <?php esc_html_e('Your site is connected, but subscription does not exists. Please disconnect site and reconnect to a valid subscription', 'swift3');?>
                              <?php else:?>
                                    <strong><?php echo sprintf(esc_html__('Your subscription is not active.', 'swift3'), $subscription_info['subscription_id']);?></strong>
                              <?php endif;?><br>

                              <?php if ($subscription_info['status'] != 'not-connected'):?>
                                    <?php esc_html_e('If you would like to change subscription, you need to disconnect your site.', 'swift3');?>
                              <?php endif;?>
                        <?php else:?>
                              <?php esc_html_e('In order to be able to use Swift Performance AI you need to active your license.', 'swift3');?>
                        <?php endif;?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <?php if (swift3_check_option('activated', 1)):?>
                              <a class="swift3-btn" href="#" data-swift3="disconnect-license"><?php esc_html_e('Disconnect site', 'swift3');?></a>
                        <?php else:?>
                              <a class="swift3-btn" href="<?php echo esc_url(add_query_arg(array('setup' => 'install'), menu_page_url('swift3', false)));?>"><?php esc_html_e('Connect site', 'swift3');?></a>
                        <?php endif;?>
                  </div>
            </div>

            <?php if (Swift3_Config::server_software() == 'apache'):?>
            <?php
                  $htaccess_path = Swift3_Helper::get_home_path() . '.htaccess';
                  if (!file_exists($htaccess_path) || !is_writable($htaccess_path)){
                        $check_htaccess_rewrites = 0;
                  }
                  else {
                        $check_htaccess_rewrites = 1;
                  }
            ?>
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Rewrite Rules', 'swift3');?></h4>
                        <?php if ($check_htaccess_rewrites == 1):?>
                              <?php esc_html_e('Htaccess rules are working, you don\'t need to do anything with them.', 'swift3');?>
                        <?php else:?>
                              <?php esc_html_e('Htaccess is not writable, you have to add rules manually.', 'swift3');?>
                              <?php echo sprintf(esc_html__('%sClick here%s to copy the generated rewrite rules to the clipboard', 'swift3'), '<a href="#" class="swift3-copy" data-target="swift3-apache-rules" data-message="' . esc_html__('Rules has been copied to clipboard.', 'swift3') . '">', '</a>');?><br><br>
                              <?php echo sprintf(esc_html__('See the documentation for further help:  %sHow to add htaccess rewrites%s', 'swift3'), '<a href="https://go.swiftperformance.io/documentation/htaccess-rules" target="_blank">', '</a>');?>
                        <?php endif;?>
                        <textarea id="swift3-apache-rules" class="swift3-hidden"><?php echo esc_textarea(Swift3_Helper::print_template('rewrites/htaccess'));?></textarea>
                  </div>
                  <div class="swift3-settings-wrapper-inner swift3-dir-col">
                        <?php if ($check_htaccess_rewrites == 1):?>
                              <span class="swift3-icon-ok dashicons dashicons-yes-alt"></span>
                              <span><?php esc_html_e('Rewrite rules are working.', 'swift3')?></span>
                        <?php else:?>
                              <span class="swift3-icon-fail dashicons dashicons-dismiss"></span>
                              <span><?php esc_html_e('Htaccess is not writable.', 'swift3')?></span>
                        <?php endif;?>
                  </div>
            </div>
            <?php endif;?>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Admin Toolbar', 'swift3');?></h4>
                        <?php esc_html_e('You can choose where the Swift Performance admin toolbar appears, or you can hide it everywhere.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner swift3-nowrap">
                        <select name="adminbar" class="swift3-auto-trigger">
                              <option value="hide"<?php Swift3_Helper::maybe_selected('hide', swift3_get_option('adminbar'));?>><?php esc_html_e('Hide everywhere', 'swift3');?></option>
                              <option value="frontend"<?php Swift3_Helper::maybe_selected('frontend', swift3_get_option('adminbar'));?>><?php esc_html_e('Show frontend only', 'swift3');?></option>
                              <option value="backend"<?php Swift3_Helper::maybe_selected('backend', swift3_get_option('adminbar'));?>><?php esc_html_e('Show backend only', 'swift3');?></option>
                              <option value="everywhere"<?php Swift3_Helper::maybe_selected('everywhere', swift3_get_option('adminbar'));?>><?php esc_html_e('Show everywhere', 'swift3');?></option>
                        </select>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Installer', 'swift3');?></h4>
                        <?php esc_html_e('With installer you can automatically reconfigure Swift Performance.', 'swift3');?>
                        <strong><?php esc_html_e('Please note that your current settings will be lost!', 'swift3');?></strong>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <a class="swift3-btn" href="<?php echo esc_url(add_query_arg(array('setup' => 'install', 'reset' => 1), menu_page_url('swift3', false)));?>"><?php esc_html_e('Start Installer', 'swift3');?></a>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('API Connection', 'swift3');?></h4>
                        <?php if (swift3_check_option('api_connection', 'duplex')):?>
                              <strong class="swift3-info-success"><?php esc_html_e('API connection is working properly.', 'swift3');?></strong>
                        <?php elseif (swift3_check_option('api_connection', 'simplex')):?>
                              <strong class="swift3-info-warning"><?php esc_html_e('API connection is simplex.', 'swift3');?></strong><br><br>
                              <?php esc_html_e('Your server can reach the API, but API can not reach your server. If it is a localhost environment, then it is normal behavior. Otherwise you may need change firewall settings.', 'swift3');?>
                        <?php else:?>
                              <strong class="swift3-info-failed"><?php esc_html_e('API connection failed.', 'swift3');?></strong><br><br>
                              <?php esc_html_e('API connection is not working. Please check network connection and firewall settings.', 'swift3');?>
                        <?php endif;?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <a class="swift3-btn" href="#" data-swift3="reconnect-api"><?php esc_html_e('Reconnect API', 'swift3');?></a>
                  </div>
            </div>

      </div>
</div>
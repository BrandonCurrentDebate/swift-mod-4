<div class="swift3-settings-tab swift3-hidden" data-id="int-cloudflare">
      <div class="swift3-config-box">
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Enable Cloudflare Module', 'swift3');?></h4>
                        <?php esc_html_e('Automatically purge Cloudflare cache when it is necessary.', 'swift3');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-enable-cloudflare" name="enable-cloudflare" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('enable-cloudflare'), 'on');?>><label for="swift3-enable-cloudflare"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Authentication mode', 'swift3');?></h4>
                        <?php esc_html_e('Choose authentication mode.', 'swift3');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <div class="swift3-radio-set">
                              <input type="radio" class="swift3-auto-trigger" id="swift3-cloudflare-auth-token" name="cloudflare-auth-mode" value="token"<?php Swift3_Helper::maybe_checked(swift3_get_option('cloudflare-auth-mode'), 'token');?>>
                              <label for="swift3-cloudflare-auth-token"><span><?php esc_html_e('Token', 'swift3');?></span></label>
                              <input type="radio" class="swift3-auto-trigger" id="swift3-cloudflare-auth-key" name="cloudflare-auth-mode" value="key"<?php Swift3_Helper::maybe_checked(swift3_get_option('cloudflare-auth-mode'), 'key');?>>
                              <label for="swift3-cloudflare-auth-key"><span><?php esc_html_e('API Key', 'swift3');?></span></label>
                        </div>
                  </div>
            </div>

            <div data-depend-on="cloudflare-auth-mode" data-depend-value="token">
                  <div class="swift3-settings-wrapper swift3-fullwidth">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Cloudflare token', 'swift3');?></h4>
                              <?php esc_html_e('Create a Cloudflare token for WordPress and paste secret key here.', 'swift3');?>
                              <?php echo sprintf(esc_html__('%sSee more about Cloudflare tokens %s.', 'swift3'), '<a href="https://go.swiftperformance.io/documentation/cloudflare-token" target="_blank">', '</a>');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <div class="swift3-password-wrapper">
                                    <input type="text" class="swift3-manual-trigger" name="cloudflare-token" value="<?php echo esc_attr(swift3_get_option('cloudflare-token'));?>">
                                    <span class="swift3-password-overlay"></span>
                              </div>
                              <a href="#" class="swift3-save-trigger swift3-btn swift3-btn-light swift3-disabled" data-for="cloudflare-token">Update changes</a>
                        </div>
                  </div>
            </div>

            <div data-depend-on="cloudflare-auth-mode" data-depend-value="key" data-maxheight="double">
                  <div class="swift3-settings-wrapper swift3-fullwidth">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Cloudflare E-mail', 'swift3');?></h4>
                              <?php esc_html_e('E-mail address what you are using for your Cloudflare account.', 'swift3');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <input type="text" class="swift3-manual-trigger" name="cloudflare-email" value="<?php echo esc_attr(swift3_get_option('cloudflare-email'));?>">
                              <a href="#" class="swift3-save-trigger swift3-btn swift3-btn-light swift3-disabled" data-for="cloudflare-email">Update changes</a>
                        </div>
                  </div>

                  <div class="swift3-settings-wrapper swift3-fullwidth">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Cloudflare API Key', 'swift3');?></h4>
                              <?php esc_html_e('Enter Cloudflare API Key secret key here.', 'swift3');?>
                              <?php echo sprintf(esc_html__('%sSee more about Cloudflare API key %s.', 'swift3'), '<a href="https://go.swiftperformance.io/documentation/cloudflare-api-key" target="_blank">', '</a>');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <div class="swift3-password-wrapper">
                                    <input type="text" class="swift3-manual-trigger" name="cloudflare-key" value="<?php echo esc_attr(swift3_get_option('cloudflare-key'));?>">
                                    <span class="swift3-password-overlay"></span>
                              </div>
                              <a href="#" class="swift3-save-trigger swift3-btn swift3-btn-light swift3-disabled" data-for="cloudflare-key">Update changes</a>
                        </div>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Enable Cloudflare Proxy Cache', 'swift3');?></h4>
                        <?php echo sprintf(esc_html__('Cloudflare Proxy Cache is a free alternative for Cloudflare APO. If you create a token as described %shere%s Swift Performance will automatically configure everything. Otherwise you need to use page rules. %sSee more about page rules%s', 'swift3'), '<a href="https://go.swiftperformance.io/documentation/cloudflare-token" target="_blank">', '</a>', '<a href="https://go.swiftperformance.io/documentation/cloudflare-proxy-cache" target="_blank">', '</a>');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-cloudflare-proxy-cache" name="cloudflare-proxy-cache" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('cloudflare-proxy-cache'), 'on');?>><label for="swift3-cloudflare-proxy-cache"></label>
                  </div>
            </div>
      </div>
</div>
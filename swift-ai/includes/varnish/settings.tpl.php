<div class="swift3-settings-tab swift3-hidden" data-id="int-varnish">
      <div class="swift3-config-box">
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Enable Varnish Module', 'swift3');?></h4>
                        <?php esc_html_e('Automatically purge varnish cache when it is necessary.', 'swift3');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-enable-varnish" name="enable-varnish" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('enable-varnish'), 'on');?>><label for="swift3-enable-varnish"></label>
                  </div>
            </div>
      </div>
</div>
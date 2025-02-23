<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<div class="swift3-settings-tab" data-id="uth-core">
      <div class="swift3-config-box">
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Cache HTTP API requests', 'swift3');?></h4>
                        <?php esc_html_e('With this feature Swift Performance will cache HTTP API requests for 5 minutes.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-http-request-cache" name="http-request-cache" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('http-request-cache'), 'on');?>><label for="swift3-http-request-cache"></label>
                  </div>
            </div>
      </div>
</div>
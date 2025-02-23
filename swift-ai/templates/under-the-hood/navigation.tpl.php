<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<div class="swift3-settings-tab" data-id="uth-navigation">
      <div class="swift3-config-box">
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Speed up onsite navigation', 'swift3');?></h4>
                        <?php esc_html_e('With this feature Swift Performance will use smart preload technique to speed up the onsite navigation.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-onsite-navigation" name="onsite-navigation" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('onsite-navigation'), 'on');?>><label for="swift3-onsite-navigation"></label>
                  </div>
            </div>

            <div data-depend-on="onsite-navigation" data-depend-value="on">
                  <div class="swift3-settings-wrapper">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Ignored Prefetch URLs', 'swift3');?></h4>
                              <?php esc_html_e('All URLs listed here will be excluded from prefetching during navigation. Write one URL per line.', 'swift3');?>
                        </div>

                        <div class="swift3-settings-wrapper-inner">
                              <textarea name="ignored-prefetch-urls" class="swift3-manual-trigger"><?php echo esc_textarea(swift3_get_option('ignored-prefetch-urls'));?></textarea>
                              <a href="#" class="swift3-save-trigger swift3-btn swift3-btn-light swift3-disabled" data-for="ignored-prefetch-urls">Update changes</a>
                        </div>
                  </div>
            </div>

      </div>
</div>
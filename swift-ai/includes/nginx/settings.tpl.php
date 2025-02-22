<?php
      $check_rewrites = Swift3_Nginx_Module::check_rewrites();
?>
<div class="swift3-settings-tab swift3-hidden" data-id="int-nginx">
      <div class="swift3-config-box">
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Rewrite Rules', 'swift3');?></h4>
                        <?php esc_html_e('Add rewrite rules to your nginx config file.', 'swift3');?>
                        <?php echo sprintf(esc_html__('%sClick here%s to copy the generated rewrite rules to the clipboard', 'swift3'), '<a href="#" class="swift3-copy" data-target="swift3-nginx-rules" data-message="' . esc_html__('Rules has been copied to clipboard.', 'swift3') . '">', '</a>');?><br><br>
                        <?php echo sprintf(esc_html__('See the documentation for further help:  %sHow to add Nginx rewrites%s', 'swift3'), '<a href="https://go.swiftperformance.io/documentation/nginx-rules" target="_blank">', '</a>');?>
                        <textarea id="swift3-nginx-rules" class="swift3-hidden"><?php echo esc_textarea(Swift3_Nginx_Module::get_rewrites());?></textarea>
                  </div>
                  <div class="swift3-settings-wrapper-inner swift3-dir-col">
                        <?php if ($check_rewrites == -1):?>
                              <span class="swift3-icon-softfail dashicons dashicons-dismiss"></span>
                              <span><?php esc_html_e('Nginx rules are working, but need to be updated.', 'swift3')?></span>
                        <?php elseif ($check_rewrites == 1):?>
                              <span class="swift3-icon-ok dashicons dashicons-yes-alt"></span>
                              <span><?php esc_html_e('Nginx rules are working.', 'swift3')?></span>
                        <?php else:?>
                              <span class="swift3-icon-fail dashicons dashicons-dismiss"></span>
                              <span><?php esc_html_e('Nginx rules are NOT working. Please add rules to config and reload Nginx.', 'swift3')?></span>
                        <?php endif;?>
                  </div>
            </div>
      </div>
</div>
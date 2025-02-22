<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<div class="swift3-dashboard-section swift3-hidden" data-id="support">
      <div class="swift3-row">
            <div class="swift3-col swift3-col-50">
                  <h3><?php esc_html_e('Open a ticket', 'swift3');?></h3>
                  <form id="swift3-support-form" method="post">
                        <textarea name="question" placeholder="<?php esc_attr_e('Please describe your issue', 'swift3');?>"></textarea>
                        <label><input type="checkbox" name="consent" value="on"> <?php esc_html_e('I agree that support can change Swift Performance settings remotely', 'swift3')?></label>
                        <button class="swift3-btn swift3-self-end"><?php esc_html_e('Submit','swift3');?></button>
                  </form>
            </div>
            <div class="swift3-col swift3-col-30">
                  <h3><?php esc_html_e('Check resources', 'swift3');?></h3>
                  <ul class="swift3-resource-list">
                        <li><a href="https://go.swiftperformance.io/documentation/" target="_blank"><img src="<?php echo SWIFT3_URL?>assets/images/icons/documentation.png" width="30" height="30"> <?php esc_html_e('Check documentation', 'swift3');?></a></li>
                        <li><a href="https://go.swiftperformance.io/blog/" target="_blank"><img src="<?php echo SWIFT3_URL?>assets/images/icons/blog.png" width="30" height="30"> <?php esc_html_e('Check Swift Blog', 'swift3');?></a></li>
                        <li><a href="https://go.swiftperformance.io/fbgroup/" target="_blank"><img src="<?php echo SWIFT3_URL?>assets/images/icons/fb.png" width="30" height="30"> <?php esc_html_e('Ask community', 'swift3');?></a></li>
                  </ul>
            </div>
      </div>
</div>
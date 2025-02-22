<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<div id="swift3-header">
      <?php Swift3_Helper::print_svg('ai-background-static');?>
      <img id="swift3-logo-ai" src="<?php echo SWIFT3_URL?>assets/images/logo.png">
      <h1 id="swift3-heading"><?php esc_html_e('The most innovative WordPress speed up plugin', 'swift3');?></h1>
</div>
<div id="swift3-dashboard-wrap">
      <div id="swift3-notice-wrap">
            <?php Swift3_Logger::print_messages();?>
      </div>
<?php
      Swift3_Helper::print_template('panel');
      Swift3_Helper::print_template('config');
      Swift3_Helper::print_template('support');
?>
</div>
<div class="swift3-toast-container"></div>
<div class="swift3-toast-icons-container">
      <div class="swift3-toast-icon-error">
            <?php Swift3_Helper::print_svg('icons/error');?>
      </div>
      <div class="swift3-toast-icon-info">
            <?php Swift3_Helper::print_svg('icons/info');?>
      </div>
      <div class="swift3-toast-icon-success">
            <?php Swift3_Helper::print_svg('icons/success');?>
      </div>
</div>
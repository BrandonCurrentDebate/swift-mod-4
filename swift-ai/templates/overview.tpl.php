<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>

<div class="swift3-dashboard-section-heading">
      <h2><?php esc_html_e('Overview', 'swift3')?></h2>
      <a href="#" data-swift3="clear-cache" class="swift3-btn"><?php esc_html_e('Clear cache','swift3');?></a>
</div>
<div id="swift3-status-wrap">
      <div id="swift3-status-container">
            <div class="swift3-status-box swift3-status-box-cache">
                  <div class="swift3-status-box-details">
                        <?php if (swift3_check_option('caching', 'on')):?>
                        <h3><?php esc_html_e('Cache', 'swift3');?></h3>
                        <?php else:?>
                        <h3><?php esc_html_e('Preload', 'swift3');?></h3>
                        <?php endif;?>
                        <span class="swift3-cache-info"><?php echo esc_html(Swift3_Dashboard::$status['cache']['info']);?></span>
                  </div>
                  <div class="swift3-status-box-value swift3-cache-percentage">
                        <?php echo esc_html(Swift3_Dashboard::$status['cache']['percentage']);?>%
                  </div>
            </div>
            <div class="swift3-status-box swift3-status-box-main-thread">
                  <div class="swift3-status-box-details">
                        <h3><?php esc_html_e('Main Optimization Thread', 'swift3');?></h3>
                        <span><?php esc_html_e('Optimize most important resources', 'swift3');?></span>
                  </div>
                  <div class="swift3-status-box-value swift3-main-thread-percentage">
                        <?php echo esc_html(Swift3_Dashboard::$status['main-thread']);?>%
                  </div>
            </div>
            <div class="swift3-status-box swift3-status-box-fine-tuning">
                  <div class="swift3-status-box-details">
                        <h3><?php esc_html_e('Fine Tuning', 'swift3');?></h3>
                        <span><?php esc_html_e('Optimize less important resources', 'swift3');?></span>
                  </div>
                  <div class="swift3-status-box-value swift3-fine-tuning-percentage">
                        <?php echo esc_html(Swift3_Dashboard::$status['fine-tuning']);?>%
                  </div>
            </div>
            <div class="swift3-status-box">
                  <div class="swift3-status-box-details">
                        <h3><?php esc_html_e('Image Optimization', 'swift3');?></h3>
                        <span><?php esc_html_e('Shrink images', 'swift3');?></span>
                  </div>
                  <div class="swift3-status-box-value swift3-image-info">
                        <?php echo esc_html(Swift3_Dashboard::$status['images']);?>
                  </div>
            </div>
      </div>
      <div id="swift3-status-message-container">
            <?php foreach (Swift3_Dashboard::get_messages() as $message):?>
                  <?php Swift3_Helper::print_template('message', 'tpl', $message);?>
            <?php endforeach;?>
      </div>
</div>

<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}

$status = Swift3_Dashboard::get_status();
?>

<div class="swift3-mini-status-container">
      <div class="swift3-toolbar-title"><?php esc_html_e('Status','swift3');?></div>
      <ul>
            <li class="swift3-toolbar-flex">
                  <?php esc_html_e('Cache', 'swift3');?>
                  <span class="swift3-cache-percentage"><?php echo esc_html(Swift3_Dashboard::$status['cache']['percentage']);?>%</span>
            </li>
            <li class="swift3-toolbar-flex">
                  <?php esc_html_e('Main Thread', 'swift3');?>
                  <span class="swift3-main-thread-percentage"><?php echo esc_html(Swift3_Dashboard::$status['main-thread']);?>%</span>
            </li>
            <li class="swift3-toolbar-flex">
                  <?php esc_html_e('Fine Tuning', 'swift3');?>
                  <span class="swift3-fine-tuning-percentage"><?php echo esc_html(Swift3_Dashboard::$status['fine-tuning']);?>%</span>
            </li>
            <?php if (swift3_check_option('optimize-images', 'on')):?>
            <li class="swift3-toolbar-flex">
                  <?php esc_html_e('Images', 'swift3');?>
                  <span class="swift3-image-info"><?php echo esc_html(Swift3_Dashboard::$status['images']);?></span>
            </li>
            <?php endif;?>
      </ul>
      <div class="swift3-toolbar-title"><?php esc_html_e('Actions','swift3');?></div>
      <ul class="swift3-toolbar-actions">
            <?php if ($status < 100): ?>
                  <li><a href="#" data-swift3="check-status"><?php esc_html_e('Check status', 'swift3');?></a></li>
            <?php endif;?>
            <li><a href="#" data-swift3="clear-all-cache"><?php esc_html_e('Clear all cache', 'swift3');?></a></li>
      </ul>
</div>
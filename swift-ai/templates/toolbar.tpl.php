<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}

$url = Swift3_Helper::get_current_url();

$is_cacheable = apply_filters('swift3_toolbar_is_page_cacheable', Swift3::get_module('cache')->is_page_cacheable('<html>dummy</html>'));
$is_cached = $is_optimized = $has_queued = false;

if ($is_cacheable){
      $warmup_data = Swift3_Warmup::get_data($url);

      $is_cached              = (!empty($warmup_data) && in_array($warmup_data->status, array(-2, -3, 1, 2, 3)));
      $is_optimized           = (!empty($warmup_data) && $warmup_data->status == 3) && swift3_check_option('api_connection', 'duplex');
      $has_queued             = Swift3_Image::has_queued($url);

      $percentage = (!$has_queued ? 10 : 0) + ($is_cached ? 30 : 0) + ($is_optimized ? 60 : 0);
}
else {
      $notes = Swift3::get_module('cache')->get_notes('is_page_cacheable');
}
?>

<div class="swift3-mini-status-container">
      <div class="swift3-toolbar-title"><?php esc_html_e('Status','swift3');?></div>
      <ul class="swift3-toolbar-grid">
      <?php if ($is_cacheable):?>
            <li>
                  <label><?php esc_html_e('Cache','swift3');?></label>
                  <?php if ($is_cached):?>
                        <span class="swift3-status-indicator">&#10003;</span>
                  <?php else:?>
                        <img src="<?php echo SWIFT3_URL?>assets/images/loading-g.svg" class="swift3-progress">
                  <?php endif;?>
            </li>
      <?php endif;?>
      <?php if (swift3_check_option('optimize-css', 0, '>') && swift3_check_option('api_connection', 'duplex')):?>
            <li>
                  <label><?php esc_html_e('Critical CSS','swift3');?></label>
                  <?php if ($is_optimized):?>
                        <span class="swift3-status-indicator">&#10003;</span>
                  <?php else:?>
                        <img src="<?php echo SWIFT3_URL?>assets/images/loading-g.svg" class="swift3-progress">
                  <?php endif;?>
            </li>
      <?php endif;?>
      <?php if (swift3_check_option('optimize-css', 1, '>') && swift3_check_option('api_connection', 'duplex')):?>
            <li>
                  <label><?php esc_html_e('Optimize Fonts','swift3');?></label>
                  <?php if ($is_optimized):?>
                        <span class="swift3-status-indicator">&#10003;</span>
                  <?php else:?>
                        <img src="<?php echo SWIFT3_URL?>assets/images/loading-g.svg" class="swift3-progress">
                  <?php endif;?>
            </li>
      <?php endif;?>
      <?php if (swift3_check_option('optimize-js', 'on') || swift3_check_option('js-delivery', 0, '>') ):?>
            <li>
                  <label><?php esc_html_e('Optimize Scripts','swift3');?></label>
                  <?php if (swift3_check_option('api_connection', 'simplex') || swift3_check_option('js-delivery', 2, '!=') || (swift3_check_option('js-delivery', 2) && $is_optimized)):?>
                        <span class="swift3-status-indicator">&#10003;</span>
                  <?php elseif (swift3_check_option('js-delivery', 2)):?>
                        <span class="swift3-status-indicator">&frac12;</span>
                  <?php endif;?>
            </li>
      <?php endif;?>
      <?php if (swift3_check_option('optimize-images', 'on')):?>
            <li>
                  <label><?php esc_html_e('Optimize Images','swift3');?></label>
                  <?php if (!$has_queued):?>
                        <span class="swift3-status-indicator">&#10003;</span>
                  <?php else:?>
                        <img src="<?php echo SWIFT3_URL?>assets/images/loading-g.svg" class="swift3-progress">
                  <?php endif;?>
            </li>
      <?php endif;?>
      <?php if (swift3_check_option('optimize-iframes', 0, '>') && swift3_check_option('api_connection', 'duplex')):?>
            <li>
                  <label><?php esc_html_e('Optimize Iframes','swift3');?></label>
                  <?php if (swift3_check_option('optimize-iframes', 1) || $is_optimized):?>
                        <span class="swift3-status-indicator">&#10003;</span>
                  <?php else:?>
                        <img src="<?php echo SWIFT3_URL?>assets/images/loading-g.svg" class="swift3-progress">
                  <?php endif;?>
            </li>
      <?php endif;?>
      <?php if (swift3_check_option('optimize-rendering', 'on') && swift3_check_option('api_connection', 'duplex')):?>
            <li>
                  <label><?php esc_html_e('Optimize LCP','swift3');?></label>
                  <?php if ($is_optimized):?>
                        <span class="swift3-status-indicator">&#10003;</span>
                  <?php else:?>
                        <img src="<?php echo SWIFT3_URL?>assets/images/loading-g.svg" class="swift3-progress">
                  <?php endif;?>
            </li>
      <?php endif;?>
      <?php if (swift3_check_option('optimize-cls', 'on') && swift3_check_option('api_connection', 'duplex')):?>
            <li>
                  <label>Reduce CLS</label>
                  <?php if ($is_optimized):?>
                        <span class="swift3-status-indicator">&#10003;</span>
                  <?php else:?>
                        <img src="<?php echo SWIFT3_URL?>assets/images/loading-g.svg" class="swift3-progress">
                  <?php endif;?>
            </li>
      <?php endif;?>
      </ul>

      <div class="swift3-toolbar-title"><?php esc_html_e('Actions','swift3');?></div>
      <?php if(!$is_cacheable):?>
            <?php esc_html_e('This page is not cacheable', 'swift3');?>
            <?php if (!empty($notes)):?>
                  (<?php echo implode(', ', $notes);?>)
            <?php endif;?>
      <?php else:?>
      <ul class="swift3-toolbar-actions swift3-toolbar-grid">
            <li><a href="#" data-swift3="check-status" data-url="<?php echo esc_attr($url);?>"><?php esc_html_e('Check status', 'swift3');?></a></li>
      <?php if (!$is_cached):?>
            <li><a href="#" data-swift3="optimize-single" data-url="<?php echo esc_attr($url);?>"><?php esc_html_e('Cache now', 'swift3');?></a></li>
      <?php elseif ($is_cached && !$is_optimized):?>
            <li><a href="#" data-swift3="optimize-single" data-url="<?php echo esc_attr($url);?>"><?php esc_html_e('Optimize now', 'swift3');?></a></li>
      <?php endif;?>
      <?php if ($is_cached):?>
            <li><a href="#" data-swift3="clear-cache-single" data-url="<?php echo esc_attr($url);?>"><?php esc_html_e('Clear cache', 'swift3');?></a></li>
            <li><a href="#" data-swift3="purge-cache-single" data-url="<?php echo esc_attr($url);?>"><?php esc_html_e('Purge cache', 'swift3');?></a></li>
      <?php endif;?>
            <li><a href="<?php echo esc_url(add_query_arg('nocache', mt_rand(0,PHP_INT_MAX), $url))?>" target="_blank"><?php esc_html_e('Unoptimized version', 'swift3');?></a></li>
      </ul>
      <?php endif;?>
</div>
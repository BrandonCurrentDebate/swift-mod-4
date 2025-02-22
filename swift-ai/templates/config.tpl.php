<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<div class="swift3-dashboard-section swift3-hidden" data-id="configuration">
      <div id="swift3-general-settings">
            <div class="swift3-dashboard-section-heading">
                  <h2><?php esc_html_e('Configuration', 'swift3')?></h2>
                  <a href="#under-the-hood" class="swift3-dashboard-nav swift3-btn"><?php esc_html_e('Under the hood','swift3');?></a>
            </div>
            <div class="swift3-config-box">
                  <div class="swift3-settings-wrapper">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Cache', 'swift3');?></h4>
                              <?php esc_html_e('Generate static HTML version for pages. If you are using a server level caching you may won\'t need this feature, however for the best compatibility and results, it is recommended to use Swift Performance built in caching', 'swift3');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-caching" name="caching" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('caching'), 'on');?>><label for="swift3-caching"></label>
                        </div>
                  </div>

                  <div class="swift3-settings-wrapper">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Optimize CSS', 'swift3');?></h4>
                              <?php esc_html_e('Optimize CSS and font delivery. It is highly recommeded to use this feature for the best results.', 'swift3');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <input type="range" class="swift3-auto-trigger swift3-range" name="optimize-css" min="0" max="2" value="<?php echo esc_attr(swift3_get_option('optimize-css'));?>">
                        </div>
                  </div>

                  <div class="swift3-settings-wrapper">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Optimize JS', 'swift3');?></h4>
                              <?php esc_html_e('Optimize javascript parsing and executing on every page.', 'swift3');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-optimize-js" name="optimize-js" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('optimize-js'), 'on');?>><label for="swift3-optimize-js"></label>
                        </div>
                  </div>

                  <div class="swift3-settings-wrapper">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('JS Delivery', 'swift3');?></h4>
                              <?php esc_html_e('Automatically optimize javascript delivery for better performance on cached pages.', 'swift3');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <input type="range" class="swift3-auto-trigger swift3-range" name="js-delivery" min="0" max="2" value="<?php echo esc_attr(swift3_get_option('js-delivery'));?>">
                        </div>
                  </div>


                  <div class="swift3-settings-wrapper">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Optimize Images', 'swift3');?></h4>
                              <?php esc_html_e('Optimize, and generate WebP version for all images on demand.', 'swift3');?>
                              <div class="swift3-button-wrapper">
                                    <a href="#" class="swift3-btn swift3-btn-success swift3-btn-s" data-swift3="delete-images"><?php esc_html_e('Delete Optimized Images', 'swift3');?></a>
                              </div>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-optimize-images" name="optimize-images" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('optimize-images'), 'on');?>><label for="swift3-optimize-images"></label>
                        </div>
                  </div>

                  <div class="swift3-settings-wrapper">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Optimize Iframes', 'swift3');?></h4>
                              <?php esc_html_e('Optimize embedded videos, maps or any other iframe.', 'swift3');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <input type="range" class="swift3-auto-trigger swift3-range" name="optimize-iframes" min="0" max="2" value="<?php echo esc_attr(swift3_get_option('optimize-iframes'));?>">
                        </div>
                  </div>

                  <div class="swift3-settings-wrapper">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Code Optimizer', 'swift3');?> <?php esc_html_e('(experimental)', 'swift3');?></h4>
                              <?php esc_html_e('Automatically deactivate unnecessary plugins specifically for each respective request using intelligent rules, and apply other small code improvements to speed up WP Admin, AJAX requests and frontend.', 'swift3');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-code-optimizer" name="code-optimizer" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('code-optimizer'), 'on');?>><label for="swift3-code-optimizer"></label>
                        </div>
                  </div>

                  <div class="swift3-settings-wrapper">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Development Mode', 'swift3');?></h4>
                              <?php esc_html_e('Development Mode temporarily suspends caching and optimization features for two hours (unless disabled beforehand).', 'swift3');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-development-mode" name="development-mode" value="on"<?php echo (swift3_check_option('development-mode', time(), '>') ? ' checked' : '');?>><label for="swift3-development-mode"></label>
                        </div>
                  </div>
            </div>
      </div>

      <div id="swift3-advanced-settings">
            <div class="swift3-dashboard-section-heading">
                  <h2><?php esc_html_e('Under the hood', 'swift3')?></h2>
                  <div class="swift3-advanced-settings-buttons">
                        <a href="#configuration" class="swift3-dashboard-nav swift3-btn"><?php esc_html_e('General settings','swift3');?></a>
                        <a href="#" class="swift3-btn swift3-btn-light" data-swift3="purge-cache"><?php esc_html_e('Purge Cache', 'swift3');?></a>
                        <a href="#" class="swift3-btn swift3-btn-light" data-swift3="reset-cache"><?php esc_html_e('Reset Cache', 'swift3');?></a>
                  </div>
            </div>

            <ul class="swift3-advanced-settings-menu">
                  <li><a href="#uth-caching" class="swift3-dashboard-nav active"><?php esc_html_e('Caching', 'swift3');?></a></li>
                  <li><a href="#uth-media" class="swift3-dashboard-nav active"><?php esc_html_e('Media', 'swift3');?></a></li>
                  <li><a href="#uth-fragments" class="swift3-dashboard-nav active"><?php esc_html_e('Fragments', 'swift3');?></a></li>
                  <li><a href="#uth-navigation" class="swift3-dashboard-nav"><?php esc_html_e('Navigation', 'swift3');?></a></li>
                  <li><a href="#uth-core" class="swift3-dashboard-nav"><?php esc_html_e('Core', 'swift3');?></a></li>
                  <li><a href="#uth-system" class="swift3-dashboard-nav"><?php esc_html_e('System', 'swift3');?></a></li>
            </ul>
            <div class="swift3-advanced-settings-tabs">
                  <?php Swift3_Helper::print_template('under-the-hood/cache');?>
                  <?php Swift3_Helper::print_template('under-the-hood/media');?>
                  <?php Swift3_Helper::print_template('under-the-hood/fragments');?>
                  <?php Swift3_Helper::print_template('under-the-hood/navigation');?>
                  <?php Swift3_Helper::print_template('under-the-hood/core');?>
                  <?php Swift3_Helper::print_template('under-the-hood/system');?>
            </div>
      </div>
</div>

<div class="swift3-dashboard-section swift3-hidden" data-id="integrations">
      <div class="swift3-dashboard-section-heading">
            <h2><?php esc_html_e('Integrations', 'swift3')?></h2>
      </div>

      <ul class="swift3-advanced-settings-menu">
            <?php foreach (Swift3_Config::$integrations as $key => $data):?>
                  <li><a href="#int-<?php echo esc_attr($key);?>" class="swift3-dashboard-nav active"><?php echo esc_html($data['title']);?></a></li>
            <?php endforeach;?>
      </ul>
      <div class="swift3-advanced-settings-tabs">
            <?php foreach (Swift3_Config::$integrations as $key => $data):?>
                  <?php call_user_func($data['callback']);?>
            <?php endforeach;?>
      </div>


</div>
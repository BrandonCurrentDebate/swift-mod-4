<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<div class="swift3-settings-tab" data-id="uth-media">
      <div class="swift3-config-box">
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Optimize images on upload', 'swift3');?></h4>
                        <?php esc_html_e('If you enable this feature, Swift Performance will optimize every image, and generated thumbnails during the upload. If you keep it disabled, then images will be optimized on demand only.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-optimize-images-on-upload" name="optimize-images-on-upload" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('optimize-images-on-upload'), 'on');?>><label for="swift3-optimize-images-on-upload"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Lazyload Images', 'swift3');?></h4>
                        <?php esc_html_e('Improve native image lazyloading for better user experience.', 'swift3');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-lazyload-images" name="lazyload-images" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('lazyload-images'), 'on');?>><label for="swift3-lazyload-images"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Smart Image Sizing', 'swift3');?></h4>
                        <?php esc_html_e('Automatically resize all images on demand, add missing images size attributes and use the right size for every images.', 'swift3');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-responsive-images" name="responsive-images" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('responsive-images'), 'on');?>><label for="swift3-responsive-images"></label>
                  </div>
            </div>

            <div data-depend-on="responsive-images" data-depend-value="on">
                  <div class="swift3-settings-wrapper">
                        <div class="swift3-settings-description">
                              <h4><?php esc_html_e('Enforce Image Sizes', 'swift3');?></h4>
                              <?php esc_html_e('In some cases, the image size is not set via CSS, but by using the original image size for the layout. If Smart Image Resizing is enabled in these situations, the layout can break. This feature enforces the use of appropriate image sizes. However, you should only enable it if you encounter layout issues without it.', 'swift3');?>
                        </div>
                        <div class="swift3-settings-wrapper-inner">
                              <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-enforce-image-size" name="enforce-image-size" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('enforce-image-size'), 'on');?>><label for="swift3-enforce-image-size"></label>
                        </div>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Optimize Rendering', 'swift3');?></h4>
                        <?php esc_html_e('Automatically optimize first contentful paint (FCP) and largest contentful paint (LCP).', 'swift3');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-optimize-rendering" name="optimize-rendering" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('optimize-rendering'), 'on');?>><label for="swift3-optimize-rendering"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Optimize CLS', 'swift3');?></h4>
                        <?php esc_html_e('Automatically optimize Cumulative Layout Shift (CLS).', 'swift3');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-optimize-cls" name="optimize-cls" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('optimize-cls'), 'on');?>><label for="swift3-optimize-cls"></label>
                  </div>
            </div>

      </div>
</div>
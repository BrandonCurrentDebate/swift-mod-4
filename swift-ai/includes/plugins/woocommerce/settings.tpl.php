<div class="swift3-settings-tab swift3-hidden" data-id="int-woocommerce">
      <div class="swift3-config-box">
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Prebuild Product Variations', 'swift3');?></h4>
                        <?php esc_html_e('Automatically preload, cache and optimize available variations for all products.', 'swift3');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-prebuild-product-variation" name="prebuild-product-variation" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('prebuild-product-variation'), 'on');?>><label for="swift3-prebuild-product-variation"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Checkout Booster', 'swift3');?></h4>
                        <?php esc_html_e('Speed up cart and checkout pages.', 'swift3');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-checkout-booster" name="checkout-booster" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('checkout-booster'), 'on');?>><label for="swift3-checkout-booster"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Price Fragments', 'swift3');?></h4>
                        <?php esc_html_e('Load prices via AJAX to show the correct gross price (and currency in multi-currency shops).', 'swift3');?>
                  </div>
                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-price-fragments" name="price-fragments" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('price-fragments'), 'on');?>><label for="swift3-price-fragments"></label>
                  </div>
            </div>
      </div>
</div>
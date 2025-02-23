<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<div class="swift3-settings-tab" data-id="uth-fragments">
      <div class="swift3-config-box">
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Enable Collage', 'swift3');?></h4>
                        <?php esc_html_e('Collage identifies dynamic parts of the page and uses fragments to load them via AJAX, when cache for logged in users is enabled.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-enable-collage" name="collage" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('collage'), 'on');?>><label for="swift3-enable-collage"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Shortcode Fragments', 'swift3');?></h4>
                        <?php esc_html_e('You can select shortcodes which should be loaded via AJAX after the page loaded. It can be useful for elements which can\'t be cached and should be loaded dynamically, like related products, recently view products, most popular posts, recent comments etc.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner swift3-nowrap">
                        <select name="shortcode-fragments" class="swift3-auto-trigger" data-placeholder="<?php esc_html_e('Select shortcodes', 'swift3');?>" multiple>
                        <?php
                              $shortcode_fragments = (array)swift3_get_option('shortcode-fragments');
                              foreach (Swift3_Helper::get_registered_shortcodes() as $shortcode){
                                    echo '<option value="' . $shortcode . '"'. (in_array($shortcode, $shortcode_fragments) ? ' selected' : '') .'>' . $shortcode . '</option>';
                              }
                        ?>
                        </select>
                  </div>
            </div>
      </div>
</div>
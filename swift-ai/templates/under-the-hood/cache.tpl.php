<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<div class="swift3-settings-tab" data-id="uth-caching">
      <div class="swift3-config-box">
            <?php if (is_ssl()):?>
            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Force SSL', 'swift3');?></h4>
                        <?php esc_html_e('If you enable this option Swift Performance will redirect all your visitor requests from http to https.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-force-ssl" name="force-ssl" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('force-ssl'), 'on');?>><label for="swift3-force-ssl"></label>
                  </div>
            </div>
            <?php endif;?>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Logged in Cache', 'swift3');?></h4>
                        <?php esc_html_e('If you enable this option Swift Performance will serve cached pages even for logged in users.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-enable-logged-in-cache" name="logged-in-cache" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('logged-in-cache'), 'on');?>><label for="swift3-enable-logged-in-cache"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Excluded URLs', 'swift3');?></h4>
                        <?php esc_html_e('All URLs listed here will be excluded from caching. Write one URL per line.', 'swift3');?><br><br>
                        <?php echo sprintf(esc_html('For example if you would like to exclude %s you can add the following:', 'swift3'), '<strong>' . home_url('/example-page/') . '</strong>');?>
                        <i><?php echo home_url('/example-page/')?></i> <?php esc_html_e('or', 'swift3');?>
                        <i>/example-page/</i>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <textarea name="excluded-urls" class="swift3-manual-trigger"><?php echo esc_textarea(swift3_get_option('excluded-urls'));?></textarea>
                        <a href="#" class="swift3-save-trigger swift3-btn swift3-btn-light swift3-disabled" data-for="excluded-urls"><?php esc_html_e('Update changes', 'swift3');?></a>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Excluded Post Types', 'swift3');?></h4>
                        <?php esc_html_e('Exclude post types from caching.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner swift3-nowrap">
                        <select name="excluded-post-types" class="swift3-auto-trigger" data-placeholder="<?php esc_html_e('Select post types', 'swift3');?>" multiple>
                        <?php
                              $excluded_post_types = Swift3_Exclusions::get_excluded_post_types();
                              $post_types = get_post_types(array('public' => true, 'publicly_queryable' => true), 'objects');
                              foreach ($post_types as $post_type){
                                    echo '<option value="' . $post_type->name . '"'. (in_array($post_type->name, $excluded_post_types) ? ' selected' : '') .'>' . $post_type->label . '</option>';
                              }
                        ?>
                        </select>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Ignored Query Parameters', 'swift3');?></h4>
                        <?php esc_html_e('Swift Performance bypass cache for requests with query strings by default, however you can add query parameters which will be ignored. Add one parameter per line.', 'swift3');?><br><br>
                        <?php echo sprintf(esc_html__('The following ones are ignored by default: %s', 'swift3'),'<br><i>utm_source, utm_campaign, utm_medium, utm_expid, utm_term, utm_content, fb_action_ids, fb_action_types, fb_source, fbclid, _ga, gclid, age-verified</i>');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <textarea name="ignored-query-parameters" class="swift3-manual-trigger"><?php echo esc_textarea(swift3_get_option('ignored-query-parameters'));?></textarea>
                        <a href="#" class="swift3-save-trigger swift3-btn swift3-btn-light swift3-disabled" data-for="ignored-query-parameters"><?php esc_html_e('Update changes', 'swift3');?></a>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Allowed Query Parameters', 'swift3');?></h4>
                        <?php esc_html_e('Swift Performance does not cache pages with query strings by default, however you can specify query parameters which should be cached. If the URL contains any parameters specified here, it will be cached separately. Add one parameter per line.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <textarea name="allowed-query-parameters" class="swift3-manual-trigger"><?php echo esc_textarea(swift3_get_option('allowed-query-parameters'));?></textarea>
                        <a href="#" class="swift3-save-trigger swift3-btn swift3-btn-light swift3-disabled" data-for="allowed-query-parameters"><?php esc_html_e('Update changes', 'swift3');?></a>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Bypass Cache Cookies', 'swift3');?></h4>
                        <?php esc_html_e('Cache will be bypassed if the user has one of these cookies. You can use full-, or partial cookie names. Add one cookie per line.', 'swift3');?><br><br>
                        <strong><?php esc_html_e('Examples:', 'swift3');?></strong><br>
                        <i>woocommerce_cart_hash</i><br><i>woocommerce_cart_</i>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <textarea name="bypass-cookies" class="swift3-manual-trigger"><?php echo esc_textarea(swift3_get_option('bypass-cookies'));?></textarea>
                        <a href="#" class="swift3-save-trigger swift3-btn swift3-btn-light swift3-disabled" data-for="bypass-cookies"><?php esc_html_e('Update changes', 'swift3');?></a>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Enable GZIP', 'swift3');?></h4>
                        <?php esc_html_e('If you enable this option it will generate htacess/nginx rules for GZIP compression.', 'swift3')?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-enable-gzip" name="enable-gzip" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('enable-gzip'), 'on');?>><label for="swift3-enable-gzip"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Enable Browser Cache', 'swift3');?></h4>
                        <?php esc_html_e('If you enable this option it will generate htacess/nginx rules for browser cache.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-enable-browser-cache" name="browser-cache" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('browser-cache'), 'on');?>><label for="swift3-enable-browser-cache"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Keep Original Headers', 'swift3');?></h4>
                        <?php esc_html_e('Send original headers for cached pages. If you are using a plugin which send custom headers you can keep them for the cached version as well.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-keep-original-headers" name="keep-original-headers" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('keep-original-headers'), 'on');?>><label for="swift3-keep-original-headers"></label>
                  </div>
            </div>

            <div class="swift3-settings-wrapper">
                  <div class="swift3-settings-description">
                        <h4><?php esc_html_e('Remote prebuild', 'swift3');?></h4>
                        <?php esc_html_e('If loopback is disabled on server you can use remote prebuild instead default local prebuild.', 'swift3');?>
                  </div>

                  <div class="swift3-settings-wrapper-inner">
                        <input type="checkbox" class="swift3-auto-trigger ios8-switch" id="swift3-remote-prebuild" name="remote-prebuild" value="on"<?php Swift3_Helper::maybe_checked(swift3_get_option('remote-prebuild'), 'on');?>><label for="swift3-remote-prebuild"></label>
                  </div>
            </div>
      </div>
</div>
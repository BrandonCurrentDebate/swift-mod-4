<?php

if (defined('GF_PLUGIN_DIR_PATH')){
      add_filter('swift3_js_delivery_tag', function($tag){
            if (!empty($tag->attributes['src']) && strpos($tag->attributes['src'], 'gravityforms/js/') !== false){
                  $tag->attributes['data-s3waitfor'] = 'grecaptcha';
            }

            return $tag;
      });
      if (swift3_check_option('js-delivery', 0, '>')){
            add_action('wp_footer', function(){
                  echo '<script type="swift/lazyscript">window.grecaptcha = window.grecaptcha || function() {};</script>';
            }, PHP_INT_MAX);
      }
}
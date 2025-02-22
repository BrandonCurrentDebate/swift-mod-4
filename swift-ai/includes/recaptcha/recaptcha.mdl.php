<?php
add_filter('swift3_script_type', function($type, $tag){
      if (isset($tag->attributes['src']) && preg_match('~recaptcha/api\.js~', $tag->attributes['src'])){
            $type = 'swift/lazyscript';
      }
      return $type;
}, 10, 2);


add_filter('swift3_js_delivery_tag', function($tag){
      if (strpos($tag->inner_html, 'grecaptcha.ready') !== false){
            $tag->attributes['data-s3waitfor'] = 'grecaptcha';
      }

      return $tag;
});
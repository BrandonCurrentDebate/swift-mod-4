<?php
add_filter('swift3_script_type', function($type, $tag){
      if (isset($tag->attributes['src']) && preg_match('~wp-content/plugins/duracelltomi-google-tag-manager/~', $tag->attributes['src'])){
            $type = 'swift/analytics';
      }
      return $type;
}, 10, 2);

?>
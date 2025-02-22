<?php

add_filter('swift3_script_type', function($type, $tag){
      if (isset($tag->attributes['src']) && strpos($tag->attributes['src'], 'search-filter-build') !== false){
            $type = 'swift/lazyscript';
      }
      return $type;
}, 10, 2);

add_filter('swift3_script_type', function($type, $tag){
      if (isset($tag->attributes['src']) && strpos($tag->attributes['src'], 'search-filter-pro/public/assets/js/select2') !== false){
            $type = 'swift/lazyscript';
      }
      return $type;
}, 10, 2);
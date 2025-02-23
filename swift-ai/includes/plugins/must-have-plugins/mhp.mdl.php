<?php

add_filter('swift3_skip_js_optimization', function($result, $tag){
      if (strpos($tag->inner_html, 'mhCookie') !== false){
            return true;
      }

      return $result;
}, 10, 2);

?>
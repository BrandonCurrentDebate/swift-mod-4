<?php

add_filter('swift3_skip_js_optimization', function($result, $tag){
      if (isset($tag->attributes['src']) && strpos($tag->attributes['src'], 'wp-grid-builder/frontend/assets/js/facets.js') != false){
            return true;
      }
      if (isset($tag->attributes['id']) && in_array($tag->attributes['id'], array('wpgb-js-extra', 'wpgb-polyfills-js-before'))){
            return true;
      }

      return $result;
}, 10, 2);
<?php

add_filter('swift3_skip_iframe_optimization', function($result, $tag){
      if (isset($tag->attributes['consent-required'])){
            return true;
      }

      return $result;
}, 10, 2);
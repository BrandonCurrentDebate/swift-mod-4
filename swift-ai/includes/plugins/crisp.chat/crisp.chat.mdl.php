<?php

add_filter('swift3_skip_js_optimization', function($result, $tag){
      if (function_exists('register_crisp_plugin_textdomain') && strpos($tag->inner_html, 'CRISP_RUNTIME_CONFIG') !== false){
            return true;
      }
      return $result;
}, 10, 2);

add_filter('swift3_avoid_blob', function($list){
      if (function_exists('register_crisp_plugin_textdomain')){
            $list[] = 'client.crisp.chat';
      }
      return $list;
});
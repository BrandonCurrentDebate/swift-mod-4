<?php
add_filter('swift3_avoid_blob', function($list){
      if (defined('PRESTO_PLAYER_PLUGIN_FILE')){
            $list[] = 'presto-player';
      }
      return $list;
});

add_filter('swift3_optimizer_scripts', function($scripts){
      if (defined('PRESTO_PLAYER_PLUGIN_FILE')){
            $scripts['iframes'] .= file_get_contents(__DIR__ . '/iframes.js');
      }
      return $scripts;
});

?>
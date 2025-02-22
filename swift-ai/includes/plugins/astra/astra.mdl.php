<?php

class Swift3_Astra_Module {
      public static function init(){
            if (defined('ASTRA_THEME_VERSION')){
                  add_filter('swift3_optimizer_scripts', function($scripts){
                        $scripts['astra_infinite_loader'] = "document.addEventListener('astraInfinitePaginationLoaded', load_fragments);";
                        return $scripts;
                  });
            }
      }
}

add_action('init', array('Swift3_Astra_Module', 'init'));
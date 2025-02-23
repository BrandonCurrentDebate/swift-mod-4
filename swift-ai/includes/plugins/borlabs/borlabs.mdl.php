<?php


if (class_exists('BorlabsCookie\Cookie\Frontend\ScriptBlocker') && method_exists('BorlabsCookie\Cookie\Frontend\ScriptBlocker', 'handleJavaScriptTagBlocking')){

      add_action('init', function(){
            if (class_exists('BorlabsCookie\Cookie\Frontend\ScriptBlocker')){
                  remove_action('wp_footer', [BorlabsCookie\Cookie\Frontend\ScriptBlocker::getInstance(), 'handleJavaScriptTagBlocking'], 19021987);
            }
      }, 11);

      add_filter('swift3_before_assets_optimizer', function($buffer){
            if (class_exists('BorlabsCookie\Cookie\Frontend\ScriptBlocker')){
                  $buffer = preg_replace_callback('/<script([^>]*)>(.*)<\/script>/Us', [BorlabsCookie\Cookie\Frontend\ScriptBlocker::getInstance(), 'blockJavaScriptTag'], $buffer);
            }

            return $buffer;
      });
}


?>
<?php

class Swift3_WP_Featherlight {

      public static $url;

      public static function init(){
            if (self::detected()){
                  Swift3_System::register_includes('wp-featherlight');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('WP Featherlight detected', 'swift3'));
                  });

                  self::$url = SWIFT3_URL . 'includes/wp-featherlight/';

                  add_filter('swift3_script_type', function($type, $tag){
                        if (isset($tag->attributes['src']) && strpos($tag->attributes['src'], '/js/wpFeatherlight.pkgd.min.js') !== false){
                              $type = 'swift/lazyscript';
                        }

                        return $type;
                  }, 10, 2);

                  add_filter('swift3_js_delivery_tag', function($tag){
                        if (isset($tag->attributes['src']) && strpos($tag->attributes['src'], '/js/wpFeatherlight.pkgd.min.js') !== false){
                              $tag->attributes['src'] = self::$url . '/wpFeatherlight.pkgd.min.js?ver=' . md5_file(__DIR__ . '/wpFeatherlight.pkgd.min.js');
                        }

                        return $tag;
                  });
            }
      }

      public static function detected(){
            return function_exists('wp_featherlight');
      }
}

add_action('init', array('Swift3_WP_Featherlight', 'init'));

?>
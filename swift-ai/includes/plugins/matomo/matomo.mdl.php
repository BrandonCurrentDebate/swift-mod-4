<?php

class Swift3_Matamo_Module {

      public static function init(){
            if (self::detected()){
                  Swift3_System::register_includes('Matamo');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('Matamo detected', 'swift3'));
                  });
                  add_filter('swift3_url_match_excluded', function($urls){
                        $urls[] = 'app/matomo.php';

                        return $urls;
                  });
                  add_filter('swift3_script_type', function($type, $tag){
                        if (preg_match('~matomo\.(php|js)~', $tag->inner_html)){
                              $type = 'swift/analytics';
                        }
                        return $type;
                  }, 10, 2);
            }
      }

      public static function detected(){
            return (defined('MATOMO_ANALYTICS_FILE') || function_exists('wp_piwik_autoloader'));
      }
}

add_action('plugins_loaded', array('Swift3_Matamo_Module', 'init'));
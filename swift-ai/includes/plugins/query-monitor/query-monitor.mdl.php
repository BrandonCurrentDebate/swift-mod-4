<?php

class Swift3_Query_Monitor_Module {

      public static function init(){
            if (self::detected()){
                  Swift3_System::register_includes('query-monitor');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('Query Monitor detected', 'swift3'));
                  });
                  add_filter('swift3_script_type', array(__CLASS__, 'script_type'), 10, 2);

                  add_filter('swift3_http_request_cache_args', array(__CLASS__, 'clean_http_request_cache_args'));
            }
      }

      public static function script_type($type, $tag){
            if (isset($tag->attributes['id']) && $tag->attributes['id'] == 'query-monitor-js'){
                  return 'swift/lazyscript';
            }
            return $type;
      }

      public static function clean_http_request_cache_args($args){
            if (isset($args['_qm_key'])){
                  unset($args['_qm_key']);
            }

            return $args;
      }

      public static function detected(){
            return defined('QM_VERSION');
      }
}

add_action('init', array('Swift3_Query_Monitor_Module', 'init'));
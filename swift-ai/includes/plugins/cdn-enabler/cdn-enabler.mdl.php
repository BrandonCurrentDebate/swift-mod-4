<?php

class Swift3_CDN_Enabler_Module {

      public static function init(){
            if (self::detected()){
                  Swift3_System::register_includes('cdn-enabler');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('CDN Enabler detected', 'swift3'));
                  });

                  include_once 'cdn_enabler_engine.class.php';
            }
      }

      public static function detected(){
            return defined('CDN_ENABLER_VERSION');
      }

}

add_action('plugins_loaded', array('Swift3_CDN_Enabler_Module', 'init'), 0);
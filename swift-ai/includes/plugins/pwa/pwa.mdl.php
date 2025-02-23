<?php

class Swift3_PWA_Module {

      public static function init(){
            if (self::detected()){
                  Swift3_System::register_includes('pwa');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('PWA detected', 'swift3'));
                  });

                  Swift3_Exclusions::add_exclded_url(home_url('wp.serviceworker'));
            }
      }

      public static function detected(){
            return defined('PWA_VERSION');
      }

}

add_action('plugins_loaded', array('Swift3_PWA_Module', 'init'));
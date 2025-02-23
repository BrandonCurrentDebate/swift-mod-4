<?php

class Swift3_Jetpack_Module {

      public function __construct(){
            if (self::detected()){
                  Swift3_System::register_includes('jetpack');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('Jetpack detected', 'swift3'));
                  });

                  add_filter('swift3_avoid_blob', function($list){
                        $list[] = 'c0.wp.com';
                        return $list;
                  });
            }

      }

      public static function detected(){
            $active_plugins = get_option('active_plugins');
            return in_array('jetpack/jetpack.php', $active_plugins);
      }

}

new Swift3_Jetpack_Module();
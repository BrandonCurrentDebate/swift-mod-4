<?php

class Swift3_Amelia_Booking_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('ameliabooking/ameliabooking.php', array(__CLASS__, 'coop'));

            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('ameliabooking');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Amelia Booking detected', 'swift3'));
                        });
                  }
            });
      }
      public static function coop($plugin, $active_plugins){
            if (!is_admin()){
                  if (isset($_REQUEST['wc-ajax'])){
                        return false;
                  }
                  return false;
            }
            if (in_array(Swift3_Code_Optimizer::$url['path'], array('/wp-admin/post-new.php', '/wp-admin/post.php'))){
                  return false;
            }
            if (Swift3_Code_Optimizer::$url['path'] == '/wp-admin/admin.php' && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^wpamelia~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::$url['path'] == '/wp-admin/admin-ajax.php' && isset($_REQUEST['action']) && !preg_match('~wpamelia~', $_REQUEST['action'])){
                  return false;
            }

            return true;
      }

      public static function detected(){
            return defined( 'AMELIA_PATH' );
      }

}

Swift3_Amelia_Booking_Module::load();
<?php

class Swift3_Pixelmate_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('pixelmate/pixelmate.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('pixelmate');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Pixelmate detected', 'swift3'));
                        });
                        add_filter('swift3_observer_script', function($script){
                              $observer = file_get_contents(__DIR__ . '/observer.js');
                              return preg_replace('~noop\(\)(;|\})~', $observer . '$0', $script);
                        });
                  }
            });
      }
      public static function coop($plugin, $active_plugins){
            if (!is_admin()){
                  if (isset($_REQUEST['wc-ajax'])){
                        return true;
                  }
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^(coo_settings|acf-options-)~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~^(acf|query|puc|example)~', $_REQUEST['action'])){
                  return false;
            }

            return true;
      }

      public static function detected(){
            return defined('PIXELMATE_VERSION');
      }

}

Swift3_Pixelmate_Module::load();
<?php

class Swift3_Broken_Link_Checker_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('broken-link-checker/broken-link-checker.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('broken-link-checker');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Broken Link Checker detected', 'swift3'));
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
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^blc_~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~^((wpmudev_)?blc_|save_settings-)~', $_REQUEST['action'])){
                  return false;
            }

            return true;
      }

      public static function detected(){
            return defined('WPCF7_VERSION');
      }

}

Swift3_Broken_Link_Checker_Module::load();
<?php

class Swift3_Duplicate_Page_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('duplicate-page/duplicatepage.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('duplicate-page');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Duplicate Page detected', 'swift3'));
                        });
                  }
            });
      }
      public static function coop($plugin, $active_plugins){
            if (!is_admin()){
                  return true;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('edit.php')){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('options-general.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && Swift3_Code_Optimizer::$query_string['page'] == 'duplicate_page_settings'){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && $_REQUEST['action'] == 'mk_dp_close_dp_help'){
                  return false;
            }

            return true;
      }

      public static function detected(){
            return defined('DUPLICATE_PAGE_PLUGIN_VERSION');
      }

}

Swift3_Duplicate_Page_Module::load();
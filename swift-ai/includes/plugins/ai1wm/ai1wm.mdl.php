<?php

class Swift3_AI1WM_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('all-in-one-wp-migration/all-in-one-wp-migration.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('ai1wm');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('All in One WP Migration detected', 'swift3'));
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
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^ai1wm~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~^ai1wm~', $_REQUEST['action'])){
                  return false;
            }
            add_action('admin_head', function(){
                  echo '<style>' . str_replace('%WP_PLUGIN_URL%', WP_PLUGIN_URL, file_get_contents(__DIR__ . '/admin.css')) . '</style>';
            },7);



            return true;
      }

      public static function detected(){
            return defined('AI1WM_PLUGIN_BASENAME');
      }

}

Swift3_AI1WM_Module::load();
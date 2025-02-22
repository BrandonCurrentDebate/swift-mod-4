<?php

class Swift3_Duplicate_Post_Module {

      public static $plugin_version = 1;

      public static function load(){
            Swift3_Code_Optimizer::add('duplicate-post/duplicate-post.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('duplicate-post');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Duplicate Post detected', 'swift3'));
                        });
                  }
            });
      }
      public static function coop($plugin, $active_plugins){
            if (!is_admin()){
                  return true;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file(array('post-new.php', 'post.php', 'edit.php', 'edit-tags.php'))){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('options-general.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && Swift3_Code_Optimizer::$query_string['page'] == 'duplicatepost'){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['action']) && preg_match('~^duplicate_post_~', Swift3_Code_Optimizer::$query_string['action'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~duplicate_post~', $_REQUEST['action'])){
                  return false;
            }

            return true;
      }

      public static function detected(){
            return defined('DUPLICATE_POST_CURRENT_VERSION');
      }

}

Swift3_Duplicate_Post_Module::load();
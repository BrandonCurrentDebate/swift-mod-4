<?php

class Swift3_WPForms_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('wpforms-lite/wpforms.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('wpforms');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('WP Forms detected', 'swift3'));
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
            if (Swift3_Code_Optimizer::is_current_admin_file(array('post-new.php', 'post.php'))){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file(array('index.php','admin.php')) && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^wpforms~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~^wp(forms|_async_request)~', $_REQUEST['action'])){
                  return false;
            }
            if ($plugin == 'wpforms-lite/wpforms.php'){
                  add_action('admin_head', function(){
                        echo '<style>' . file_get_contents(__DIR__ . '/admin.css') . '</style>';
                  },7);
            }

            return true;
      }

      public static function detected(){
            return defined('WPFORMS_VERSION');
      }

}

Swift3_WPForms_Module::load();
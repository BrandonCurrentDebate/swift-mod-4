<?php

class Swift3_Ninja_Forms_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('ninja-forms/ninja-forms.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('ninja-forms');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Ninja Forms detected', 'swift3'));
                        });
                  }
            });

            add_action('init', function(){
                  if (isset($_REQUEST['nf_admin_notice_ignore']) && wp_verify_nonce($_GET['_wpnonce'])){
                        Swift3_Code_Optimizer::clear_admin_cache();
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
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^(nf-|ninja-forms)~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~^(nf_|ninja_forms)~', $_REQUEST['action'])){
                  return false;
            }
            if (isset($_REQUEST['nf_admin_notice_ignore'])){
                  return false;
            }

            add_action('admin_enqueue_scripts', function(){
                  if (strpos(Swift3_Code_Optimizer::$admin_cache_raw, 'nf-admin-notice') !== false)
                  wp_enqueue_style('nf-admin-notices', WP_PLUGIN_URL . '/ninja-forms/assets/css/admin-notices.css');
            });

            return true;
      }

      public static function detected(){
            return function_exists('ninja_forms_three_table_exists');
      }

}

Swift3_Ninja_Forms_Module::load();
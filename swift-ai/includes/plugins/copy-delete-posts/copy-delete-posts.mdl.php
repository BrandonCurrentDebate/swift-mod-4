<?php

class Swift3_Copy_Delete_Posts_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('copy-delete-posts/copy-delete-posts.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('copy-delete-posts');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Copy Delete Posts detected', 'swift3'));
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
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && Swift3_Code_Optimizer::$query_string['page'] == 'copy-delete-posts'){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~(cdp_|analyst_|tifm_|inisev_)~', $_REQUEST['action'])){
                  return false;
            }
            add_action('admin_enqueue_scripts', function(){
                  if (strpos(Swift3_Code_Optimizer::$admin_cache_raw, 'notice-success analyst-notice') !== false)
                  wp_enqueue_style('cdp-css-global', WP_PLUGIN_URL . '/copy-delete-posts/assets/css/cdp-global.min.css');
      	});

            add_action('admin_head', function(){
                  echo '<style>' . file_get_contents(__DIR__ . '/admin.css') . '</style>';
            },6);



            return true;
      }

      public static function detected(){
            return defined('CDP_VERSION');
      }

}

Swift3_Copy_Delete_Posts_Module::load();
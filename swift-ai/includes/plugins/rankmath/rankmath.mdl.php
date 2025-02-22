<?php

class Swift3_Rank_Math_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('seo-by-rank-math/rank-math.php', array(__CLASS__, 'coop'));
            Swift3_Code_Optimizer::add('seo-by-rank-math-pro/rank-math-pro.php', array(__CLASS__, 'coop'));

            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('rankmath');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Rank Math detected', 'swift3'));
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
            if (Swift3_Code_Optimizer::is_current_admin_file(array('post-new.php', 'post.php', 'edit.php', 'edit-tags.php', 'admin-post.php'))){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^rank(\-|_)math~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~(rank_math|async_request|cmb2_oembed_handler|helpers_notice_dismissible|csv_import_progress)~', $_REQUEST['action'])){
                  return false;
            }
            if ($plugin == 'seo-by-rank-math/rank-math.php'){
                  add_action('admin_enqueue_scripts', function(){
                        echo '<style>' . file_get_contents(__DIR__ . '/admin.css') . '</style>';
                  },6);
            }

            return true;
      }

      public static function detected(){
            return defined( 'WPSEO_FILE' );
      }

}

Swift3_Rank_Math_Module::load();
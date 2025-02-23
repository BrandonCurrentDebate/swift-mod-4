<?php

class Swift3_CF7_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('contact-form-7/wp-contact-form-7.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('cf7');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Contact Form 7 detected', 'swift3'));
                        });
                        add_filter('swift3_js_delivery_tag', function($tag){
                              if (!empty($tag->attributes['src']) && strpos($tag->attributes['src'], 'contact-form-7/modules/recaptcha/index.js') !== false){
                                    $tag->attributes['data-s3waitfor'] = 'grecaptcha';
                              }

                              return $tag;
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
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^wpcf7~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~^wpcf7~', $_REQUEST['action'])){
                  return false;
            }

            return true;
      }

      public static function detected(){
            return defined('WPCF7_VERSION');
      }

}

Swift3_CF7_Module::load();
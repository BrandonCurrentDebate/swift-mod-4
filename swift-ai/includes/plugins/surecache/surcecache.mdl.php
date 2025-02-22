<?php

class Swift3_SureCache_Module {

      public static function load(){
            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('surecache');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('SureCache detected', 'swift3'));
                        });

                        add_action('swift3_invalidate_object', array(__CLASS__, 'clear_cache'));
                        add_action('swift3_purge_cache', array(__CLASS__, 'clear_cache'));
                        add_action('swift3_cache_done', array(__CLASS__, 'clear_cache'));


                  }
            });
      }

      public static function clear_cache($url){
            $surecache = SureCache_AutoPurge::getInstance();

            if (empty($url)){
                  $surecache->addAll();
            }
            else {
                  $post_id = url_to_postid($url);
                  if (empty($post_id)){
                        $surecache->addAll();
                  }
                  else {
                        $surecache->addPost($post_id);
                  }
            }
      }

      public static function detected(){
            return class_exists('SureCache_AutoPurge');
      }

}

Swift3_SureCache_Module::load();
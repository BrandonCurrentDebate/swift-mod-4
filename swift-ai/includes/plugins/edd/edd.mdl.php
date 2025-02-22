<?php

class Swift3_EDD_Module {
      public static function init(){
            if(class_exists('Easy_Digital_Downloads')){
                  foreach (self::get_excluded_pages() as $page_id){
                        Swift3_Exclusions::add_exclded_url(get_permalink($page_id));
                  }
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('Easy Digital Downloads detected', 'swift3'));
                  });
                  add_filter('swift3_prefetch_ignore', function($list){
                        $list[] = 'add_to_cart';
                        return $list;
                  });
            }
      }
      public static function get_excluded_pages(){
            $settings = get_option('edd_settings');

            return apply_filters('swift3_excluded_edd_pages', array(
                  $settings['purchase_page'],
                  $settings['success_page'],
                  $settings['failure_page'],
                  $settings['purchase_history_page']
            ));
      }
}
add_action('init', array('Swift3_EDD_Module', 'init'));
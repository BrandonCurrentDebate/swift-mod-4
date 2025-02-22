<?php

class Swift3_WPBakery_Module {

      public function __construct(){
            add_action('init', array($this, 'init'));
      }

      public function init(){
            if(defined('WPB_VC_VERSION')){
                  Swift3_System::register_includes('wpbakery');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('WP Bakery detected', 'swift3'));
                  });

                  add_action('swift_after_header', array(__CLASS__, 'add_styles'));
            }
      }
      public static function add_styles(){
            echo Swift3_Helper::get_style_tag('[data-vc-full-width]{position:relative;left:calc((100% - 100vw)/2);width:100vw;max-width:100vw;margin-left:0!important;margin-right:0!important}');
      }
}

new Swift3_WPBakery_Module();
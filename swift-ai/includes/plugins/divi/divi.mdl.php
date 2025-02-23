<?php

class Swift3_Divi_Module {


      public function __construct(){
            add_filter('swift3_skip_optimizer', function($result){
                  if (isset($_GET['et_fb'])){
                        return true;
                  }
                  return $result;
            });
            add_filter('option_et_divi', function($options){
                  if (function_exists('is_admin') && !is_admin()){
                        foreach (array('divi_dynamic_module_framework', 'divi_dynamic_css', 'divi_dynamic_icons', 'divi_inline_stylesheet', 'divi_critical_css', 'divi_disable_emojis', 'divi_defer_block_css', 'divi_google_fonts_inline', 'divi_limit_google_fonts_support_for_legacy_browsers', 'divi_enable_jquery_body', 'divi_enable_jquery_body_super') as $key){
                              $options[$key] = "false";
                        }
                  }
                  return $options;
            },10,3);
      }
}

new Swift3_Divi_Module();
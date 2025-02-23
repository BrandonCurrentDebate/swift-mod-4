<?php

class Swift3_Revslider_Module {

      public static $lazy_tag_count = 0;

      public function __construct(){
            add_action('init', array($this, 'init'));
            Swift3_Code_Optimizer::add('revslider/revslider.php', array(__CLASS__, 'coop'));

            if (defined('DOING_AJAX') && isset($_REQUEST['action']) && preg_match('~^revslider~', $_REQUEST['action'])){
                  add_filter('swift3_skip_optimizer', '__return_true');
            }
      }

      public function init(){
            if(defined('RS_REVISION')){
                  add_filter('swift3_before_assets_optimizer', array(__CLASS__, 'lazy_tags'));
                  add_filter('swift3_lazy_element_css', array(__CLASS__, 'css'), 10, 2);
                  add_filter('swift_optimize_image_size', array(__CLASS__, 'optimize_image_size'), 10, 2);
                  Swift3_System::register_includes('revslider');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('Slider Revolution detected', 'swift3'));
                  });
            }
      }

      public static function lazy_tags($buffer){
            return preg_replace_callback('~<rs-module-wrap ~', array(__CLASS__, 'add_lazy_tag'), $buffer);
      }

      public static function add_lazy_tag(){
            return '<rs-module-wrap data-s3-lazy-element="rs/'. self::$lazy_tag_count .'" data-s3-lazy-waitfor="rs-sbg-px" ';
      }

      public static function css($css, $elements){
            foreach ($elements as $key => $url) {
                  if (preg_match('~rs/(\d+)~',$key)){
                        foreach (array('mobile', 'desktop') as $device){
                              $placeholder_file = Swift3_Helper::get_abspath_from_url($url->{$device});
                              @$size = getimagesize($placeholder_file);
                              $ar = (isset($size[1]) && !empty($size[1]) ? 'aspect-ratio:' . ($size[0]/$size[1]) : '');
                              $css[$device] .= 'html body rs-module-wrap[style*="visibility:hidden"][data-s3-lazy-element="' . $key . '"]{display: block !important; visibility: visible !important; background:url(' . $url->{$device} . ') no-repeat !important; background-size: cover !important; position: relative;z-index: 2147483640;max-width:100vw;' . $ar . '}';
                        }
                  }
            }
            return $css;
      }
      public static function optimize_image_size($result, $tag){
            if (isset($tag->attributes['data-lazyload'])){
                  $result = false;
            }
            return $result;
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
            if (Swift3_Code_Optimizer::is_current_admin_file(array('index.php', 'admin.php')) && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^revslider~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~^revslider~', $_REQUEST['action'])){
                  return false;
            }

            return true;
      }
}

new Swift3_Revslider_Module();
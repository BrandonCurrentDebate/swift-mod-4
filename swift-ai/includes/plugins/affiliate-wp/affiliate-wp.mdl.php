<?php

class Swift3_AffiliateWP_Module {

      private static $_affiliate_area_permalink;

      public function __construct(){
            add_action('init', array(__CLASS__, 'init'));
      }

      public static function init(){
            if (self::detected()){
                  Swift3_System::register_includes('affiliate-wp');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('Affiliate WP detected', 'swift3'));
                  });

                  $settings = affiliate_wp()->settings;
                  $affiliate_page = $settings->get('affiliates_page');
                  self::$_affiliate_area_permalink = get_permalink($affiliate_page);
                  add_filter('swift3_url_match_excluded', function($urls){
                        $urls[] = self::$_affiliate_area_permalink;

                        return $urls;
                  });

                  add_filter('swift3_skip_optimizer', function($ignore){
                       if ((isset($_SERVER['REQUEST_URI']) && preg_match('~^' . preg_quote(parse_url(self::$_affiliate_area_permalink, PHP_URL_PATH)) . '~', $_SERVER['REQUEST_URI']))){
                             return true;
                       }
                       return $ignore;
                 });
            }
      }

      public static function detected(){
            return function_exists('affiliate_wp');
      }



}

new Swift3_AffiliateWP_Module();
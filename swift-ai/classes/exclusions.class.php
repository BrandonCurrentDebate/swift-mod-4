<?php

class Swift3_Exclusions {

      public static $excluded_urls = array();

      public static $excluded_post_types = array();

      public static $allowed_query_parameters = array();

      public static $ignored_query_strings = array();

      public static $bypass_cookies = array();

      public function __construct(){
            self::init();
      }
      public static function init(){
            self::$excluded_urls = self::$excluded_post_types = self::$bypass_cookies = array();
            self::$allowed_query_parameters = apply_filters('swift3_default_allowed_query_parameters', array('p', 'page_id'));
            self::$ignored_query_strings = apply_filters('swift3_default_ignored_query_strings', array('utm_source', 'utm_campaign', 'utm_medium', 'utm_expid', 'utm_term', 'utm_content', 'fb_action_ids', 'fb_action_types', 'fb_source', 'fbclid', '_ga', 'gclid', 'age-verified', 'rdt_cid', 'li_fat_id'));
            self::add_exclded_url(home_url('robots.txt'));
            self::add_exclded_url(wp_login_url());
            $excluded_post_types = swift3_get_option('excluded-post-types');
            if (!empty($excluded_post_types)){
                  foreach ((array)$excluded_post_types as $post_type){
                        self::add_exclded_post_type($post_type);
                  }
            }
            foreach (array_filter(explode("\n", swift3_get_option('allowed-query-parameters'))) as $param){
                  self::add_allowed_query_parameter($param);
            }
            foreach (array_filter(explode("\n", swift3_get_option('ignored-query-parameters'))) as $param){
                  self::add_ignored_query_string($param);
            }
            if (swift3_check_option('logged-in-cache', 'on', '!=') && defined('LOGGED_IN_COOKIE')){
                  self::add_bypass_cookie(LOGGED_IN_COOKIE);
            }
            foreach (array_filter(explode("\n", swift3_get_option('bypass-cookies'))) as $cookie){
                  self::add_bypass_cookie($cookie);
            }
      }
      public static function is_excluded($url, $post_id = NULL){
            return (self::is_url_excluded($url, $post_id) || self::is_url_match_excluded($url, $post_id) || self::is_post_type_excluded($url, $post_id));
      }
      public static function is_url_excluded($url, $post_id = NULL){
            $excluded = false;
            $id = Swift3_Warmup::get_id($url);
            if (array_key_exists($id, self::get_excluded_urls())){
                  $excluded = true;
            }

            return apply_filters('swift3_is_url_excluded', $excluded, $url, $post_id, $id);
      }
      public static function is_url_match_excluded($url, $post_id = NULL){
            $excluded = false;

            $excluded_url_parts = array_map(function($url_part){
                  return preg_quote($url_part, '~');
            },array_filter(apply_filters('swift3_url_match_excluded', explode("\n",swift3_get_option('excluded-urls')))));
            if (!empty($excluded_url_parts)){
                  if (preg_match('~(' . implode('|', $excluded_url_parts) . ')~', $url)){
                        $excluded = true;
                  }
            }

            return apply_filters('swift3_is_excluded_by_user', $excluded, $url, $post_id);
      }
      public static function is_post_type_excluded($url, $post_id = NULL){
            global $wp_rewrite;
            $excluded = false;
            $post_type = NULL;
            if (empty($post_id) && !empty($wp_rewrite)){
                  $post_id = url_to_postid($url);
            }
            if (!empty($post_id)){
                  $post_type = get_post_type($post_id);
                  $excluded = in_array($post_type, self::get_excluded_post_types());
            }
            if (parse_url(trim($url, '/'), PHP_URL_PATH) == parse_url(trim(home_url(), '/'), PHP_URL_PATH)){
                  $excluded = false;
            }

            return apply_filters('swift3_is_url_excluded', $excluded, $url, $post_id, $post_type);
      }

      public static function is_content_excluded($buffer, $url){
            $excluded = false;

            if (strpos($buffer, '<?xml version="1.0"') === 0){
                  $excluded = true;
            }

            return apply_filters('swift3_is_content_excluded', $excluded, $buffer, $url);
      }

      public static function get_excluded_urls(){
            return apply_filters('swift3_excluded_urls', array_combine(array_map(array('Swift3_Warmup', 'get_id'), self::$excluded_urls), self::$excluded_urls));
      }

      public static function get_excluded_post_types(){
            return apply_filters('swift3_excluded_post_types', self::$excluded_post_types);
      }

      public static function get_allowed_query_parameters(){
            return apply_filters('swift3_allowed_query_parameters', array_unique(self::$allowed_query_parameters));
      }

      public static function get_bypass_cookies(){
            return apply_filters('swift3_bypass_cookies', array_unique(self::$bypass_cookies));
      }

      public static function get_ignored_query_strings(){
            return apply_filters('swift3_ignored_query_strings', array_unique(self::$ignored_query_strings));
      }

      public static function add_exclded_url($url){
            if (!empty($url)){
                  self::$excluded_urls[] = $url;
            }
      }

      public static function add_exclded_post_type($post_type){
            if (!empty($post_type)){
                  self::$excluded_post_types[] = $post_type;
            }
      }

      public static function add_allowed_query_parameter($key){
            if (!empty($key)){
                  self::$allowed_query_parameters[] = $key;
            }
      }

      public static function add_ignored_query_string($key){
            if (!empty($key)){
                  self::$ignored_query_strings[] = $key;
            }
      }

      public static function add_bypass_cookie($key){
            if (!empty($key)){
                  self::$bypass_cookies[] = $key;
            }
      }
}

?>
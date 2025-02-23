<?php

class Swift3_Hosting_Module {

      public function __construct(){
            if (self::cache_detected()){
                  add_filter('swift3_cache_function', function(){
                        return array('Swift3_Helper', 'do_nothing');
                  });
            }

            add_action('swift3_invalidate_cache', array(__CLASS__, 'clear_cache'));
            add_action('swift3_purge_cache', array(__CLASS__, 'clear_cache'));

      }
      public static function cache_detected(){
            // WP Engine detected
            if (class_exists("WpeCommon")) {
                  return true;
            }

            // SG Optimizer detected
            if (function_exists('sg_cachepress_purge_cache')) {
                  $sg_cachepress = get_option('sg_cachepress');

                  if (isset($sg_cachepress['enable_cache']) && $sg_cachepress['enable_cache'] === 1){
                        return true;
                  }
            }

            return false;
      }
      public static function clear_cache($url = ''){
            // Godaddy
            if (class_exists("\\WPaaS\\Cache")){
                  \WPaaS\Cache::ban();
            }

            // WP Engine
            if (class_exists("WpeCommon")) {
                  if (method_exists('WpeCommon', 'purge_varnish_cache')){
                        WpeCommon::purge_varnish_cache();
                  }
                  if (method_exists('WpeCommon', 'purge_memcached')){
                      WpeCommon::purge_memcached();
                  }
                  if (method_exists('WpeCommon', 'clear_maxcdn_cache')){
                      WpeCommon::clear_maxcdn_cache();
                  }
            }

            // Siteground
            if (function_exists('sg_cachepress_purge_cache')) {
                  sg_cachepress_purge_cache();
            }

            // Runcache
            if (class_exists('RunCache_Purger')){
                  if (empty($url)){
                        RunCache_Purger::flush_home(true);
                  }
                  else {
                        RunCache_Purger::flush_url($url);
                  }
            }

            // WSA_Cachepurge_WP
            if (method_exists('WSA_Cachepurge_WP', 'purge_cache')){
                  WSA_Cachepurge_WP::purge_cache();
            }
      }
}

new Swift3_Hosting_Module();
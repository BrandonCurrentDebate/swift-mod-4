<?php

class Swift3_Warmup {

      public static $version = '2';

      public static $urls = array();

      // Add a static flag to prevent recursive execution
      private static $is_running = false;

      public function __construct(){
            if (swift3_check_option('db_version', self::$version, '<')){
                  $this->_init_db();
            }
            if (!swift3_check_option('warmup', 1)){
                  add_action('init', array($this, 'generate_urls'));
            }
            add_action('update_option_page_on_front', function($old, $new){
                  self::update_url(Swift3_Helper::get_permalink($old), array('priority' => 2));
                  self::update_url(Swift3_Helper::get_permalink($new), array('priority' => 1));
            }, 10, 2);
      }
      
      private function _init_db(){
            $sql = "CREATE TABLE " . Swift3_Helper::$db->swift3_warmup . " (
                  id VARCHAR(32) NOT NULL,
                  url VARCHAR(500) NOT NULL,
                  priority INT(10) NOT NULL,
                  checksum VARCHAR(32) NOT NULL,
                  type VARCHAR(10) NOT NULL,
                  status INT(1) NOT NULL,
                  cts INT(11) NOT NULL,
                  ppts INT(11) NOT NULL,
                  sq VARCHAR(255) NOT NULL,
                  PRIMARY KEY (id),
                  KEY url (url),
                  KEY checksum (checksum),
                  KEY priority (priority),
                  KEY status (status),
                  KEY cts (cts),
                  KEY ppts (ppts),
                  KEY sq (sq)
            );";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta($sql);

            swift3_update_option('db_version', self::$version);
      }
      
      public function generate_urls(){
            // Prevent recursive execution
            if ( self::$is_running ) {
                  return;
            }
            self::$is_running = true;

            Swift3_Helper::increase_timeout(600, 'generate_urls');
            self::add_url(trailingslashit(home_url()), apply_filters('swift3_home_priority', 1));

            $post_types = self::get_post_types();
            foreach ($post_types as $post_type) {
                  $archive = get_post_type_archive_link($post_type);
                  if ($archive !== false){
                        self::add_url($archive, apply_filters('swift3_archive_priority', 3));
                  }
                  $taxonomy_objects = get_object_taxonomies($post_type, 'objects');
                  foreach ($taxonomy_objects as $key => $value) {
                        $terms = get_terms($key);
                        foreach ($terms as $term) {
                              $url = get_term_link($term);
                              if (preg_match('~(category|_cat)~', $key)){
                                    self::add_url($url, apply_filters('swift3_category_priority', 3, $term));
                              }
                              else {
                                    self::add_url($url, apply_filters('swift3_term_priority', 4, $term));
                              }
                        }
                  }
            }
            foreach (Swift3_Helper::$db->get_col("SELECT ID FROM " . Swift3_Helper::$db->posts . " WHERE post_status = 'publish' AND post_type = 'page'") as $post_id){
                  // Removed direct flush: wp_cache_flush();
                  self::add_url(Swift3_Helper::get_permalink($post_id), apply_filters('swift3_page_priority', 2), $post_id);
            }
            if (!empty($post_types)){
                  foreach ($post_types as $post_type){
                        foreach (Swift3_Helper::$db->get_results(Swift3_Helper::$db->prepare("SELECT ID, post_type FROM " . Swift3_Helper::$db->posts . " WHERE post_status = 'publish' AND post_type = %s ORDER BY post_modified DESC LIMIT %d", $post_type, SWIFT3_WARMUP_POST_TYPE_LIMIT)) as $post){
                              // Removed direct flush: wp_cache_flush();
                              self::add_url(Swift3_Helper::get_permalink($post->ID), apply_filters('swift3_single_priority', 5, $post), $post->ID);
                        }
                  }
            }
            // Schedule a single cache flush event after 1 second instead of calling wp_cache_flush() repeatedly
            wp_schedule_single_event( time() + 1, 'swift_flush_object_cache' );

            $max_allowed_packet        = Swift3_Helper::$db->get_row("SHOW VARIABLES LIKE 'max_allowed_packet'", ARRAY_A);
            $max_allowed_packet_size   = (isset($max_allowed_packet['Value']) && !empty($max_allowed_packet['Value']) ? $max_allowed_packet['Value']*0.9 : 1024*970);
            self::$urls = array_slice((array)self::$urls, 0, SWIFT3_WARMUP_LIMIT);
            $rows = array();
            $index = 0;
            foreach ((array)apply_filters('swift3_warmup_urls', self::$urls) as $id => $data){
                  if (self::validate_url($data['url']) == false){
                        continue;
                  }
                  $row = '("'.esc_sql($id).'", "' . esc_sql($data['url']) . '", ' . (int)$data['priority'] .')';

                  if (!isset($rows[$index])){
                        $rows[$index] = array();
                  }
                  if (strlen(implode($rows[$index]) . $row) > max($max_allowed_packet_size, 1024*970)){
                        $index++;
                  }

                  $rows[$index][] = $row;
            }
            foreach ($rows as $row){
                  $sql = "INSERT IGNORE INTO " . Swift3_Helper::$db->swift3_warmup . " (id, url, priority) VALUES " . implode(',', $row);
                  Swift3_Helper::$db->query("INSERT IGNORE INTO " . Swift3_Helper::$db->swift3_warmup . " (id, url, priority) VALUES " . implode(',', $row));
            }

            swift3_update_option('warmup', 1);

            self::$is_running = false;
      }
      
      public static function get_post_types(){
            $post_types = get_post_types(array('public' => true, 'publicly_queryable' => true));
            $excluded_post_types = Swift3_Exclusions::get_excluded_post_types();
            foreach ($excluded_post_types as $excluded_post_type){
                  if (isset($post_types[$excluded_post_type])){
                        unset($post_types[$excluded_post_type]);
                  }
            }
            return apply_filters('swift3_warmup_post_types', $post_types);
      }
      
      public static function add_url($url, $priority, $maybe_post_id = NULL){
            $id = self::get_id($url);
            $standardized_query = Swift3_Helper::standardize_query(parse_url($url, PHP_URL_QUERY));

            if (empty($standardized_query) && !Swift3_Exclusions::is_excluded($url) && (!isset(self::$urls[$id]) || $priority < self::$urls[$id]['priority'])){
                  self::$urls[$id] = array(
                        'url' => apply_filters('swift3_warmup_url_to_add', trailingslashit($url)),
                        'priority' => $priority
                  );
            }

            do_action('swift3_warmup_add_url', $url, $priority, $maybe_post_id);
      }
      
      public static function maybe_insert_url($url, $priority = 6){
            if (self::validate_url($url) == false){
                  return;
            }
            $id = self::get_id($url);
            Swift3_Helper::$db->query(Swift3_Helper::$db->prepare("INSERT IGNORE INTO " . Swift3_Helper::$db->swift3_warmup . ' (id, url, priority) VALUES (%s, %s, %d)', $id, $url, $priority));
      }
      
      public static function update_url($key, $data){
            if (!preg_match('~([abcdef0-9]{32})~', $key)){
                  $key = self::get_id($key);
            }

            Swift3_Helper::$db->update(Swift3_Helper::$db->prefix . 'swift3_warmup', $data, array(
                  'id' => $key
            ));
      }
      
      public static function get_urls_by_loop_post_type($post_type){
            return Swift3_Helper::$db->get_col(Swift3_Helper::$db->prepare("SELECT url FROM " . Swift3_Helper::$db->swift3_warmup . ' WHERE sq LIKE %s', '%|' . $post_type . '|%'));
      }
      
      public static function delete_url($url){
            $id = self::get_id($url);
            Swift3_Helper::$db->query(Swift3_Helper::$db->prepare("DELETE FROM " . Swift3_Helper::$db->swift3_warmup . " WHERE id = %s", $id));

            Swift3_Helper::delete_files(Swift3::get_module('cache')->get_cache_path($url));
      }
      
      public static function get_url_by_id($id){
            return Swift3_Helper::$db->get_var(Swift3_Helper::$db->prepare("SELECT url FROM " . Swift3_Helper::$db->swift3_warmup . " WHERE id = %s", $id));
      }
      
      public static function get_id($key){
            if (preg_match('~^([abcdef0-9]{32})$~', $key)){
                  return $key;
            }
            $url = html_entity_decode(urldecode($key));
            $query = parse_url($url, PHP_URL_QUERY);
            if (!empty($query)){
                  $url = str_replace($query, http_build_query(Swift3_Helper::standardize_query($query)), $url);
            }

            return md5(untrailingslashit(str_replace('//www.','//',$url)));
      }
      
      public static function get_content_type($key){
            $id = self::get_id($key);
            return Swift3_Helper::$db->get_var(Swift3_Helper::$db->prepare("SELECT type FROM " . Swift3_Helper::$db->swift3_warmup . " WHERE id = %s", $id));
      }
      
      public static function get_data($key){
            $id = self::get_id($key);
            return Swift3_Helper::$db->get_row(Swift3_Helper::$db->prepare("SELECT checksum, status, cts FROM " . Swift3_Helper::$db->swift3_warmup . " WHERE id = %s", $id));
      }
      
      public static function get_cache_status(){
            $expiry = intval(time() - SWIFT3_CACHE_LIFESPAN);
            $status = Swift3_Helper::$db->get_results("SELECT COUNT(*) 'total', COUNT(IF(status = -1, 1, NULL)) 'invalid', COUNT(IF(status = 0, 1, NULL)) 'uncached', COUNT(IF(status IN (-2, -3, -4, 1, 2, 3), 1, NULL)) 'cached', COUNT(IF(status IN (2, -3), 1, NULL)) 'queued', COUNT(IF(status IN (-2, -3, -4, -1, 0) OR cts < {$expiry}, 1, NULL)) 'revisit', COUNT(IF(status = 3, 1, NULL)) 'optimized' FROM " . Swift3_Helper::$db->swift3_warmup);
            return $status[0];
      }
      
      public static function validate_url($url){
            $url = wp_http_validate_url($url);
            if (empty($url) || strlen($url) > SWIFT3_MAX_URL_LENGTH){
                  return false;
            }
            if (strpos($url, home_url()) !== 0){
                  return false;
            }
            if (Swift3_Exclusions::is_excluded($url)){
                  return false;
            }

            return true;
      }
      
      public static function reset_ppts(){
            Swift3_Helper::$db->query("UPDATE " . Swift3_Helper::$db->swift3_warmup . " SET ppts = 0");
      }
      
      public static function reset(){
            Swift3_Helper::$db->query("TRUNCATE TABLE " . Swift3_Helper::$db->swift3_warmup);
            echo "TRUNCATE TABLE " . Swift3_Helper::$db->swift3_warmup;
            Swift3_Cache::purge_object();
            Swift3_Daemon::unlock_all();
            swift3_update_option('warmup', 0);
      }

}

// Add the cache flush event handler.
// This hook ensures that when the scheduled event fires,
// wp_cache_flush() is executed in a separate context.
add_action( 'swift_flush_object_cache', function() {
      wp_cache_flush();
});

?>

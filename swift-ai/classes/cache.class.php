<?php

class Swift3_Cache {

      public $buffer = '';

      public $current_url = NULL;

      private $_notes = array();

      private static $subqueries = array();
      private static $loop_cache_cleared = false;

      public function __construct(){
            if (swift3_check_option('caching', 'on')){
                  $this->load_cache();
                  foreach (array(
                        'save_post',
                        'delete_post',
                        'wp_trash_post',
                        'pre_post_update',
                        'delete_attachment',
                        'after_switch_theme',
                        'customize_save_after',
                        'wp_update_nav_menu',
                        'update_option_sidebars_widgets',
                        'activated_plugin',
                        'deactivated_plugin',
                        'upgrader_process_complete',
                  ) as $action){
                        add_action($action, array(__CLASS__, 'invalidate'));
                  }
                  foreach (array(
                        'update_option_permalink_structure',
                        'update_option_tag_base',
                        'update_option_category_base'
                  ) as $action){
                        add_action($action, array(__CLASS__, 'reset'));
                  }
                  add_action('transition_post_status', array(__CLASS__, 'handle_post_status'), 10, 3);
                  add_action('wp_set_comment_status', array(__CLASS__, 'handle_comment'), 10, 2);
                  add_action('init', array(__CLASS__, 'handle_user_actions'), 10, 2);
                  add_filter('pre_get_posts', array(__CLASS__, 'record_loop'));
                  add_action('wp', array($this, 'missing_assets_handler'), -PHP_INT_MAX);
                  ob_start(array($this, 'cache'));
            }

      }
      public function cache($buffer){
            $request_cacheable = $this->is_request_cacheable();
            $page_cacheable = $this->is_page_cacheable($buffer);

            if (Swift3_Helper::is_prebuild()){
                  if ($page_cacheable){
                        if ($request_cacheable){
                              @ini_set('display_errors', 0);
                              $checksum = Swift3_Helper::checksum($buffer);
                              $type = $this->measure_content_type();

                              call_user_func_array(apply_filters('swift3_cache_function', array($this, 'save_cache')), array($buffer, $type, $this));
                              $status = $this->transient_status($checksum);
                              Swift3_Warmup::update_url($this->get_current_url(), array('status' => $status, 'cts' => time(), 'type' => $type, 'checksum' => $checksum, 'sq' => '|' . implode('|', self::$subqueries) . '|'));
                        }
                  }
                  else {
                        Swift3_Warmup::delete_url($this->get_current_url());
                  }
            }
            else if ($page_cacheable && $request_cacheable && !$this->is_skippable()){
                  if (is_home() || is_front_page() || apply_filters('swift3_is_priority_1', false)){
                        $priority = 1;
                  }
                  else if (is_page() || apply_filters('swift3_is_priority_2', false)){
                        $priority = 2;
                  }
                  else if (is_category() || apply_filters('swift3_is_priority_3', false)){
                        $priority = 3;
                  }
                  else if (is_archive() || apply_filters('swift3_is_priority_4', false)){
                        $priority = 4;
                  }
                  else if (is_singular() || apply_filters('swift3_is_priority_5', false)){
                        $priority = 5;
                  }
                  else {
                        $priority = 6;
                  }

                  do_action('swift3_page_discovered', $this->get_current_url(), $priority);

                  Swift3_Warmup::maybe_insert_url($this->get_current_url(), $priority);
            }

            return $buffer;
      }

      public function save_cache($buffer, $type){
            $cache_path = $this->get_cache_path($this->get_current_url());

            if (!file_exists($cache_path)){
                  if (is_writeable(WP_CONTENT_DIR)){
                        mkdir($cache_path, 0777, true);
                        Swift3_Logger::rlog('cache-folder');
                  }
                  else {
                        Swift3_Logger::log(array('message' => sprintf(__('WP content directory (%s) is not writable for WordPress. Please change the permissions and try again.', 'swift3'), WP_CONTENT_DIR)), 'cache-folder-not-writable', 'wp-content-folder');
                  }
            }

            if (swift3_check_option('caching', 'on')){
                  if (file_exists($cache_path) && !file_exists($cache_path . '__index')){
                        mkdir($cache_path . '__index');
                  }

                  // Remove charset meta if exists
                  $buffer = preg_replace('~<meta charset([^>]+)>~', '', $buffer);

                  // Append charset to the top
                  $buffer = preg_replace('~<head([^>]*)?>~',"<head$1>\n<meta charset=\"".get_bloginfo('charset')."\">", $buffer, 1);
                  file_put_contents($cache_path . '__index/index.' . $this->get_device_postfix() . $type, $buffer);
                  if (swift3_check_option('keep-original-headers', 'on')){
                        $headers = self::get_original_headers();
                        $data_dir = Swift3_Optimizer::get_data_dir($this->get_current_url());
                        file_put_contents($data_dir . 'headers.json', json_encode($headers));
                        if (Swift3_Config::server_software() == 'apache'){
                              $header_htaccess = '';
                              foreach ((array)$headers as $key => $value) {
                                    $value = str_replace('"','\"',$value);
                                    $header_htaccess .= "Header add {$key} \"$value\"\n";
                              }
                              if (!empty($header_htaccess)){
                                    $header_htaccess = "<ifModule mod_headers.c>\n{$header_htaccess}</iFmodule>";
                                    file_put_contents($cache_path . '__index/.htaccess', $header_htaccess, true);
                              }
                        }
                  }
            }
      }

      public function load_cache(){
            add_filter('wp_headers', function($headers){
                  $miss_header = explode(':', apply_filters('swift3_miss_header', 'Swift3: MISS'));
                  $headers[$miss_header[0]] = trim($miss_header[1]);
                  return $headers;
            });
            if ($this->is_skippable()){
                  return;
            }

            $base_path = $this->get_cache_path($this->get_current_url());
            $cache_path = trailingslashit($base_path . '__index');
            $data_path = trailingslashit($base_path . '__data');

            if (file_exists($cache_path)){
                  $type = Swift3_Warmup::get_content_type($this->get_current_url());

                  switch ($type) {
                        case 'xml':
                              $content_type = 'text/xml';
                              break;
                        case 'json':
                              $content_type = 'application/json';
                              break;
                        case 'html':
                        default:
                              $type = 'html';
                              $content_type = 'text/html';
                              break;
                  }
                  if (swift3_check_option('keep-original-headers', 'on') && file_exists($data_path . 'headers.json')){
                        $headers = (array)json_decode(file_get_contents($data_path . 'headers.json'), true);
                        foreach ($headers as $key => $value){
                              header("{$key}: {$value}");
                        }
                  }

                  header("Content-type: {$content_type}");
                  header(apply_filters('swift3_cache_header', 'Swift3: HIT/PHP'));
                  readfile($cache_path . 'index' . $this->get_device_postfix() . ".{$type}");
                  die;
            }
            else {
                  $warmup_data = Swift3_Warmup::get_data($this->get_current_url());
                  if (!empty($warmup_data) && in_array($warmup_data->status, array(2,3))){
                        Swift3_Warmup::update_url($this->get_current_url(), array('status' => -1));
                  }
            }
      }

      public function is_prebuild(){
            return (isset($_SERVER['HTTP_X_PREBUILD']) && $_SERVER['HTTP_X_PREBUILD'] == '1');
      }

      public function is_request_cacheable(){
            $cacheable = true;
            if (!isset($_SERVER['REQUEST_METHOD'])){
                  $cacheable = false;
                  $this->add_note('is_request_cacheable', 'Request method is missing');
            }
            if (http_response_code() >= 300){
                  $cacheable = false;
                  $this->add_note('is_request_cacheable', 'Response code (' . http_response_code() . ')');
            }
            $query_string = parse_url($this->get_current_url(), PHP_URL_QUERY);
            $standardized_query = Swift3_Helper::standardize_query($query_string);

            if (!empty($standardized_query)){
                  foreach (Swift3_Exclusions::get_allowed_query_parameters() as $allowed_key) {
                        if (isset($standardized_query[$allowed_key])){
                              unset($standardized_query[$allowed_key]);
                        }
                  }

                  if (!empty($standardized_query)){
                        $cacheable = false;
                        $this->add_note('is_request_cacheable', 'Query string');
                  }
            }
            if (defined('XMLRPC_REQUEST')){
                  $cacheable = false;
                  $this->add_note('is_request_cacheable', 'XMLRPC request');
            }
            if (defined('WP_CLI') && WP_CLI){
                  $cacheable = false;
                  $this->add_note('is_request_cacheable', 'CLI request');
            }

            if ($this->is_maintenance()){
                  $cacheable = false;
                  $this->add_note('is_request_cacheable', 'Maintenance mode is active');
            }

            return apply_filters('swift3_is_request_cacheable', $cacheable);
      }

      public function is_page_cacheable($buffer = ''){
            $cacheable = true;

            $url = $this->get_current_url();

            $buffer = apply_filters('swift3_cache_buffer', $buffer);
            if (strpos($buffer, '<html') === false){
                  $cacheable = false;
                  $this->add_note('page_content_empty', 'This URL is not a HTML page');
            }
            if (apply_filters('swift3_is_admin', is_admin())){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', 'Admin page');
            }
            if (defined('REST_REQUEST')){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', 'REST API endpoint');
            }
            if (is_feed()){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', 'Feed');
            }
            if (is_404()){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', '404 page');
            }
            if ($this->is_password_protected()){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', 'Password protected');
            }
            $query_string = parse_url($url, PHP_URL_QUERY);
            $standardized_query = Swift3_Helper::standardize_query($query_string);
            if (!empty($standardized_query)){
                  foreach (Swift3_Exclusions::get_allowed_query_parameters() as $allowed_key) {
                        if (isset($standardized_query[$allowed_key])){
                              unset($standardized_query[$allowed_key]);
                        }
                  }

                  if (!empty($standardized_query)){
                        $cacheable = false;
                        $this->add_note('is_page_cacheable', 'Query string');
                  }
            }
            if (Swift3_Exclusions::is_url_excluded($url)){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', 'Page is excluded');
            }
            if (Swift3_Exclusions::is_url_match_excluded($url)){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', 'Page is excluded by configuration');
            }
            if (Swift3_Exclusions::is_post_type_excluded($url, get_the_id())){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', 'Post type is excluded by configuration');
            }
            if (Swift3_Exclusions::is_content_excluded($buffer, $url)){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', 'Content is excluded');
            }
            if (preg_match('~\.(xsl|php)$~', parse_url($url, PHP_URL_PATH), $matches)){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', $matches[0] . ' URLs are not cacheable');
            }
            if (parse_url($url, PHP_URL_HOST) !== parse_url(home_url(), PHP_URL_HOST)){
                  $cacheable = false;
                  $this->add_note('is_page_cacheable', 'Strict host (' . parse_url($url, PHP_URL_HOST) . ')');
            }

            return apply_filters('swift3_is_page_cacheable', $cacheable);
      }

      public function is_skippable(){
            $skippable = false;
            if (!isset($_SERVER['REQUEST_METHOD']) || !in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD'))){
                  $skippable = true;
            }
            foreach (Swift3_Exclusions::get_bypass_cookies() as $cookie){
                  if (isset($_COOKIE[$cookie])){
                        $skippable = true;
                        break;
                  }
            }
            if (defined('XMLRPC_REQUEST')){
                  $skippable = true;
            }
            if (defined('WP_CLI') && WP_CLI){
                  $skippable = true;
            }

            return apply_filters('swift3_skippable', $skippable);
      }

      /**
       * Check is maintenance mode active
       * @return boolean
       */
      public function is_maintenance(){
            $is_maintenance = false;
            if (file_exists( ABSPATH . '.maintenance' )){
                  global $upgrading;
                  include( ABSPATH . '.maintenance' );

                  if ((time() - $upgrading ) <= 600){
                        $is_maintenance = true;
                  }
            }

            return apply_filters('swift3_is_maintenance', $is_maintenance);
      }

      /**
      * Is current post password protected
      * @return boolean;
      */
      public function is_password_protected(){
            global $post;

            return apply_filters('swift3_is_password_protected', (isset($post->post_password) && !empty($post->post_password)));
      }

      public static function invalidate($object = NULL, $action = ''){
            $action = (empty($action) ? current_filter() : $action);

            switch ($action){
                  case 'save_post':
                  case 'pre_post_update':
                  case 'wp_set_comment_status':
                        $post_id = (is_object($object) ? $object->ID : (int)$object);
                        $post_type = get_post_type($post_id);
                        $url = Swift3_Helper::get_permalink($post_id);
                        self::purge_object($url);
                        self::invalidate_object(home_url());
                        if (!self::$loop_cache_cleared){
                              self::$loop_cache_cleared = true;
                              foreach (Swift3_Warmup::get_urls_by_loop_post_type($post_type) as $url){
                                    self::invalidate_object($url);
                              }
                        }
                        $archive_urls = self::get_archive_urls($post_id);
                        foreach ($archive_urls as $archive_url){
                              self::invalidate_object($archive_url);
                        }

                        break;
                  case 'delete_post':
                  case 'delete_attachment':
                  case 'wp_trash_post':
                        $post_id = (is_object($object) ? $object->ID : (int)$object);
                        $url = Swift3_Helper::get_permalink($post_id);
                        self::purge_object($url, true);
                        self::invalidate_object(home_url());
                        $archive_urls = self::get_archive_urls($post_id);
                        foreach ($archive_urls as $archive_url){
                              self::invalidate_object($archive_url);
                        }

                        break;
                  case 'after_switch_theme':
                  case 'customize_save_after':
                  case 'wp_update_nav_menu':
                  case 'update_option_sidebars_widgets':
                  case 'activated_plugin':
                  case 'deactivated_plugin':
                  case 'upgrader_process_complete':
                  default:
                        $url = '';
                        self::invalidate_object();
                        break;
            }


            do_action('swift3_invalidate_cache', $url, $object, $action);

      }
      public static function invalidate_object($url = NULL, $status = -4){
            if (empty($url)){
                  delete_transient('swift3_daemon');
                  Swift3_Helper::$db->query(Swift3_Helper::$db->prepare("UPDATE " . Swift3_Helper::$db->swift3_warmup . " set status = %d", $status));
            }
            else {
                  Swift3_Warmup::update_url($url, array('status' => $status));
            }

            do_action('swift3_invalidate_object', $url);
      }
      public static function purge_object($url = NULL, $remove_warmup = false){
            if (empty($url)){
                  delete_transient('swift3_daemon');
                  Swift3_Helper::$db->query("UPDATE " . Swift3_Helper::$db->swift3_warmup . " set status = 0");
                  Swift3_Helper::delete_files(Swift3::get_module('cache')->get_basedir());
            }
            else {
                  if ($remove_warmup){
                        Swift3_Warmup::delete_url($url);
                  }
                  else {
                        Swift3_Warmup::update_url($url, array('status' => 0));
                  }
                  Swift3_Helper::delete_files(Swift3::get_module('cache')->get_cache_path($url));
            }

            do_action('swift3_purge_cache', $url, $remove_warmup);
      }

      public static function reset(){
            $action = current_filter();
            swift3_update_option('warmup', 0);
            if(in_array($action, array('update_option_permalink_structure', 'update_option_tag_base','update_option_category_base'))){
                  Swift3_Helper::$db->query("DELETE FROM " . Swift3_Helper::$db->swift3_warmup . "");
                  Swift3_Helper::delete_files(Swift3::get_module('cache')->get_basedir());
            }
      }

      public static function handle_post_status($new, $old = '', $post = NULL){
            if (!empty($post)){
                  if ($new == 'publish'){
                        $url = Swift3_Helper::get_permalink($post->ID);
                        if (!empty($url)){
                              Swift3_Warmup::maybe_insert_url($url, ($post->post_type == 'page' ? 2 : 5));
                              self::invalidate($post->ID, 'save_post');
                        }
                  }
                  else if ($new == 'draft' && $old == 'publish'){
                        self::invalidate($post->ID, 'delete_post');
                  }
            }
      }

      public static function handle_comment($comment_id, $status){
            if (in_array($status, array('approve', 'hold'))){
                 $comment = get_comment( $comment_id );
                 self::invalidate($comment->comment_post_ID);
            }
      }
      public static function handle_user_actions(){
            if (isset($_POST['comment_post_ID']) && !empty($_POST['comment_post_ID']) && !Swift3_Helper::check_constant('INVALIDATE_CACHE_ON_COMMENT') ){
                  self::invalidate($_POST['comment_post_ID'], 'wp_set_comment_status');
            }
      }

      public function get_current_url(){
            if (!empty($this->current_url)){
                  return $this->current_url;
            }

            $this->current_url = Swift3_Helper::get_current_url();

            return $this->current_url;
      }

      public function get_device_postfix(){
            return apply_filters('swift3_cache_device', (Swift3_Helper::check_constant('SWIFT3_SEPARATE_DEVICES') && is_mobile() ? '-mobile' : ''));
      }

      public function get_basedir(){
            return apply_filters('swift3_cache_basedir', WP_CONTENT_DIR . '/swift-ai/cache/');
      }

      public function get_reldir(){
            return apply_filters('swift3_cache_reldir', str_replace(ABSPATH, '/' . Swift3_Helper::get_home_folder(), WP_CONTENT_DIR) . '/swift-ai/cache/');
      }

      public function get_baseuri(){
            return apply_filters('swift3_cache_baseuri', WP_CONTENT_URL . '/swift-ai/cache/');
      }

      public function get_cache_path($url = ''){
            $url_parts = parse_url(urldecode($url));

            $path = (!empty($url_parts['path']) ? trailingslashit(rtrim($url_parts['path'],'/') . (!empty($url_parts['query']) ? '/_q/' . md5(http_build_query(Swift3_Helper::standardize_query($url_parts['query']))) : '')) : '');
            return trailingslashit($this->get_basedir() . $this->get_host() . $path);
      }

      public function get_host(){
            return (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : parse_url(home_url(), PHP_URL_HOST));
      }

      public function measure_content_type(){
            $content_type = '';
            foreach (headers_list() as $header) {
                  if (preg_match('~Content-type:\s?([a-z]*)/([a-z]*)~i', $header, $matches)){
                        $content_type = $matches[1].'/'.$matches[2];
                  }
            }


            switch ($content_type) {
                  case 'text/xml':
                        return 'xml';
                        break;
                  case 'application/json':
                        return 'json';
                        break;

                  case 'text/html':
                  default:
                        return 'html';
                        break;
            }
      }

      public function transient_status($checksum){
            $url  = $this->get_current_url();
            $id   = Swift3_Warmup::get_id($url);

            $warmup_data = Swift3_Warmup::get_data($url);
            if (empty($warmup_data)){
                  return apply_filters('swift3_transient_cache_status', 0, 0, $url, $checksum, $this);
            }

            $status = 0;
            switch ($warmup_data->status){
                  case '-3':
                  case '3':
                        if (Swift3_Image::has_queued($id)){
                              $status = 1;
                        }
                        else if (Swift3::get_module('optimizer')->has_data()){
                              $status = 3;
                        }
                        else {
                              $status = (swift3_check_option('api_connection', 'duplex') ? 2 : 3);
                        }
                        break;
                  case '-2':
                        if (!Swift3_Image::has_queued($id)){
                              if ($checksum == $warmup_data->checksum && Swift3::get_module('optimizer')->has_data()){
                                    $status = 3;
                              }
                              else if (swift3_check_option('api_connection', 'duplex')){
                                    $status = 2;
                              }
                              else {
                                    $status = 3;
                              }
                        }
                        break;
                  case '-1':
                  case '-4':
                        if (Swift3_Image::has_queued($id)){
                              $status = 1;
                        }
                        else {
                              if ($checksum == $warmup_data->checksum && Swift3::get_module('optimizer')->has_data()){
                                    $status = 3;
                              }
                              else if (swift3_check_option('api_connection', 'duplex')){
                                    self::purge_object($url);
                                    $status = 0;
                              }
                              else {
                                    $status = 3;
                              }
                        }
                        break;
                  case '0':
                  default:
                        if (!Swift3_Image::has_queued($id)){
                              $status = (swift3_check_option('api_connection', 'duplex') ? 2 : 3);
                        }
                        else {
                              $status = 1;
                        }
                        break;
            }
            do_action('swift3_transient_status', $warmup_data->status, $status, $url);

            return apply_filters('swift3_transient_cache_status', $status, $warmup_data->status, $url, $checksum, $this);
      }
      public function add_note($group, $message){
            $this->_notes[$group][] = $message;
      }
      public function get_notes($group = ''){
            if (isset($this->_notes[$group])){
                  return $this->_notes[$group];
            }
            else {
                  $notes = array();
                  foreach ($this->_notes as $note){
                        $notes = array_merge($notes, (array)$note);
                  }
                  return $notes;
            }
      }
      public function missing_assets_handler(){
            if (isset($_SERVER['REQUEST_URI'])){
                  $cache_base_uri = parse_url($this->get_baseuri(), PHP_URL_PATH);
                  $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                  $protocol = (is_ssl() ? 'https://' : 'http://');
                  if (is_404() && preg_match('~^' . preg_quote($cache_base_uri, '~'). '(.*)~', $request_uri, $matches)){
                        $cache_uri = $protocol . strstr($matches[1], '__data', true);
                        self::invalidate_object($cache_uri);
                  }
            }
      }
      public static function get_archive_urls($post_id){
            $namespace  = 'wp/v2/';
            $urls = array();
            $categories = get_the_category($post_id);
            if (!empty($categories)){
                  foreach ((array)$categories as $category){
                        $urls[] = get_category_link($category->term_id);
                        if (function_exists('get_rest_url')){
                              $urls[] = get_rest_url() . $namespace . 'categories/' . $category->term_id . '/';
                        }
                  }
            }
            $tags = get_the_tags($post_id);
            if (!empty($tags)){
                  foreach ((array)$tags as $tag){
                        $urls[] = get_tag_link($tag->term_id);
                        if (function_exists('get_rest_url')){
      			      $urls[] = get_rest_url() . $namespace . 'tags/' . $tag->term_id . '/';
                        }
                  }
            }

            return array_filter(apply_filters('swift3_get_archive_urls', $urls, $post_id));
      }
      public static function get_original_headers(){
            $kept_headers = array();
            foreach ((array)headers_list() as $header){
                  preg_match('~([^:]+):\s?(.*)~', $header, $matches);
                  if (!empty($matches[1])){
                        if (in_array(strtolower($matches[1]), array('swift3', 'content-type', 'content-encoding', 'set-cookie'))){
                              continue;
                        }
                        $kept_headers[$matches[1]] = $matches[2];
                  }
            }

            return array_filter((array)apply_filters('swift3_kept_headers', $kept_headers));
      }
      public static function record_loop($query){
            if (!$query->is_main_query()){
                  foreach ((array)$query->get('post_type') as $post_type) {
                        if (in_array($post_type, apply_filters('swift3_subquery_excluded_post_types', array('wp_template_part', 'wp_global_styles', 'wp_template')))){
                              continue;
                        }
                        self::$subqueries[$post_type] = $post_type;
                  }
            }
      }

}


?>
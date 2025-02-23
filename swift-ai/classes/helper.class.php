<?php

class Swift3_Helper {

      public static $db;

      public static function init(){
            self::$db = $GLOBALS['wpdb'];
            self::$db->swift3_warmup = self::$db->prefix . 'swift3_warmup';
      }
      public static function check_constant($constant){
            $constant = 'SWIFT3_' . strtoupper($constant);
            return (defined($constant) && constant($constant));
      }
      public static function get_template($template, $type = 'tpl', $data = array()){
            switch ($type) {
                  case 'js':
                        $ext = '.js';
                        return apply_filters('swift3_get_template', file_get_contents(SWIFT3_DIR . 'templates/' . $template . '.js'), $template, $type);
                        break;
                  case 'tpl':
                  default:
                        ob_start();
                        include SWIFT3_DIR . 'templates/' . $template . '.tpl.php';
                        return apply_filters('swift3_get_template', ob_get_clean(), $template, $type);
                        break;
            }
      }
      public static function print_template($template, $type = 'tpl', $data = array()){
            echo self::get_template($template, $type, $data);
      }
      public static function get_svg($file, $localize = array()){

            $svg = file_get_contents(SWIFT3_DIR . 'assets/images/' . $file . '.svg');

            if (!empty($localize)){
                  foreach ($localize as $key => $value) {
                        $svg = str_replace($key, $value, $svg);
                  }
            }
            return $svg;
      }
      public static function print_svg($file, $localize = array()){
            echo self::get_svg($file, $localize);
      }
      public static function get_tld(){
            return implode('.', array_slice(explode('.', parse_url(site_url(), PHP_URL_HOST)), -2, 2));
      }
      public static function get_home_path(){
            $home    = set_url_scheme( get_option( 'home' ), 'http' );
            $siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );

            if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
                  $wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
                  $pos                 = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );
                  $home_path           = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
                  $home_path           = trailingslashit( $home_path );
            } else {
                  $home_path = ABSPATH;
            }

            return str_replace( '\\', '/', $home_path );
      }
      public static function get_home_folder(){
            $home_folder = trim(parse_url(site_url(), PHP_URL_PATH), '/');
            return (empty($home_folder) ? '' : trailingslashit($home_folder));
      }
      public static function get_root_path(){
            $home_folder = self::get_home_folder();
            return (empty($home_folder) ? ABSPATH : preg_replace('~' . $home_folder . '$~', '', ABSPATH));
      }
      public static function get_home_host(){
            $parts = parse_url(home_url());
            return trailingslashit($parts['host'] . (isset($parts['path']) ? $parts['path'] : ''));
      }
      public static function get_home_url() {
          return self::$db->get_var("SELECT option_value FROM " . self::$db->options . " WHERE option_name = 'home' LIMIT 1");
      }
      public static function get_permalink($post_id){
            return apply_filters('swift3_warmup_get_permalink', trailingslashit(get_permalink($post_id)));
      }
      public static function get_current_url($clean = false){
            $host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : parse_url(site_url(), PHP_URL_HOST));
            $url = (is_ssl() ? 'https' : 'http') . '://'. $host . $_SERVER['REQUEST_URI'];
            $query_string = parse_url($url, PHP_URL_QUERY);
            $standardized_query = Swift3_Helper::standardize_query($query_string);
            if ($clean){
                  if (!empty($standardized_query)){
				$allowed_query_strings = Swift3_Exclusions::get_allowed_query_parameters();
                        foreach ($standardized_query as $key => $value) {
                              if (!in_array($key, $allowed_query_strings)){
                                    unset($standardized_query[$key]);
                              }
                        }
                  }
            }
            if (!empty($query_string)){
                  $url = rtrim(str_replace($query_string, http_build_query($standardized_query), $url), '?');
            }

            return apply_filters('swift3_get_current_url',$url);
      }
      public static function get_registered_shortcodes(){
            global $shortcode_tags;
            $shortcodes = array_keys($shortcode_tags);
            sort($shortcodes);
            return $shortcodes;
      }
      public static function standardize_query($query){
            parse_str((string)$query, $query_array);
            $ignored_query_strings = Swift3_Exclusions::get_ignored_query_strings();
            foreach ($ignored_query_strings as $key){
                  if (isset($query_array[$key])){
                        unset($query_array[$key]);
                  }
            }

            ksort($query_array);
            return $query_array;
      }
      public static function checksum($code){
            $code = preg_replace('~([a-zA-Z0-9\x{00C0}-\x{FFFF}@:%._\\+\~#?&/=\-]{2,256})\.webp(\'|"|\s|,|\))+~iu', '', $code);
            $code = preg_replace('~<a(((?!href).)*)href=(\'|")([^\'"]*)(\'|")~i', '<a$1href=$3$3', $code);
            $code = preg_replace('~1(6|7)([0-9]{8})~i', '', $code);
            $code = preg_replace('~([0-9abcdef]{32})~i', '', $code);
            $code = preg_replace('~([0-9abcdef]{13})~i', '', $code);
            $code = preg_replace('~("|\'|=)([0-9abcdef]{10})("|\')?~i', '$3', $code);
            $code = preg_replace_callback('~<(a|article|aside|b|body|details|div|footer|h[123456]|header|i|label|li|main|mark|p|section|span|strong|summary|time|u)([^>]*)?>([^<]*)~', function($matches){
                  $length = strlen(preg_replace("~\s+~",' ', trim((string)$matches[3])));
                  if ($length > 50) {
                        $length = ($length + (50 -  $length % 50));
                  }
                  else if ($length > 20){
                        $length = 30;
                  }
                  else if ($length > 10){
                        $length = 20;
                  }
                  return '<' . $matches[1] . $matches[2] . '>' . ($length > 0 ? $length : '');
            }, $code);

            return md5(apply_filters('swift3_checksum_source', $code));
      }
      public static function delete_files($folder, $filter = ''){
            if (strpos($folder, '..') !== false || !file_exists($folder)){
                  return;
            }
            if (strpos(realpath($folder), realpath(WP_CONTENT_DIR . '/swift-ai')) !== 0){
                  return;
            }

            $files = scandir($folder);
            if (!empty($files)){
                  $files = array_diff($files, array('.','..'));
                  foreach ((array)$files as $file) {

                        if (is_dir($folder . '/'. $file)){
                              self::delete_files($folder . '/'. $file, $filter);
                        }
                        else if (empty($filter) || preg_match($filter, $file)){
                               @unlink($folder . '/'. $file);
                        }

                  }
            }

            @rmdir($folder);
      }
      public static function get_file_hash($file){
            if (file_exists($file)){
                  return md5_file($file);
            }
            return 0;
      }
      public static function maybe_checked($value, $check){
            echo ($value == $check ? ' checked' : '');
      }
      public static function maybe_selected($value, $check){
            echo ($value == $check ? ' selected' : '');
      }
      public static function increase_timeout($timeout = -1, $hook = ''){
            $default	= ini_get('max_execution_time');
            $timeout	= apply_filters('swift3_timeout_' . $hook, $timeout, $default);
            if (!Swift3_Helper::is_function_disabled('set_time_limit') && !Swift3_Helper::check_constant('DISABLE_TIMEOUT') && $timeout > $default){
                  set_time_limit($timeout);
                  return $timeout;
            }
            return $default;
      }
      public static function is_function_disabled($function_name) {
            $disabled = explode(',', ini_get('disable_functions'));
            $result = (in_array($function_name, $disabled) || !function_exists($function_name));
            return $result;
      }
      public static function is_local_url($url){
            $url_parts = parse_url($url);
            return (!isset($url_parts['PHP_URL_HOST']) || empty($url_parts['PHP_URL_HOST']) || $url_parts['PHP_URL_HOST'] == parse_url(home_url(), PHP_URL_HOST));
      }
      public static function is_amp($buffer) {
            return apply_filters('swift3_is_amp', (preg_match('~<html([^>])?\samp(\s|>)~', $buffer)));
      }
      public static function is_prebuild(){
            return (isset($_SERVER['HTTP_X_PREBUILD']) && $_SERVER['HTTP_X_PREBUILD'] == '1');
      }
      public static function get_abspath_from_url($url){
            $host = parse_url(home_url(), PHP_URL_HOST);
            if (preg_match('~(https?:)?//(www)?' . preg_quote($host) . '~', $url)){
                  return preg_replace('~(https?:)?//(www)?'  . preg_quote($host) . '~', ABSPATH, $url);
            }
            else if (preg_match('~^/~', $url)){
                  return ABSPATH . $url;
            }
            return false;
      }
      public static function get_script_tag($content, $attributes = array()){
            $node = new Swift3_HTML_Tag('<script></script>');
            $node->inner_html = $content;
            $node->attributes = $attributes;
            return (string)$node;
      }
      public static function get_style_tag($content, $attributes = array()){
            if (empty($content)){
                  return '';
            }

            $node = new Swift3_HTML_Tag('<style></style>');
            $node->inner_html = $content;
            $node->attributes = $attributes;
            return (string)$node;
      }

      public static function maybe_block_element($html){
            return preg_match('~<(address|article|aside|blockquote|canvas|dd|div|dl|dt|fieldset|figcaption|figure|footer|form|h1-h6|header|hr|li|main|nav|noscript|ol|p|pre|section|table|tfoot|ul|video)~', $html);
      }
      public static function ua_string($context = 'default'){
            return apply_filters('swift3_ua_string', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36', $context);
      }
      public static function remove_mixed_urls($css){
            return preg_replace('~url\s?\(\s?(\'|")?\s?http:~', 'url($1', $css);
      }
      public static function str2number($string){
            if (strpos($string, '.') !== false){
                  return (int)$string;
            }
            else {
                  return (float)$string;
            }
      }

      public static function add_submenu_page_array($slug, $submenus, $default_role = 'manage_options'){
            foreach ($submenus as $submenu){
                  if (!empty($submenu)){
                        add_submenu_page(
                              $slug,
                              $submenu['title'],
                              $submenu['title'],
                              (isset($submenu['role']) ? $submenu['role'] : 'manage_options'),
                              $submenu['slug'],
                              (isset($submenu['callback']) ? $submenu['callback'] : function(){})
                        );
                  }
            }
      }
      public static function normalize_url($address){
          $address = explode('/', $address);
          $keys = array_keys($address, '..');

          foreach($keys as $keypos => $key){
              array_splice($address, $key - ($keypos * 2 + 1), 2);
          }

          $address = implode('/', $address);
          $address = str_replace('./', '', $address);

          return $address;
      }
      public static function do_nothing(){}

}

Swift3_Helper::init();

?>
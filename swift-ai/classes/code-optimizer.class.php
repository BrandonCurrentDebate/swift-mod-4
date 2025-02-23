<?php

class Swift3_Code_Optimizer {

      public static $plugins = array();

      public static $disabled_plugins = array();

      public static $url;

      public static $query_string = array();

      public static $admin_notices;

      public static $admin_cache_raw = '';

      public function __construct(){
            if (isset($_SERVER['HTTP_SWIFT3_ADMIN_CACHE'])){

                  define('SWIFT3_DOING_ADMIN_CACHE', true);

                  add_action('admin_notices', function(){
                        if (is_user_logged_in()){
                              ob_start();
                        }
                  }, -PHP_INT_MAX);

                  add_action('admin_notices', function(){
                        if (is_user_logged_in()){
                              $admin_cache = get_user_meta(get_current_user_id(), '_swift3_admin_cache', true);
                              $admin_cache['notices'] = apply_filters('swift3_admin_cache_notices', ob_get_clean());
                              update_user_meta(get_current_user_id(), '_swift3_admin_cache', $admin_cache);
                        }
                  }, PHP_INT_MAX);

                  add_action('admin_bar_menu', function(){
                        if (is_user_logged_in()){
                              global $menu, $submenu, $_registered_pages, $wp_admin_bar;
                              update_user_meta(get_current_user_id(), '_swift3_admin_cache', array(
                                    'timestamp' => time(),
                                    'menu' => apply_filters('swift3_admin_cache_menu', $menu),
                                    'submenu' => apply_filters('swift3_admin_cache_submenu', $submenu),
                                    'admin_bar' => apply_filters('swift3_admin_cache_adminbar', $wp_admin_bar),
                                    'notices' => ''
                              ));
                        }
                  }, PHP_INT_MAX);
            }
            else {
                  add_action('admin_notices', function(){
                        if (!empty(self::$disabled_plugins) && self::is_current_admin_file(array('wp-admin', 'index.php'))){
                              self::$admin_cache_raw = Swift3_Helper::$db->get_var(Swift3_Helper::$db->prepare("SELECT meta_value FROM " . Swift3_Helper::$db->usermeta . " WHERE meta_key = '_swift3_admin_cache' AND user_id = %d", get_current_user_id()));
                              $admin_cache = maybe_unserialize(self::$admin_cache_raw);
                              if (!empty($admin_cache['notices'])){
                                    remove_all_actions('admin_notices');
                                    echo $admin_cache['notices'];
                              }
                        }
                  }, -PHP_INT_MAX);

                  add_action('admin_menu', function(){
                        if (!empty(self::$disabled_plugins)){
                              global $menu, $submenu, $_registered_pages, $_parent_pages;
                              $admin_cache = maybe_unserialize(self::$admin_cache_raw);

                              if (empty($admin_cache)){
                                    self::do_admin_cache(true);
                                    self::$admin_cache_raw = Swift3_Helper::$db->get_var(Swift3_Helper::$db->prepare("SELECT meta_value FROM " . Swift3_Helper::$db->usermeta . " WHERE meta_key = '_swift3_admin_cache' AND user_id = %d", get_current_user_id()));
                                    $admin_cache = maybe_unserialize(self::$admin_cache_raw);
                              }
                              else if ($admin_cache['timestamp'] + apply_filters('swift3_admin_cache_lifespan', 600) < time()){
                                    self::do_admin_cache();
                              }

                              $menu = $admin_cache['menu'];
                              $submenu = $admin_cache['submenu'];
                              //$submenu = $admin_cache['submenu'];
                              foreach ($admin_cache['submenu'] as $parent_slug => $_submenu){
                                    foreach ($_submenu as $_submenu_item){
                                          $menu_slug = $_submenu_item[2];
                                          $hookname = get_plugin_page_hookname( $menu_slug, $parent_slug );
                                          if (!preg_match('~([a-z]+)\.php~', $menu_slug) && !has_action($hookname)){
                                                add_action($hookname, array(__CLASS__, 'dummy'));
                                          }
                                    }
                              }
                        }
                  }, PHP_INT_MAX);

                  add_action('admin_bar_menu', function(){
                        if (is_admin() && !empty(self::$disabled_plugins)){
                              $admin_cache = maybe_unserialize(self::$admin_cache_raw);
                              if (!empty($admin_cache['admin_bar'])){
                                    global $wp_admin_bar;
                                    $wp_admin_bar = $admin_cache['admin_bar'];
                              }
                        }
                  }, PHP_INT_MAX);
            }

            if (!Swift3_Helper::check_constant('DOING_ADMIN_CACHE') && swift3_check_option('code-optimizer', 'on') && apply_filters('swift3_code_optimizer', true) && !isset($_GET['nocache'])){
                  if (isset($_SERVER['REQUEST_URI'])){
                        self::$url = parse_url(home_url($_SERVER['REQUEST_URI']));

                        if (!empty(self::$url['query'])){
                              parse_str(self::$url['query'], self::$query_string);
                        }

                        if (!self::is_current_admin_file(array('update.php', 'plugins.php'))){
                              add_filter('option_active_plugins', array(__CLASS__, 'manage'));
                        }
                  }
            }
            foreach (array(
                        'upgrader_process_complete',
                        'activated_plugin',
                        'deactivated_plugin',
                        'after_switch_theme'
            ) as $action){
                  add_action($action, array(__CLASS__, 'clear_admin_cache'));
            }
            add_action('admin_init', function(){
                  if (defined('DOING_AJAX') && isset($_REQUEST['action']) && preg_match('~dismiss~', $_REQUEST['action'])){
                        self::clear_admin_cache();
                  }
            });
            add_filter('wp_php_error_message', array(__CLASS__, 'rescue_link'));
            add_filter('wp_die_handler', function(){
                  return array(__CLASS__, 'wp_die_handler');
            });
      }

      public static function add($slug, $callback){
            self::$plugins[$slug] = $callback;
      }

      public static function init(){
            $mu_loader_path = WP_CONTENT_DIR . '/mu-plugins/__swift3-loader.php';
            $mu_loader_exists = file_exists($mu_loader_path);

            if (swift3_check_option('code-optimizer', 'on') && !$mu_loader_exists){
                  if (!file_exists(WP_CONTENT_DIR . '/mu-plugins')){
                        if (is_writeable(WP_CONTENT_DIR)){
                              @mkdir(WP_CONTENT_DIR . '/mu-plugins');
                        }
                        else {
                              Swift3_Logger::log(array('message' => sprintf(__('WP content directory (%s) is not writable for WordPress. Please change the permissions and try again.', 'swift3'), WP_CONTENT_DIR)), 'mu-plugins-folder-not-writable', 'wp-content-folder');
                              return;
                        }
                  }
                  $mu_loader = file_get_contents(SWIFT3_DIR . 'templates/mu-loader.tpl.php');
                  $mu_loader = str_replace(array('%PLUGIN_HEADER%', '%PLUGIN_DIR%', '%PLUGIN_BASENAME%'), array('Plugin Name: Swift3 Loader', SWIFT3_DIR, plugin_basename(SWIFT3_FILE)), $mu_loader);
                  file_put_contents($mu_loader_path, $mu_loader);
            }
            else if (!swift3_check_option('code-optimizer', 'on')){
                  if ($mu_loader_exists){
                        self::delete_mu_loader();
                  }
                  self::clear_admin_cache();
            }
      }

      public static function manage($active_plugins){
            remove_filter('option_active_plugins', array(__CLASS__, 'manage'));


            $original_active_plugins = $active_plugins;
            foreach ($active_plugins as $key => $plugin){
                  do_action('swift3_code_optimizer_before_check_plugin', $plugin);
                  if (isset(self::$plugins[$plugin]) && is_callable(self::$plugins[$plugin]) && call_user_func(self::$plugins[$plugin], $plugin, $original_active_plugins)){
                        self::$disabled_plugins[] = $plugin;
                        unset($active_plugins[$key]);
                  }
            }

            return $active_plugins;
      }

      public static function is_current_admin_file($files = array()){
            $current_file = basename(self::$url['path']);
            return in_array($current_file, (array)$files);
      }

      public static function do_admin_cache($blocking = false){
            if (Swift3_Helper::check_constant('DOING_ADMIN_CACHE')){
                  return;
            }
            if (!isset($_COOKIE[LOGGED_IN_COOKIE])){
                  return;
            }

            $cookies = [];
            foreach ($_COOKIE as $name => $value) {
                  if (preg_match('~wordpress_~', $name)){
                        $cookies[] = "{$name}={$value}";
                  }
            }
            $cookie_string = implode('; ', $cookies);
            $headers = array(
                'Cookie' => $cookie_string,
                'Swift3-admin-cache' => 1
            );

            wp_remote_get(admin_url(), array('headers' => $headers, 'sslverify' => false, 'timeout' => 60));
      }

      public static function delete_mu_loader(){
            $mu_loader_path = WP_CONTENT_DIR . '/mu-plugins/__swift3-loader.php';
            if (file_exists($mu_loader_path)){
                  unlink($mu_loader_path);
            }
      }
      public static function clear_admin_cache($uninstall = false){
            Swift3_Helper::$db->delete(Swift3_Helper::$db->usermeta, array('meta_key' => '_swift3_admin_cache'));
            if (!$uninstall && swift3_check_option('code-optimizer', 'on')){
                  self::do_admin_cache();
            }
      }
      public static function rescue_link($message){
            if (swift3_check_option('code-optimizer', 'on')){
                  if (!is_wp_error($message)){
                        $message .= Swift3_Helper::get_template('rescue');
                  }
            }
            return $message;
      }

      public static function wp_die_handler($message, $title = '', $args = array()){
            $message = self::rescue_link($message);
            _default_wp_die_handler($message, $title, $args);
      }
      public static function dummy(){}

}

?>
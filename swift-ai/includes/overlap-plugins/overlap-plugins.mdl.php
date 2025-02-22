<?php

class Swift3_Overlap_Plugins_Module {

      public $plugin_files = array();

      public function __construct(){
            add_action('plugins_loaded', array($this, 'maybe_deactivate'));

            add_action('admin_init', array($this, 'deactivate_plugins'));
      }

      public function maybe_deactivate(){
            if (class_exists('Swift_Performance')){
                  update_option('swift-performance-deactivation-settings', array(
                        'keep-settings' => 1,
                        'keep-custom-htaccess' => 0,
                        'keep-warmup-table' => 0,
                        'keep-image-optimizer-table' => 0,
                        'keep-logs' => 0,
                  ), false);

                  $this->plugin_files[] = self::locate('swift-performance/performance.php', 'class Swift_Performance');
                  Swift3_Logger::log(array('plugin' => __('Swift Performance 2', 'swift3')), 'plugin-deactivated', 'swift-performance');
            }
            if (defined('W3TC_DIR')){
                  $this->plugin_files[] = self::locate('w3-total-cache/w3-total-cache.php', "require_once W3TC_DIR . '/Cli.php'");
                  Swift3_Logger::log(array('plugin' => __('W3 Total Cache', 'swift3')), 'plugin-deactivated', 'w3-total-cache');
            }
            if (defined('AUTOPTIMIZE_PLUGIN_VERSION')){
                  $this->plugin_files[] = self::locate('autoptimize/autoptimize.php', 'new autoptimizeMain(');
                  Swift3_Logger::log(array('plugin' => __('Autoptimize', 'swift3')), 'plugin-deactivated', 'autoptimize');
            }
            if (defined('WP_ROCKET_VERSION')){
                  $this->plugin_files[] = self::locate('wp-rocket/wp-rocket.php', 'new WP_Rocket_Requirements_Check');
                  Swift3_Logger::log(array('plugin' => __('WP Rocket', 'swift3')), 'plugin-deactivated', 'wp-rocket');
            }
            if (defined('WPFC_WP_CONTENT_DIR')){
                  $this->plugin_files[] = self::locate('wp-fastest-cache/wpFastestCache.php', 'new WpFastestCache()');
                  Swift3_Logger::log(array('plugin' => __('WP Fastest Cache', 'swift3')), 'plugin-deactivated', 'wp-rocket');
            }
            if (defined('WPCACHECONFIGPATH')){
                  $this->plugin_files[] = self::locate('wp-super-cache/wp-cache.php', 'function wpsc_init()');
                  Swift3_Logger::log(array('plugin' => __('WP Super Cache', 'swift3')), 'plugin-deactivated', 'wp-rocket');
            }
            if (class_exists('Hummingbird\\WP_Hummingbird')){
                  $this->plugin_files[] = self::locate('hummingbird-performance/wp-hummingbird.php', 'namespace Hummingbird;');
                  Swift3_Logger::log(array('plugin' => __('Hummingbird', 'swift3')), 'plugin-deactivated', 'wp-rocket');
            }
            if (defined('SiteGround_Optimizer\PLUGIN_SLUG')){
                  $this->plugin_files[] = self::locate('sg-cachepress/sg-cachepress.php', 'namespace SiteGround_Optimizer;');
                  Swift3_Logger::log(array('plugin' => __('SG Optimizer', 'swift3')), 'plugin-deactivated', 'wp-rocket');
            }
            if (function_exists('cache_enabler_autoload')){
                  $this->plugin_files[] = self::locate('cache-enabler/cache-enabler.php', 'function cache_enabler_autoload');
                  Swift3_Logger::log(array('plugin' => __('Cache Enabler', 'swift3')), 'plugin-deactivated', 'wp-rocket');
            }
            if (defined('BREEZE_PLUGIN_DIR')){
                  $this->plugin_files[] = self::locate('breeze/breeze.php', 'require_once BREEZE_PLUGIN_DIR');
                  Swift3_Logger::log(array('plugin' => __('Breeze', 'swift3')), 'plugin-deactivated', 'wp-rocket');
            }
            if (function_exists('nitropack_is_wp_cli')){
                  $this->plugin_files[] = self::locate('nitropack/main.php', '\NitroPack\PluginStateHandler::init()');
                  Swift3_Logger::log(array('plugin' => __('Nitropack', 'swift3')), 'plugin-deactivated', 'wp-rocket');
            }



      }
      public function deactivate_plugins(){
            deactivate_plugins(apply_filters('swift3_deactivate_plugins', $this->plugin_files));
      }

      public static function locate($default_location, $footprint){
            $active_plugins = get_option('active_plugins');
            if (!in_array($default_location, $active_plugins)){
                  foreach ($active_plugins as $plugin){
                        $source = file_get_contents(trailingslashit(WP_PLUGIN_DIR) . $plugin);
                        if (strpos($source, $footprint) !== false){
                              return $plugin;
                        }
                  }
            }
            return $default_location;
      }
}

new Swift3_Overlap_Plugins_Module();

?>
<?php

class Swift3_Setup {

      private static $_steps = array();

      public function __construct(){
            if (swift3_check_option('install', 1, '!=') || isset($_GET['swift3-connect']) || (isset($_GET['swte-connect']) && $_GET['swte-connect'] == 'swift-performance')){
                  add_action('admin_init', array($this, 'start_installer'), 8);
            }
            if (isset($_GET['setup']) && isset($_GET['page']) && $_GET['page'] == 'swift3'){
                  add_action('admin_init', array($this, 'init'), 9);
            }
            if (isset($_GET['swift3-deactivate'])){
                  add_action('admin_init', array(__CLASS__, 'deactivate'));
            }
            add_filter('plugin_action_links', array(__CLASS__, 'plugin_links'), 10, 2);
      }
      public function init(){
            if (!current_user_can('manage_options')){
                  return;
            }
            $css_ver = hash('crc32', md5_file(SWIFT3_DIR . 'assets/css/setup.css'));
            wp_enqueue_style('swift3', SWIFT3_URL . 'assets/css/setup.css', array(), $css_ver);
		wp_enqueue_style( 'wp-admin' );
		if ($_GET['setup'] == 'install'){
                  if (swift3_check_option('activated', 1)){
                        $js_ver = hash('crc32', md5_file(SWIFT3_DIR . 'assets/js/install.js'));
                        wp_enqueue_script('swift3-install', SWIFT3_URL . 'assets/js/install.js', array('jquery'), $js_ver);
                        wp_localize_script('swift3-install', 'swift3_setup', array('nonce' => wp_create_nonce('swift3-admin'), 'i18n' => self::i18n()));
      			set_current_screen('swift3-install');
                        if(isset($_GET['reset'])){
                              Swift3_Config::reset_settings(true);
                        }
                        else {
                              swift3_update_option('api_connection','');
                        }

                        Swift3_Helper::print_template('setup/main', 'tpl', array('template' => 'install'));
                  }
                  else {
                        wp_redirect(add_query_arg(array('site' => home_url(), 'site-key' => Swift3_Api::get_site_key(), 'plugin'=> 'swift-performance', 'redirect_to' => urlencode(menu_page_url('swift3', false))), 'https://musthaveplugins.com/my-account/api/connect/'));
                        die;
                  }
		}
            else if ($_GET['setup'] == 'install-complete'){
                  set_current_screen('swift3-install-complete');
                  swift3_update_option('initial-messages', 1);
			Swift3_Helper::print_template('setup/main', 'tpl', array('template' => 'install-complete'));
		}
            else if ($_GET['setup'] == 'deactivate'){
                  set_current_screen('swift3-deactivate');
			Swift3_Helper::print_template('setup/main', 'tpl', array('template' => 'deactivate'));
		}
		die;
	}
      public static function install($step){
            $install_steps = self::get_install_steps();
            $progress = ceil(($step+1)/count($install_steps)*100);

            return array('text' => (is_callable($install_steps[$step]) ? call_user_func($install_steps[$step]) : $install_steps[$step]), 'progress' => $progress, 'redirect' => add_query_arg(array('page' => 'swift3', 'setup' => 'install-complete'), admin_url('tools.php')));
      }
      public static function get_install_steps(){
            $steps = array();
            self::add_step(10, sprintf(esc_html__('Server software: %s', 'swift3'), Swift3_Config::server_software()));
            $theme = wp_get_theme();
            self::add_step(20, sprintf(esc_html__('Active theme: %s', 'swift3'), $theme->name));
            $cache_status = Swift3_Warmup::get_cache_status();
            self::add_step(100, sprintf(esc_html(_n('%d page found', '%d pages found', $cache_status->total, 'swift3')), $cache_status->total));

            if (swift3_check_option('optimize-images', 'on')){
                  self::add_step(110,  esc_html__('Start optimizing images', 'swift3'));
            }

            do_action('swift3_get_install_steps');

            ksort(self::$_steps);

            foreach (self::$_steps as $step){
                  $steps = array_merge($steps, $step);
            }

            return $steps;
      }
      public static function add_step($priority, $step){
            self::$_steps[$priority][] = $step;
      }
      public static function i18n(){
            return array(
                  'Unknown error. Please refresh the page.' => __('Unknown error. Please refresh the page.', 'swift3'),
            );
      }
      public static function plugin_links($actions, $plugin_file){
            if ($plugin_file == plugin_basename(SWIFT3_FILE)){
                  if (isset($_GET['swift3-deactivate']) && $_GET['swift3-deactivate'] == 'temporary'){
                        unset($actions['deactivate']);
                  }
                  else {
                        $actions['deactivate'] = '<a href="' . add_query_arg(array('page' => 'swift3', 'setup' => 'deactivate'), admin_url('tools.php')) . '">' . esc_html('Deactivate', 'swift3') . '</a>';
                  }
            }
            return $actions;
      }
      public static function start_installer(){
            if (current_user_can('manage_options') && !defined('DOING_AJAX')){
                  if (isset($_GET['swift3-connect']) || (isset($_GET['swte-connect']) && $_GET['swte-connect'] == 'swift-performance')){
                        Swift3_Logger::rlogs('api-error');
                        Swift3_Logger::rlogs('license-error');
                        swift3_update_option('activated',1);
                        if (isset($_GET['mhp-license'])){
                              swift3_update_option('license-handling','v2');
                        }
                        else {
                              swift3_update_option('license-handling','v1');
                        }
                  }

                  wp_redirect(add_query_arg(array('setup' => 'install'), menu_page_url('swift3', false)));
                  swift3_update_option('install', 1);
                  die;
            }
      }
      public static function deactivate(){
            if (current_user_can('manage_options') && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'swift3-deactivate')){
                  switch ($_GET['swift3-deactivate']){
                        case 'temporary':
                              Swift3_Config::remove_rewrites();
                              deactivate_plugins(plugin_basename(SWIFT3_FILE));
                              break;
                        case 'uninstall':
                              deactivate_plugins(plugin_basename(SWIFT3_FILE));
                              self::turn_off_the_light();
                              break;
                  }
            }
      }
      public static function turn_off_the_light(){
            Swift3::get_module('api')->request('disconnect');

            Swift3_Config::remove_rewrites();
            Swift3_Cache::purge_object();
            Swift3_Helper::delete_files(WP_CONTENT_DIR . '/swift-ai');

            Swift3_Helper::$db->query("DROP TABLE " . Swift3_Helper::$db->swift3_warmup);
            Swift3_Code_Optimizer::delete_mu_loader();
            Swift3_Code_Optimizer::clear_admin_cache(true);

            delete_option('swift3_options');
            delete_option('swift3_log');
            delete_option('swift3_image_queue');
            delete_option('external_updates-swift3');
            delete_option('swift3_analytics');

            delete_transient('swift3_daemon');
            delete_transient('swift3_subscription_status');
            Swift3_Daemon::unlock_all();

            delete_plugins(array(plugin_basename(SWIFT3_FILE)));

            wp_redirect(admin_url('plugins.php'));
            die;
      }
}

return new Swift3_Setup();

?>
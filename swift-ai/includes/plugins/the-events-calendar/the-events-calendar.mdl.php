<?php

class Swift3_The_Events_Calendar_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('the-events-calendar/the-events-calendar.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('the-events-calendar');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('The Events Calendar detected', 'swift3'));
                        });
                  }
            });
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
            if (isset(Swift3_Code_Optimizer::$query_string['post_type']) && in_array((Swift3_Code_Optimizer::$query_string['post_type']), array('tribe_events', 'tribe_venue', 'tribe_organizer', ))){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~^((tribe|tec|pue-validate-key|stellarwp_installer)_|exit-interview|wp_async_request|)~', $_REQUEST['action'])){
                  return false;
            }

            add_action('admin_enqueue_scripts', function(){
                  if (strpos(Swift3_Code_Optimizer::$admin_cache_raw, 'tribe-notice-event') !== false)
                  wp_enqueue_style('tribe-events-admin-notice-install-event-tickets-css', WP_PLUGIN_URL . '/the-events-calendar/src/resources/css/admin/notice-install-event-tickets.min.css', array('wp-components'));
                  wp_enqueue_script('tribe-events-admin-notice-install-event-tickets-js', WP_PLUGIN_URL . '/the-events-calendar/src/resources/js/admin/notice-install-event-tickets.min.js');
            });

            return true;
      }

      public static function detected(){
            return defined('TRIBE_EVENTS_FILE');
      }

}

Swift3_The_Events_Calendar_Module::load();
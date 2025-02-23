<?php

class Swift3_Logger {

      public static $logs = array();

      public function __construct(){
            self::load();
            add_action('shutdown', array($this, 'save'));
            add_action('admin_notices', array(__CLASS__, 'admin_notices'));
      }

      public function save(){
            update_option('swift3_log', (array)self::$logs, false);
      }

      public static function load(){
            self::$logs = array_filter((array)get_option('swift3_log'));
      }
      public static function get_logs($group){
            $result = array();
            foreach (self::$logs as $hash => $entry){
                  if (!empty($entry) && $entry['group'] == $group){
                        $result[$hash] = $entry;
                  }
            }
            return $result;
      }
      public static function log($data, $group, $id){
            $hash = hash('crc32', $id);
            self::$logs[$hash] = array('data' => $data, 'group' => $group);
      }
      public static function rlog($id, $is_hash = false){
            $hash = ($is_hash ? $id : hash('crc32', $id));
            if (isset(self::$logs[$hash])){
                  unset(self::$logs[$hash]);
            }
      }
      public static function rlogs($group){
            foreach(self::get_logs($group) as $hash => $entry){
                  unset(self::$logs[$hash]);
            }
      }
      public static function get_groupped_entries(){
            $groupped = array();

            foreach (self::$logs as $key => $log_entry){
                  $groupped[$log_entry['group']][$key] = $log_entry;
            }

            return $groupped;
      }
      public static function get_messages(){
            $output = array();
            $groupped = self::get_groupped_entries();

            foreach ($groupped as $group => $log_entries){
                  switch($group){
                        case 'api-error':
                              foreach ($log_entries as $log_key => $log_entry){
                                    $message = sprintf(__('API connection error: %s', 'swift3'), $log_entry['data']['error']);
                                    $output[] = Swift3_Helper::get_template('log-entry', 'tpl', array('group' => $group, 'message' => $message));
                              }
                              break;
                        case 'prebuild-failed':
                        case 'optimization-failed':
                              if (count($groupped[$group]) >= 3){
                                    $action = ($group == 'prebuild-failed' ? __('Prebuild', 'swift3') : __('Optimization', 'swift3'));
                                    $errors = array();
                                    foreach ($groupped[$group] as $error_log_element){
                                          $errors[$error_log_element['data']['error']] = (isset($errors[$error_log_element['data']['error']]) ? $errors[$error_log_element['data']['error']] + 1 : 1);
                                    }
                                    $error_strings = array();
                                    foreach ($errors as $error_key => $error_value){
                                          $error_strings[] = $error_key . '(' . $error_value . ')';
                                    }

                                    $last = array_shift($groupped[$group]);
                                    $count = count($groupped[$group]);
                                    $message = sprintf(__('%s has been failed on %s and %d other pages. Error: %s. %sTry again%s', 'swift3'), $action, $last['data']['url'], $count, implode(', ', $error_strings), '<a href="#" class="swift3-btn swift3-btn-s" data-swift3="try-again">', '</a>');
                                    $output[] = Swift3_Helper::get_template('log-entry', 'tpl', array('group' => $group, 'message' => $message));
                              }
                              else {
                                    $action = ($group == 'prebuild-failed' ? __('prebuild', 'swift3') : __('optimization', 'swift3'));
                                    foreach ($log_entries as $log_key => $log_entry){
                                          $message = sprintf(__('%s %s has been failed. Error: (%s). %sTry again%s', 'swift3'), $log_entry['data']['url'], $action, $log_entry['data']['error'], '<a href="#" class="swift3-btn swift3-btn-s" data-swift3="try-again">', '</a>');
                                          $output[] = Swift3_Helper::get_template('log-entry', 'tpl', array('log-key' => $log_key, 'message' => $message));
                                    }
                              }
                              break;
                        case 'image-optimization-failed':
                              if (count($groupped[$group]) >= 3){
                                    $errors = array();
                                    foreach ($groupped[$group] as $error_log_element){
                                          $errors[$error_log_element['data']['error']] = (isset($errors[$error_log_element['data']['error']]) ? $errors[$error_log_element['data']['error']] + 1 : 1);
                                    }
                                    $error_strings = array();
                                    foreach ($errors as $error_key => $error_value){
                                          $error_strings[] = $error_key . '(' . $error_value . ')';
                                    }

                                    $last = array_shift($groupped[$group]);
                                    $count = count($groupped[$group]);
                                    $message = sprintf(__('Optimization of %s on %s and %d other images has been failed. Error: %s.', 'swift3'), $last['data']['file'], Swift3_Warmup::get_url_by_id($last['data']['page_id']), $count, implode(', ', $error_strings));
                                    $output[] = Swift3_Helper::get_template('log-entry', 'tpl', array('group' => $group, 'message' => $message));
                              }
                              else {
                                    foreach ($log_entries as $log_key => $log_entry){
                                          $message = sprintf(__('Optimization of %s on %s has been failed. Error: %s.', 'swift3'), $log_entry['data']['file'], Swift3_Warmup::get_url_by_id($log_entry['data']['page_id']), $log_entry['data']['error']);
                                          $output[] = Swift3_Helper::get_template('log-entry', 'tpl', array('log-key' => $log_key, 'message' => $message));
                                    }
                              }
                              break;
                        case 'plugin-deactivated':
                              $deactivated_plugins = array();
                              foreach ($log_entries as $log_key => $log_entry){
                                    $deactivated_plugins[] = $log_entry['data']['plugin'];
                              }
                              if (count($deactivated_plugins) > 1){
                                    $last_one = array_pop($deactivated_plugins);
                                    $message = sprintf(__('%s and %s has been deactivated.', 'swift3'), implode(', ', $deactivated_plugins), $last_one);
                              }
                              else {
                                    $message = sprintf(__('%s has been deactivated.', 'swift3'), implode(', ', $deactivated_plugins));
                              }
                              $output[] = Swift3_Helper::get_template('log-entry', 'tpl', array('group' => $group, 'message' => $message));
                        break;
                        default:
                              foreach ($log_entries as $log_key => $log_entry){
                                    $message = (isset($log_entry['data']['message']) ? $log_entry['data']['message'] : (isset($log_entry['data']['error']) ? $log_entry['data']['error'] : ''));
                                    if (!empty($message)){
                                          $output[] = Swift3_Helper::get_template('log-entry', 'tpl', array('log-key' => $log_key, 'message' => $message));
                                    }
                              }
                              break;
                  }
            }
            if (Swift3_Config::$is_development_mode){
                  $expiry = swift3_get_option('development-mode');
                  $time_format = get_option('time_format');
                  $the_day = (date('z') == date('z', $expiry) ? __('today', 'swift3') : __('tomorrow'));
                  $expiry_string = wp_date($time_format, $expiry) . ' ' . $the_day;
                  $message = '<span>' . sprintf(__('%sDevelopment Mode is active!%sCaching and optimization suspended until %s%s%s.', 'swift3'), '<strong>', '</strong><br><br>', '<strong>', $expiry_string, '</strong>') . '</span>';
                  $output[] = Swift3_Helper::get_template('log-entry', 'tpl', array('log-key' => 'devmode', 'message' => $message, 'permanent' => true));
            }

            return implode("\n", $output);
      }
      public static function print_messages(){
            echo self::get_messages();
      }
      public static function admin_notices(){
            $groupped = self::get_groupped_entries();

            if (isset($groupped['license-error'])){
                  foreach ($groupped['license-error'] as $error){
                        Swift3_Helper::print_template('admin-notice', 'tpl', array('message' => $error['data']['message']));
                  }
            }

      }
}
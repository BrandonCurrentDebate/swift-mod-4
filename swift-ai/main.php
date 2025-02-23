<?php
/**
 * Plugin Name: Swift Performance AI
 * Plugin URI: https://swiftperformance.io
 * Description: Intelligent, all in one optimization and cache plugin for WordPress
 * Version: 0.6.1
 * Author: Must-Have Plguins
 * Author URI: https://musthaveplugins.com
 * Text Domain: swift-performance
 */
define ('SWIFT3_FILE', __FILE__);
define ('SWIFT3_DIR', trailingslashit(__DIR__));
define ('SWIFT3_URL', trailingslashit(plugins_url(basename(__DIR__))));

include_once SWIFT3_DIR . "constants.php";
spl_autoload_register(function($class_name){
      if ($class_name == 'Swift3'){
            require_once SWIFT3_DIR . 'classes/core.class.php';
      }
      else {
            preg_match('~^Swift3_(.*)~', $class_name, $matches);
            if (isset($matches[1]) && !empty($matches[1]) ){
                  $filename = str_replace(array('.','_'), array('','-'), strtolower($matches[1]));
                  require_once SWIFT3_DIR . 'classes/'.$filename.'.class.php';
            }
      }
});

function swift3_get_option($key, $default = false){
      if (isset(Swift3::$options[$key])){
            return apply_filters('swift3_option_' . $key, Swift3::$options[$key]);
      }

      return apply_filters('swift3_option_not_set_' . $key, $default);
}

function swift3_set_option($key, $value){
      Swift3::$options[$key] = $value;
}

function swift3_update_option($key, $value){
      Swift3::$options[$key] = apply_filters('swift3_update_option_' . $key, $value);
      update_option('swift3_options', Swift3::$options);
      do_action('swift3_option_' . $key .'_updated', $value);
      do_action('swift3_option_updated', $key, $value);
}

function swift3_check_option($key, $value, $compare = '='){
      $option = swift3_get_option($key);

      switch ($compare){
            case '=':
                  $result = ($option == $value);
                  break;
            case '!=':
                  $result = ($option != $value);
                  break;
            case '<':
                  $result = (Swift3_Helper::str2number($option) < Swift3_Helper::str2number($value));
                  break;
            case '>':
                  $result = (Swift3_Helper::str2number($option) > Swift3_Helper::str2number($value));
                  break;
            case '<=':
                  $result = (Swift3_Helper::str2number($option) <= Swift3_Helper::str2number($value));
                  break;
            case '>=':
                  $result = (Swift3_Helper::str2number($option) >= Swift3_Helper::str2number($value));
                  break;
            case 'CONTAINS':
                  if (is_array($value)){
                        $result = in_array($option, $value);
                  }
                  else {
                        $result = (strpos($option, $value) !== false);
                  }
                  break;
            case 'IN':
                  $result = (is_array($option) && in_array($value, $option));
                  break;
      }

      return apply_filters('swift3_check_option_' . $key, $result, $value, $compare);
}

function swift3_check_options($options, $type = 'ANY'){
      $match = false;
      foreach ($options as $key => $check){
            $check = (array)$check;
            $match = swift3_check_option($key, $check[0], (isset($check[1]) ? $check[1] : '='));
            if (strtoupper($type) == 'ANY' && $match){
                  return true;
            }
      }
      return $match;
}

Swift3::get_instance();
<?php

class Swift_Performance_Autocomplete {

      public static $list = NULL;

      public function __construct(){
            Swift_Performance_Autocomplete::$list = get_option('swift_performance_autocomplete');

            if (empty(Swift_Performance_Autocomplete::$list) || !is_array(Swift_Performance_Autocomplete::$list)){
                  Swift_Performance_Autocomplete::$list = array(
                        'scripts'         => array(),
                        'styles'          => array(),
                        'shortcodes'      => array(),
                        'template_parts'  => array(),
                  );
            }

            add_action('shutdown', array(__CLASS__, 'save'));
      }

      public static function get_list($type){
            $list = array_filter((array)Swift_Performance_Autocomplete::$list[$type]);
            if (!empty($list)){
                  asort($list);

                  return array_combine($list, $list);
            }
            return array();
      }

      public static function collect($type, $value){
            // normalize URLs
            if (in_array($type, array('scripts', 'styles'))){
                  $url = parse_url($value);
                  if (empty($url['host']) || strpos($value, parse_url(home_url(), PHP_URL_HOST)) !== false){
                        $value = $url['path'];
                  }
                  else {
                        $value = $url['host'] . $url['path'];
                  }

            }
            Swift_Performance_Autocomplete::$list[$type][] = $value;
      }

      public static function save(){
            $normalized = array(
                  'scripts'         => array_unique(array_filter((array)Swift_Performance_Autocomplete::$list['scripts'])),
                  'styles'          => array_unique(array_filter((array)Swift_Performance_Autocomplete::$list['styles'])),
                  'shortcodes'      => array_unique(array_filter((array)Swift_Performance_Autocomplete::$list['shortcodes'])),
                  'template_parts'  => array_unique(array_filter((array)Swift_Performance_Autocomplete::$list['template_parts'])),
            );
            if (!empty($normalized)){
                  update_option('swift_performance_autocomplete', $normalized);
            }
      }

}

return new Swift_Performance_Autocomplete();
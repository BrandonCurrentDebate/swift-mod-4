<?php
class Swift3_Analytics {

      public function __construct(){
            add_action('swift3_rest_action_analytics/record', function(){
                  self::record('page');
            });
      }

      public static function record($type){
            switch ($type){
                  case 'page':
                        $wfe = intval(time() / 604800);
                        $swfe = swift3_get_option('wfe');
                        if ($swfe != $wfe){
                              Swift3_Warmup::reset_ppts();
                              swift3_update_option('wfe', $wfe);
                        }
                        $url = $_POST['url'];
                        $query_string = parse_url($url, PHP_URL_QUERY);

                        if (!empty($query_string)){
                              $standardized_query = Swift3_Helper::standardize_query($query_string);
                              $allowed_query_strings = Swift3_Exclusions::get_allowed_query_parameters();
                              $present_query_strings = array_keys($standardized_query);
                              $difference = array_diff((array)$present_query_strings, (array)$allowed_query_strings);
                              if (!empty($difference)){
                                    return;
                              }

                              $url = rtrim(str_replace($query_string, http_build_query($standardized_query), $url), '?');
                        }
                        $id = Swift3_Warmup::get_id($url);
                        Swift3_Helper::$db->query(Swift3_Helper::$db->prepare("UPDATE " . Swift3_Helper::$db->swift3_warmup . " SET ppts = ppts + 1 WHERE id = %s", $id));
                        break;
                  default:
                        $stats = get_option('swift3_analytics');

                        if (!isset($stats[$type])){
                              $stats[$type] = 0;
                        }

                        $stats[$type]++;
                        update_option('swift3_analytics', $stats, false);
                        break;

            }
      }

      public static function get($type){
            $stats = get_option('swift3_analytics');
            switch ($type){
                  case 'image':
                        return self::number_format((isset($stats[$type]) ? $stats[$type] : 0), true);
                        break;
                  case 'page':
                        $recorded = (isset($stats[$type]) ? $stats[$type] : 0);
                        return self::number_format($recorded * 100);
                        break;
            }
      }

      public static function number_format($number, $exact = false){
            $sign = ($exact ? '' : '≈ ');
            if ($number >= 1000000000){
                  return '> 1 B';
            }
            else if ($number >= 1000000){
                  return '≈ ' . round($number/1000000, 2) . ' M';
            }
            else if ($number >= 1000){
                  if ($exact){
                        return round($number/1000, 2) . ' K';
                  }
                  return '≈ ' . round($number/1000,2) . ' K';
            }
            else if (!$exact && $number < 100){
                  return '< 100';
            }
            else if (!$exact){
                  return '≈ ' . $number;
            }

            return $number;
      }
}

?>
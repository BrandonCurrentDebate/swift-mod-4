<?php

class Swift3_Etracker_Module {

      public static function init(){
            if (self::detected()){
                  add_filter('swift3_skip_js_optimization', function($result, $tag){
                        if (isset($tag->attributes['src']) && strpos($tag->attributes['src'], 'code.etracker.com') !== false){
                              return true;
                        }
                        else if (!isset($tag->attributes['src']) && strpos($tag->inner_html, '_etrackerOnReady') !== false){
                              return true;
                        }
                        return $result;
                  }, 10, 2);
            }
      }

      public static function detected(){
            return defined('ETRACKER_VERSION');
      }

}

add_action('init', array('Swift3_Etracker_Module', 'init'));
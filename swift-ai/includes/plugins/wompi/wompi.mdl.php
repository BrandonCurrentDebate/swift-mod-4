<?php

if(defined('WOO_WOMPI_PAYMENT_WWP_VERSION')){
      add_filter('swift3_skip_js_optimization', function($result, $tag){
            if (!empty($tag->attributes['src']) && strpos($tag->attributes['src'], 'checkout.wompi.co') !== false){
                  return true;
            }
            return $result;
      }, 10, 2);
}

?>
<?php

add_filter('http_request_args', function($args, $url){
      if (preg_match('~\.plesk\.page~', $url)){
            if (empty($args['headers'])) {
                  $args['headers'] = array();
            }

            $args['headers']['Cookie'] = 'plesk_technical_domain=1;' . (!isset($args['headers']['Cookie']) ? '' : $args['headers']['Cookie']);
      }
      return $args;
}, 10, 2);

?>
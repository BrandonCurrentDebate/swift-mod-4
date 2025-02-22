<?php

add_action('wp_head', function(){
      if (class_exists('COMPLIANZ')){
            echo '<style>body:not(.swift-js) .cmplz-cookiebanner{display:none};</style>';
      }
},8);
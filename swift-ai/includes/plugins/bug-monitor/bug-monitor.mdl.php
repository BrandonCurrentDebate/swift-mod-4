<?php

add_filter('swift3_avoid_blob', function($list){
      $list[] = 'html2canvas';
      return $list;
});
add_filter('swift3_skip_optimizer', function($result){
      if (isset($_GET['bm-preview'])){
            return true;
      }
      return $result;
});

?>
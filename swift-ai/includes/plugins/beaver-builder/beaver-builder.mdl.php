<?php
add_filter('swift3_skip_optimizer', function($result){
      if (isset($_GET['fl_builder'])){
            return true;
      }
      return $result;
});
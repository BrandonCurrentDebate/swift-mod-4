<?php

add_filter('swift3_skip_optimizer', function($result){
       if (isset($_GET['motionpage_iframe'])){
             return true;
       }
       return $result;
});
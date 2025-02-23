<?php

class Swift3_Brizy_Module {

      public function __construct(){
            add_filter('swift3_skip_optimizer', function($result){
                  if (isset($_GET['is-editor-iframe']) || (isset($_GET['action']) && $_GET['action'] == 'in-front-editor')){
                        return true;
                  }
                  return $result;
            });
      }
}

new Swift3_Brizy_Module();
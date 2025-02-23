<?php
class Swift3_Includes {
      public function __construct(){
            foreach(new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(SWIFT3_DIR . 'includes/')), '~\.mdl\.php$~', RegexIterator::GET_MATCH) as $file => $match) {
                  include_once $file;
            }
      }

}
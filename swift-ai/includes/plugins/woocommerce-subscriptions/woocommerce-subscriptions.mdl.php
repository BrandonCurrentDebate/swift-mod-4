<?php

class Swift3_WooCommerce_Subscriptions_Module {

      public static function load(){
            Swift3_Code_Optimizer::add('woocommerce-subscriptions/woocommerce-subscriptions.php', array('Swift3_WooCommerce_Module', 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('woocommerce-subscriptions');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('WooCommerce Subscriptions detected', 'swift3'));
                        });
                  }
            });
      }

      public static function detected(){
            return class_exists('WC_Subscriptions');
      }

}

Swift3_WooCommerce_Subscriptions_Module::load();
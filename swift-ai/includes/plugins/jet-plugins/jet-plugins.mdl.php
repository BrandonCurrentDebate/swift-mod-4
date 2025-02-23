<?php

class Swift3_Jet_Plugins_Module {

      public $is_elementor_rendering = false;

      public function __construct(){
            add_action('init', array(__CLASS__, 'init'));
            add_action('wp_ajax_jet_blog_smart_listing_get_posts', array(__CLASS__, 'smart_listing_ajax'), 9);
            add_action('wp_ajax_nopriv_jet_blog_smart_listing_get_posts', array(__CLASS__, 'smart_listing_ajax'), 9);
            Swift3_Exclusions::add_exclded_post_type('jet-menu');
            Swift3_Exclusions::add_exclded_post_type('jet-popup');
            Swift3_Exclusions::add_exclded_post_type('jet-theme-core');
            Swift3_Exclusions::add_exclded_post_type('jet-woo-builder');
            Swift3_Exclusions::add_exclded_post_type('jet-smart-filters');
            add_filter('elementor/widget/render_content', array($this, 'responsive_mega_menu'), 10, 2);
            add_filter('swift3_elementor_template_types', array(__CLASS__, 'template_types'));
            add_filter('swift3_after_assets_optimizer', array(__CLASS__, 'gmaps'));

      }

      public function responsive_mega_menu($widget_content, $that){
            if (!Swift3_Helper::is_prebuild() || $that->get_name() !== 'jet-mega-menu' || $this->is_elementor_rendering){
                  return $widget_content;
            }

            $this->is_elementor_rendering = true;
            $settings = $that->get_settings();

            $mobile_instance = new \Jet_Menu\Render\Mobile_Menu_Render( array(
                  'menu-id'                   => isset($settings[ 'menu' ]) ? $settings[ 'menu' ] : NULL,
                  'mobile-menu-id'            => isset($settings[ 'mobile-menu' ]) ? $settings[ 'mobile-menu' ] : NULL,
                  'layout'                    => isset($settings[ 'mobile-layout' ]) ? $settings[ 'mobile-layout' ] : NULL,
                  'toggle-position'           => isset($settings[ 'mobile-toggle-position' ]) ? $settings[ 'mobile-toggle-position' ] : NULL,
                  'container-position'        => isset($settings[ 'mobile-container-position' ]) ? $settings[ 'mobile-container-position' ] : NULL,
                  'item-header-template'      => isset($settings[ 'mobile-item-header-template' ]) ? $settings[ 'mobile-item-header-template' ] : NULL,
                  'item-before-template'      => isset($settings[ 'mobile-item-before-template' ]) ? $settings[ 'mobile-item-before-template' ] : NULL,
                  'item-after-template'       => isset($settings[ 'mobile-item-after-template' ]) ? $settings[ 'mobile-item-after-template' ] : NULL,
                  'use-breadcrumbs'           => isset($settings[ 'mobile-use-breadcrumbs' ]) ? filter_var( $settings[ 'mobile-use-breadcrumbs' ], FILTER_VALIDATE_BOOLEAN ) : NULL,
                  'breadcrumbs-path'          => isset($settings[ 'mobile-breadcrumbs-path' ]) ? $settings[ 'mobile-breadcrumbs-path' ] : NULL,
                  'toggle-text'               => isset($settings[ 'mobile-toggle-text' ]) ? $settings[ 'mobile-toggle-text' ] : NULL,
                  'toggle-loader'             => isset($settings[ 'mobile-toggle-loader' ]) ? filter_var( $settings[ 'mobile-toggle-loader' ], FILTER_VALIDATE_BOOLEAN ) : NULL,
                  'back-text'                 => isset($settings[ 'mobile-back-text' ]) ? $settings[ 'mobile-back-text' ] : NULL,
                  'is-item-icon'              => isset($settings[ 'mobile-is-item-icon' ]) ? filter_var( $settings[ 'mobile-is-item-icon' ], FILTER_VALIDATE_BOOLEAN ) : NULL,
                  'is-item-badge'             => isset($settings[ 'mobile-is-item-badge' ]) ? filter_var( $settings[ 'mobile-is-item-badge' ], FILTER_VALIDATE_BOOLEAN ) : NULL,
                  'is-item-desc'              => isset($settings[ 'mobile-is-item-desc' ]) ? filter_var( $settings[ 'mobile-is-item-desc' ], FILTER_VALIDATE_BOOLEAN ) : NULL,
                  'loader-color'              => isset($settings[ 'mobile-toggle-loader-color' ]) ? $settings[ 'mobile-toggle-loader-color' ] : NULL,
                  'sub-menu-trigger'          => isset($settings[ 'mobile-sub-menu-trigger' ]) ? $settings[ 'mobile-sub-menu-trigger' ] : NULL,
                  'sub-open-layout'           => isset($settings[ 'mobile-sub-open-layout' ]) ? $settings[ 'mobile-sub-open-layout' ] : NULL,
                  'close-after-navigate'      => isset($settings[ 'mobile-close-after-navigate' ]) ? filter_var( $settings[ 'mobile-close-after-navigate' ], FILTER_VALIDATE_BOOLEAN ) : NULL,
                  'toggle-closed-icon-html'   => isset($settings[ 'mobile-toggle-closed-state-icon' ]) ? $that->get_icon_html( $settings[ 'mobile-toggle-closed-state-icon' ] ) : NULL,
                  'toggle-opened-icon-html'   => isset($settings[ 'mobile-toggle-opened-state-icon' ]) ? $that->get_icon_html( $settings[ 'mobile-toggle-opened-state-icon' ] ) : NULL,
                  'close-icon-html'           => isset($settings[ 'mobile-container-close-icon' ]) ? $that->get_icon_html( $settings[ 'mobile-container-close-icon' ] ) : NULL,
                  'back-icon-html'            => isset($settings[ 'mobile-container-back-icon' ]) ? $that->get_icon_html( $settings[ 'mobile-container-back-icon' ] ) : NULL,
                  'dropdown-icon-html'        => isset($settings[ 'mobile-dropdown-icon' ]) ? $that->get_icon_html( $settings[ 'mobile-dropdown-icon' ] ) : NULL,
                  'dropdown-opened-icon-html' => isset($settings[ 'mobile-dropdown-opened-icon' ]) ? $that->get_icon_html( $settings[ 'mobile-dropdown-opened-icon' ] ) : NULL,
                  'breadcrumb-icon-html'      => isset($settings[ 'mobile-breadcrumb-icon' ]) ? $that->get_icon_html( $settings[ 'mobile-breadcrumb-icon' ] ) : NULL,
            ) );


            ob_start();
            $mobile_instance->render();
            $mobile_content = ob_get_clean();

            $this->is_elementor_rendering = false;

            return '<style>@media (max-width: 767px){.s3-jet-mega-menu-desktop-container{display: none;}}@media (min-width: 768px){.s3-jet-mega-menu-mobile-container{display: none;}}</style><div class="s3-jet-mega-menu-desktop-container">'.$widget_content.'</div><div class="s3-jet-mega-menu-mobile-container">'.$mobile_content.'</div>';
      }

      public static function init(){
            if (self::blog_detected()){
                  Swift3_System::register_includes('jet-blog');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('Jet Blog detected', 'swift3'));
                  });
            }
      }
      public static function smart_listing_ajax(){
            ob_start(function($buffer){
                  $decoded = json_decode($buffer);
                  if (!empty($decoded)){
                        $decoded->data->posts = Swift3::get_module('optimizer')->image_handler($decoded->data->posts);
                        $buffer = json_encode($decoded);
                  }

                  return $buffer;
            });
      }
      public static function gmaps($buffer){
            if (strpos($buffer, 'jet-map') !== false){
                  $script = Swift3_Helper::get_script_tag(file_get_contents(__DIR__ . '/gmaps.js'), array('type' => 'swift/javascript'));
                  $buffer = str_replace('</body>', $script . '</body>', $buffer);
            }
            return $buffer;
      }
      public static function template_types($labels){
            $labels['jet_header'] = __('Header', 'jet-theme-core');
            $labels['jet_footer'] = __('Footer', 'jet-theme-core');
            $labels['jet-popup'] = __('JetPopup', 'jet-popup');

            return $labels;
      }
      public static function blog_detected(){
            return apply_filters('swift3_jet_blog_detected', class_exists('Jet_Blog'));
      }

}

new Swift3_Jet_Plugins_Module();

?>
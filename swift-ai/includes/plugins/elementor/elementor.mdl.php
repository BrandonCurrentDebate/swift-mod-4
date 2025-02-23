<?php

class Swift3_Elementor_Module {

      public static $documents = array();

      public static $plugin_version = '3.x';

      public static function load(){
            if (file_exists(WP_PLUGIN_DIR . '/elementor/elementor.php')){
                  preg_match('~Version: ([\d\.]+)~', file_get_contents(WP_PLUGIN_DIR . '/elementor/elementor.php'), $matches);
                  if (!empty($matches[1])){
                        self::$plugin_version = $matches[1];
                  }

            }
            if (isset($_GET['elementor-preview'])){
                  add_filter('swift3_skip_optimizer', '__return_true');
            }
            add_action('init', array(__CLASS__, 'init'));
            add_action('elementor/core/files/clear_cache', array(__CLASS__, 'purge_cache'));
            add_filter('swift3_fragment_elementor', array(__CLASS__, 'widget_fragment'), 10, 2);
            add_filter('elementor/widget/render_content', array(__CLASS__, 'fragment_wrapper'), PHP_INT_MAX, 2);

            add_action('elementor/element/before_section_end', function( $element, $section_id ) {
                  if ($section_id == '_section_style'){
                        $element->start_injection(array(
                              'at' => 'after',
                              'of' => '_css_classes',
                        ));
                        // add a control
                        $element->add_control(
                              'swift-ajaxify',
                              array(
                                    'label' => __('Lazyload', 'swift3'),
                                    'type' => \Elementor\Controls_Manager::SWITCHER,
                              )
                        );
                        $element->end_injection();
                  }
            }, PHP_INT_MAX, 2);
            add_filter('swift3_subquery_excluded_post_types', function($post_types){
                  $post_types[] = 'elementor_font';
                  $post_types[] = 'elementor_snippet';
                  return $post_types;
            });
            Swift3_Code_Optimizer::add('elementor/elementor.php', array(__CLASS__, 'coop'));
            Swift3_Code_Optimizer::add('elementor-pro/elementor-pro.php', array(__CLASS__, 'coop'));
            add_action('wp', function(){
                  if (is_404() && isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] == parse_url(admin_url(), PHP_URL_PATH) . 'e-landing-page'){
                        if (Swift3_Helper::$db->get_var("SELECT COUNT(*) FROM " . Swift3_Helper::$db->posts . " WHERE post_type = 'e-landing-page' AND post_status IN ('draft', 'publish')") > 0){
                              wp_redirect(admin_url('edit.php?post_type=e-landing-page'));
                        }
                        else {
                              wp_redirect(admin_url('edit.php?post_type=elementor_library&page=e-landing-page'));
                        }
                  }
            });
      }

      public static function init(){
            if (self::detected()){
                  Swift3_System::register_includes('elementor');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('Elementor detected', 'swift3'));
                  });
                  Swift3_Exclusions::add_exclded_post_type('elementor_library');
                  add_filter('swift3_avoid_blob', function($arr){
                        $arr[] = 'assets/lib/dialog/';
                        $arr[] = 'assets/lib/share-link/';
                        $arr[] = 'assets/lib/swiper/';
                        return $arr;
                  });
                  if (swift3_check_option('collage', 'on')){
                        add_filter('elementor/documents/get/post_id', function($post_id){
                              self::$documents[$post_id] = $post_id;
                              return $post_id;
                        });

                        add_filter('swift3_collage_admin_bar_request', function($request){
                              $request[] = self::$documents;
                              return $request;
                        });
                        add_action('swift3_collage_before_admin_bar', function($data){
                              if (is_array($data[3]) && !in_array($data[1], $data[3])){
                                    return;
                              }

                              add_action('admin_bar_menu', function ($admin_bar) use ($data){
                                    $main_document = $data[1];
                                    $documents = array();

                                    if (get_post_type($main_document) != 'page'){
                                          return;
                                    }

                                    foreach ($data[3] as $post_id){
                                          if ($post_id != $main_document){
                                                $post_type = get_post_type($post_id);
                                                if (in_array($post_type, array('post', 'elementor_library'))){
                                                      continue;
                                                }

                                                $documents[] = array(
                                                      'id' => $post_id,
                                                      'title' => get_the_title($post_id),
                                                      'type' => self::get_template_type_label($post_id)
                                                );
                                          }
                                    }

                                    $admin_bar->add_menu(array(
                                          'id' => 'elementor_edit_page',
                                          'title' => __('Edit with Elementor', 'elementor'),
                                          'href' => add_query_arg(array('post' => $main_document, 'action' => 'elementor'), admin_url('post.php'))
                                    ));

                                    if (!empty($documents)){
                                          foreach ($documents as $document){
                                                $admin_bar->add_menu(array(
                                                      'parent' => 'elementor_edit_page',
                                                      'id' => 'elementor_edit_page' . $document['id'],
                                                      'title' => '<span class="elementor-edit-link-title">' . $document['title'] . '</span><span class="elementor-edit-link-type">' . $document['type'] . '</span>',
                                                      'href' => add_query_arg(array('post' => $document['id'], 'action' => 'elementor'), admin_url('post.php')),
                                                      'meta' => array(
                                                            'class' => 'elementor-general-section'
                                                      )
                                                ));
                                          }
                                    }

                                    $admin_bar->add_menu(array(
                                          'parent' => 'elementor_edit_page',
                                          'id' => 'elementor_edit_page_site_settings',
                                          'title' => '<span class="elementor-edit-link-title">' . __('Site Settings', 'elementor') . '</span><span class="elementor-edit-link-type">Site</span>',
                                          'href' => add_query_arg(array('post' => $document['id'], 'action' => 'elementor#e:run:panel/global/open'), admin_url('post.php')),
                                          'meta' => array(
                                                'class' => 'elementor-second-section'
                                          )
                                    ));

                                    $admin_bar->add_menu(array(
                                          'parent' => 'elementor_edit_page',
                                          'id' => 'elementor_edit_page_theme_builder',
                                          'title' => '<span class="elementor-edit-link-title">' . __('Theme Builder', 'elementor') . '</span><span class="elementor-edit-link-type">Site</span>',
                                          'href' => add_query_arg(array('page' => 'elementor-app#site-editor/promotion'), admin_url('admin.php')),
                                          'meta' => array(
                                                'class' => 'elementor-second-section'
                                          )
                                    ));

                              }, 90);
                        });
                        add_filter('swift3_collage_result_admin_bar', function($result){
                              $result['assets'][] = array('type'=>'style', 'data' => '#wp-admin-bar-elementor_edit_page > .ab-item::before {font-family: eicons!important;}');
                              return $result;
                        });

                        add_action('swift3_before_collage_admin_bar_assets', function(){
                              wp_enqueue_style('elementor-pro-notes-frontend', ELEMENTOR_PRO_URL . '/assets/css/modules/notes/frontend.min.css', array('elementor-icons'), ELEMENTOR_PRO_VERSION);
                        });
                  }
            }
      }
      public static function widget_fragment($html, $data){
            if (!empty($data[1])){
                  global $post;
                  $post = get_post($data[1]);
            }

            $widget_data = Swift3_Fragments::get_buffer($data[2]);
            $document = Elementor\Plugin::$instance->documents->get($data[1]);
            Elementor\Plugin::$instance->documents->switch_to_document($document);
            return $document->render_element($widget_data);
      }
      public static function fragment_wrapper($widget_content, $that){
            if (Swift3_Helper::check_constant('DOING_FRAGMENTS')){
                  return $widget_content;
            }

            $data = $that->get_data();

            $settings = apply_filters('swift3_elementor_widget_settings', $data['settings'], $widget_content, $that);

            if (isset($settings['swift-ajaxify']) && $settings['swift-ajaxify'] == 'yes' && apply_filters('swift3_elementor_widget_fragment', true, $that)){
                  $request = base64_encode(json_encode(array('elementor', get_the_ID(), Swift3_Fragments::set_buffer($that->get_data()))));
                  $widget_content = '<span id="s3-'.hash('crc32', $request).'" class="swift3-fragment" data-request="'.$request.'" data-callback="' . apply_filters('swift3_fragment_callback', '', $widget_content, $that) . '">' . $widget_content . '</span>';
            }
            return $widget_content;
      }

      public static function get_template_type_label($post_id){
            $labels = apply_filters('swift3_elementor_template_types', array(
                  'wp-post' => __('Post', 'elementor'),
                  'wp-page' => __('Page', 'elementor')
            ));

            $type = get_post_meta($post_id, '_elementor_template_type', true);

            if (isset($labels[$type])){
                  return $labels[$type];
            }
            else {
                  return ucfirst(str_replace(array('-','_'), ' ', $type));
            }
      }
      public static function detected(){
            return apply_filters('swift3_elementor_detected', defined('ELEMENTOR__FILE__'));
      }

      public static function purge_cache(){
            Swift3_Cache::purge_object();
      }
      public static function coop($plugin){
            if (!is_admin()){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('post-new.php') || (Swift3_Code_Optimizer::is_current_admin_file('post.php') && isset(Swift3_Code_Optimizer::$query_string['action']) && Swift3_Code_Optimizer::$query_string['action'] == 'elementor')){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file(array('edit-tags.php', 'edit.php')) && isset(Swift3_Code_Optimizer::$query_string['post_type']) && preg_match('~^(e-landing-page|elementor)~', Swift3_Code_Optimizer::$query_string['post_type'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file(array('post.php')) && isset(Swift3_Code_Optimizer::$query_string['action']) && in_array(Swift3_Code_Optimizer::$query_string['action'], array('trash', 'delete'))){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^(elementor|e\-form\-submissions|go_knowledge_base_site)~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && (preg_match('~elementor~', $_REQUEST['action']) || ($_REQUEST['action'] == 'heartbeat' && isset($_REQUEST['data']['elementor_post_lock'])))){
                  return false;
            }
            if ($plugin == 'elementor/elementor.php'){
                  add_action('admin_enqueue_scripts', function(){
                        wp_enqueue_style('elementor-icons', WP_PLUGIN_URL . '/elementor/assets/lib/eicons/css/elementor-icons.min.css', array(), self::$plugin_version);
                        wp_enqueue_style('elementor-admin', WP_PLUGIN_URL . '/elementor/assets/css/admin.min.css', array(), self::$plugin_version);
            	});
            }

      	add_filter('post_row_actions', function($actions, $post){
      		if (!!get_post_meta($post->ID, '_elementor_edit_mode', true)){
      			$actions['edit_with_elementor'] = sprintf(
      				'<a href="%1$s">%2$s</a>',
      				add_query_arg(array('post' => $post->ID, 'action' => 'elementor'), admin_url('post.php')),
      				__( 'Edit with Elementor', 'elementor' )
      			);
      		}
      		return $actions;
      	}, 11, 2);

            add_filter('page_row_actions', function($actions, $post){
      		if (!!get_post_meta($post->ID, '_elementor_edit_mode', true)){
      			$actions['edit_with_elementor'] = sprintf(
      				'<a href="%1$s">%2$s</a>',
      				add_query_arg(array('post' => $post->ID, 'action' => 'elementor'), admin_url('post.php')),
      				__( 'Edit with Elementor', 'elementor' )
      			);
      		}
      		return $actions;
      	}, 11, 2);

      	add_filter('display_post_states', function($states, $post){
      		if (!!get_post_meta($post->ID, '_elementor_edit_mode', true)){
      			$states['edit_with_elementor'] = 'Elementor';
      		}
      		return $states;
            }, 11, 2);


            return true;
      }

}

Swift3_Elementor_Module::load();
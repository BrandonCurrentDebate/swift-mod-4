<?php
class Swift3_Optimizer {

      public $data_dir = '';

      public $data = array();

      public $oversized_images = array();

      public $placeholders = array();

      public $cors = array();

      public static $container_id = 0;

      public function __construct(){
            add_action('init', array($this, 'init'));
            if (parse_url(Swift3_Helper::get_current_url(), PHP_URL_HOST) !== parse_url(home_url(), PHP_URL_HOST)){
                  return;
            }
            if (apply_filters('swift3_skip_optimizer', false) || isset($_GET['preview']) || isset($_GET['nocache']) || isset($_GET['swift-test-page'])){
                  return;
            }
            if ((isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch') || (isset($_SERVER['HTTP_PURPOSE']) && $_SERVER['HTTP_PURPOSE'] == 'prefetch')){
                  return;
            }
            add_action('template_redirect', function(){
                  if (!self::is_ignored()){
                        ob_start(array($this, 'assets_optimizer_callback'));
                  }
            });
            add_action('wp_head', array($this, 'optimize'), (Swift3_Helper::is_prebuild() ? 0 : 2));
            if (!is_admin() && !defined('REST_REQUEST') && !defined('DOING_CRON') && !defined('WP_CLI')){
                  ob_start(array(__CLASS__, 'early_nodes'));
            }
            if (swift3_check_option('optimize-css', 0, '>')){
                  add_filter('body_class', function($classes){
                        if (Swift3_Helper::is_prebuild()){
                              $classes[] = 'swift-nojs';
                              $classes[] = 'swift-noui';
                        }

                        return $classes;
                  });

                  // Remove the noui class to release scrolling on user interaction (ie user tried to scroll)
                  add_action('wp_head', function(){
                        echo Swift3_Helper::get_script_tag('[\'touchstart\', \'mousemove\'].forEach(function(e){document.addEventListener(e, function(){document.body.classList.remove(\'swift-noui\')}, {once: true});});', array('type' => (swift3_check_option('js-delivery', 0, '>') ? 'swift/normalscript' : 'text/javascript')));
                  }, PHP_INT_MAX);
            }
            if (swift3_check_option('optimize-images-on-upload', 'on')){
                  add_filter('wp_handle_upload', array('Swift3_Image', 'handle_upload'));
                  add_action('image_make_intermediate_size', array('Swift3_Image', 'handle_upload'));
            }
            add_filter('wp_delete_file', array('Swift3_Image', 'delete_image'));

            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'), PHP_INT_MAX);

      }

      public function init(){
            $this->data_dir = self::get_data_dir(Swift3_Helper::get_current_url());
      }

      public function image_handler($buffer, $main_call = true){
            $buffer = preg_replace_callback('~(alt|title|content|href)=([\'"])(.*?)\2~', function($matches){
                  return $matches[1] . '=' . $matches[2] . 'SwiftB64(' . base64_encode($matches[3]) . ')' . $matches[2];
            }, $buffer);
            if (swift3_check_option('optimize-images', 'on')){
                  $buffer = preg_replace_callback('~background-image:url\((\'|")?([^\'"\)]*)(\'|")?~i', function($matches){
                        return 'background-image:url('. $this->image_handler_callback($matches[2]);
                  }, $buffer);
            }
            if ($main_call){
                  $preload_mobile_regex = implode('|', array_map(function($url){
                        return preg_quote(preg_replace('~^https?:~','',htmlspecialchars_decode($url)), '~');
                  }, (array)$this->data['preloaded_images']['mobile']));
                  $preload_desktop_regex = implode('|', array_map(function($url){
                        return preg_quote(preg_replace('~^https?:~','',htmlspecialchars_decode($url)), '~');
                  }, (array)$this->data['preloaded_images']['desktop']));

                  $image_ids = array();
                  $images = self::get_nodes('img', $buffer);
                  foreach ($images as $image){
                        $maybe_decoded = json_decode('"' . $image . '"');
                        if (!empty($maybe_decoded) && $maybe_decoded !== $image){
                              $image = $maybe_decoded;
                              $encoded = true;
                        }
                        else {
                              $encoded = false;
                        }

                        $tag = apply_filters('swift3_image_handler_img_tag', new Swift3_HTML_Tag($image));

                        if (!isset($tag->attributes['src']) || strpos($tag->attributes['src'], 'data:image') === 0){
                              continue;
                        }

                        $id = hash('crc32', $tag->outer_html);
                        $image_ids[$id] = (isset($image_ids[$id]) ? $image_ids[$id] + 1 : 0);
                        $tag->attributes['data-s3image'] = $id . '/' . $image_ids[$id];
                        if (swift3_check_option('optimize-images', 'on') && isset($tag->attributes['src']) && !preg_match('~\.(webp|svg)((\?|#).*)?~', $tag->attributes['src'])){
                              $tag->attributes['src'] = $this->image_handler_callback($tag->attributes['src']);
                        }
                        if (swift3_check_option('responsive-images', 'on') && apply_filters('swift_optimize_image_size', true, $tag)){
                              $id = $tag->attributes['data-s3image'];
                              if (!empty($id) && isset($this->oversized_images->{$id})){
                                    $srcset = array();
                                    $max_width = $max_height = 0;

                                    foreach (array('desktop', 'mobile') as $device){
                                          $is2x = false;
                                          $width = $this->oversized_images->{$id}->{$device}->width;
                                          $height = $this->oversized_images->{$id}->{$device}->height;

                                          $max_width = ($width > $max_width ? ceil($width) : $max_width);
                                          $max_height = ($height > $max_height ? ceil($height) : $max_height);

                                          if (isset($this->oversized_images->{$id}->{$device}->image) && !empty($this->oversized_images->{$id}->{$device}->image)){
                                                $srcset_url = self::get_data_url($this->data_dir . $this->oversized_images->{$id}->{$device}->image);
                                                if (isset($this->oversized_images->{$id}->{$device}->image2x) && !empty($this->oversized_images->{$id}->{$device}->image2x)){
                                                      $is2x = true;
                                                      $srcset2x_url = self::get_data_url($this->data_dir . $this->oversized_images->{$id}->{$device}->image2x);
                                                }
                                                $media = ($device == 'desktop' ? '(min-width:768px)' : '(max-width:767px)');

                                                $srcset[] = $srcset_url . ' ' . round($width) . 'w';
                                                if ($is2x){
                                                      $srcset[] = $srcset2x_url  . ' ' . round($width) * 2 . 'w';
                                                }
                                                $imagesrcset = array();
                                                if ($is2x){
                                                      $imagesrcset = array('imagesrcset' => $srcset_url . ' 1x, ' . $srcset2x_url . ' 2x');
                                                }
                                                $buffer = str_replace(self::get_preload_tag(preg_replace('~^https?:~', '', $tag->attributes['src']), 'image', $media), self::get_preload_tag($srcset_url, 'image', $media, $imagesrcset), $buffer);
                                          }
                                    }
                                    if (!empty($srcset)){
                                          $tag->attributes['srcset'] = implode(', ', $srcset);
                                          unset($tag->attributes['sizes']);
                                    }
                                    if (apply_filters('swift3_image_fix_missing_dimensions', true, $tag)){
                                          if (!empty($max_width)){
                                                $tag->attributes['width'] = $max_width;
                                          }
                                          if (!empty($max_height)){
                                                $tag->attributes['height'] = $max_height;
                                          }
                                    }
                              }
                        }

                        if (swift3_check_option('lazyload-images', 'on')){
                              $preloaded_on_mobile = (!empty($preload_mobile_regex) && preg_match('~('.$preload_mobile_regex.')~', htmlspecialchars_decode($tag->attributes['src'])));
                              $preloaded_on_desktop = (!empty($preload_desktop_regex) && preg_match('~('.$preload_desktop_regex.')~', htmlspecialchars_decode($tag->attributes['src'])));
                              if ($preloaded_on_mobile && $preloaded_on_desktop){
                                    unset($tag->attributes['loading']);
                              }
                              else if ($preloaded_on_mobile){
                                    $tag->attributes['loading'] = 'mobile';
                                    $tag->attributes['fetchpriority'] = 'low';
                              }
                              else if ($preloaded_on_desktop){
                                    $tag->attributes['loading'] = 'desktop';
                                    $tag->attributes['fetchpriority'] = 'low';
                              }
                              else {
                                    $tag->attributes['loading'] = 'lazy';
                                    $tag->attributes['fetchpriority'] = 'low';
                              }
                        }

                        if (empty($tag->attributes['alt'])){
                              $alt = preg_replace('~\?(.*)~', '', pathinfo($tag->attributes['src'], PATHINFO_BASENAME));
                              $alt = preg_replace('~(\-|_|%20)~', ' ', $alt);
                              $alt = preg_replace('~(jpe?g|png|gif)?\.(webp|svg)~', '', $alt);
                              $tag->attributes['alt'] = trim($alt);
                        }

                        if ($encoded){
                              $image = substr(json_encode($image),1,-1);
                              $tag_html = substr(json_encode((string)$tag),1,-1);
                        }
                        else {
                              $tag_html = (string)$tag;
                        }

                        $buffer = preg_replace('~' . preg_quote($image, '~') . '~', $tag_html, $buffer, 1);
                  }
            }

            if (swift3_check_option('optimize-images', 'on')){
                  $buffer = preg_replace_callback('~([a-zA-Z0-9\x{00C0}-\x{FFFF}@:%._\\\\+\~#?&\/=\-]{2,256})\.(png|jpe?g|gif)(\?([^\'"\s,\)]*))?(\'|"|\s|,|\))~iu', function($matches) use ($buffer){
                        return $this->image_handler_callback($matches[1] . '.' . $matches[2] . $matches[3]) . $matches[5];
                  }, $buffer);
                  if ($main_call){
                        $webp_com = Swift3_Helper::get_template('script/webp', 'js');
                        $webp_com = str_replace('SWIFT3_IMAGE_DIR', trim(json_encode(apply_filters('swift3_image_reluri', WP_CONTENT_URL . '/swift-ai/images/')),'"'), $webp_com);
                        $buffer = str_replace('</body>', Swift3_Helper::get_script_tag($webp_com) . '</body>', $buffer);
                  }
            }
            $buffer = preg_replace_callback('~SwiftB64\(([^\)]*)\)~', function($matches){
                  return base64_decode($matches[1]);
            }, $buffer);

            return apply_filters('swift3_image_handler', $buffer, $main_call);
      }

      public function image_handler_callback($image_url){
            $is_escaped = false;

            $image_url = str_replace('&quot;', '', $image_url);
            if (preg_match('~\.php~', $image_url)){
                  return $image_url;
            }
            if (preg_match('~^(\s+)?data:image~', $image_url)){
                  return $image_url;
            }
            if (strpos($image_url, '{{') === 0){
                  return $image_url;
            }
            if (apply_filters('swift3_skip_image', false, $image_url)){
                  return $image_url;
            }

            if (strpos($image_url, '\/') !== false){
                  $is_escaped = true;
                  $image_url = json_decode('"' . $image_url . '"');
            }

            if (strpos($image_url, '\u') !== false){
                  $image_url = json_decode('"' . $image_url . '"');
            }

            $path = preg_replace('~(https?:)?//' . $_SERVER['HTTP_HOST'] . '~', '', $image_url);
            if (!empty($path) && strpos($path, '/') !== false){
                  $webp = Swift3_Image::get_webp_path($path);
                  if (preg_match('~\.webp$~', $image_url)){
                        if ($is_escaped){
                              $image_url = trim(json_encode($image_url), '"');
                        }
                        return $image_url;
                  }
                  if (file_exists($webp)){
                        $image_url = preg_replace('~^https?:~','',Swift3_Image::get_image_reluri($webp));
                  }
                  else {
                        $priority = $this->get_priority();
                        $current_url = Swift3_Helper::get_current_url();
                        $page_id = Swift3_Warmup::get_id($current_url);

                        Swift3_Image::queue_image($path, $priority, $page_id);
                  }

                  if ($is_escaped){
                        $image_url = trim(json_encode($image_url), '"');
                  }
            }

            return $image_url;
      }

      public function optimize(){
            if (self::is_ignored()){
                  return;
            }
            $this->reset_assets_folder();

            if ($this->has_critical_css()){
                  echo self::get_preload_tag(self::get_data_url($this->data_dir . 'critical-mobile.css?h=' . Swift3_Helper::get_file_hash($this->data_dir . 'critical-mobile.css')), 'style', '(max-width:767px)');
                  echo self::get_preload_tag(self::get_data_url($this->data_dir . 'critical.css?h=' . Swift3_Helper::get_file_hash($this->data_dir . 'critical.css')), 'style', '(min-width:768px)');
            }
            if (file_exists($this->data_dir . 'data.json')){
                  $this->data = array_filter((array)json_decode(file_get_contents($this->data_dir . 'data.json'), true));
                  if (!empty($this->data)){
                        echo "\n<!-- Optimized with Swift Performance AI -->\n";
                  }
            }

            if (file_exists($this->data_dir . 'oversized.json')){
                  $this->oversized_images = json_decode(file_get_contents($this->data_dir . 'oversized.json'));
            }

            if (file_exists($this->data_dir . 'placeholders.json')){
                  $this->placeholders = json_decode(file_get_contents($this->data_dir . 'placeholders.json'));
            }

            if (file_exists($this->data_dir . 'lazy-elements.json')){
                  $this->lazy_elements = json_decode(file_get_contents($this->data_dir . 'lazy-elements.json'));
            }

            if (file_exists($this->data_dir . 'cors.json')){
                  $this->cors = json_decode(file_get_contents($this->data_dir . 'cors.json'));
            }
            if (isset($this->oversized_images)){
                  if (swift3_check_option('enforce-image-size', 'on')){
                        $media = array('desktop' => array(), 'mobile' => array());
                        $css = '';

                        foreach ($this->oversized_images as $id => $image){
                              foreach (array('desktop', 'mobile') as $device){
                                    $vp = ($device == 'desktop' ? 1441 : 541);
                                    if (isset($image->{$device}->image) && !empty($image->{$device}->image) && !empty($image->{$device}->width) && !empty($image->{$device}->height)){
                                          if ($image->{$device}->is_fixed){
                                                $media[$device][] = '[data-s3image="' . $id . '"]{width:' . (int)$image->{$device}->width . 'px!important;}[data-s3image="' . $id . '"][data-src]{height:' . (int)$image->{$device}->height . 'px!important;}';
                                          }
                                          else {
                                                $media[$device][] = '[data-s3image="' . $id . '"]{width:' . (int)(($image->{$device}->width/$vp)*10000)/100 . 'vw!important;' . (!in_array($image->{$device}->width, array(1441,541)) ? 'max-width:' . $image->{$device}->width . 'px!important;' : '') . 'height:auto;}[data-s3image="' . $id . '"][data-src]{width:' . $image->{$device}->width . 'px!important;height:' . $image->{$device}->height . 'px!important;}';
                                          }
                                    }
                              }
                        }
                        if (!empty($media['mobile'])){
                              $css .= '@media (max-width: 767px){' . implode('', $media['mobile']) . '}';
                        }
                        if (!empty($media['desktop'])){
                              $css .= '@media (min-width: 768px) and (max-width: 1440px){' . implode('', $media['desktop']) . '}';
                        }

                        if (!empty($css)){
                              $responsive_image_css_file = $this->data_dir . hash('crc32', $css) . '.css';
                              if (!file_exists($responsive_image_css_file)){
                                    @file_put_contents($responsive_image_css_file, $css);
                              }

                              echo '<link rel="stylesheet" href="' . self::get_data_url($responsive_image_css_file) . '">';
                        }
                  }
                  else {
                        echo '<style>:where([data-s3image]){height: auto}</style>';
                  }
            }
            if (Swift3_Image::is_image_dir_exists() && swift3_check_options(array('optimize-images' => 'on', 'responsive-images' => 'on', 'lazyload-images' => 'on'))){
                  ob_start(array($this, 'image_handler'));
            }
            if ($this->has_critical_css()){
                  $mobile_critical_css = file_get_contents($this->data_dir . 'critical-mobile.css');
                  $desktop_critical_css = file_get_contents($this->data_dir . 'critical.css');
                  if (swift3_check_option('optimize-images', 'on')){
                        $mobile_critical_css = $this->image_handler($mobile_critical_css, false);
                        @file_put_contents($this->data_dir . 'critical-mobile.css', $mobile_critical_css);

                        $desktop_critical_css = $this->image_handler($desktop_critical_css, false);
                        @file_put_contents($this->data_dir . 'critical.css', $desktop_critical_css);
                  }
                  if (isset($this->data['critical_fonts'])){
                        foreach ((array)$this->data['critical_fonts'] as $critical_font){
                              $has_font_on_mobile = (strpos($mobile_critical_css, $critical_font) !== false);
                              $has_font_on_desktop = (strpos($desktop_critical_css, $critical_font) !== false);

                              if ($has_font_on_mobile && $has_font_on_desktop){
                                    echo self::get_preload_tag($critical_font, 'font');
                              }
                              else if ($has_font_on_mobile){
                                    echo self::get_preload_tag($critical_font, 'font', '(max-width:767)');
                              }
                              else if ($has_font_on_desktop){
                                    echo self::get_preload_tag($critical_font, 'font', '(min-width:768)');
                              }
                        }
                  }
                  echo Swift3_Helper::get_style_tag('html{opacity:0}body.swift-noui:not{overflow:hidden;height:100vh;}body.swift-nojs * {transition:none!important}', array('media' => 'swift/renderfix'));
                  echo '<link rel="swift/ccss" id="swift-dccss" href="'.self::get_data_url($this->data_dir . 'critical.css?h=' . Swift3_Helper::get_file_hash($this->data_dir . 'critical.css')).'" media="(min-width: 768px)">';
                  echo '<link rel="swift/ccss" id="swift-mccss" href="'.self::get_data_url($this->data_dir . 'critical-mobile.css?h=' . Swift3_Helper::get_file_hash($this->data_dir . 'critical-mobile.css')).'" media="(max-width: 767px)">';
            }
            $this->data['preloaded_images'] = array(
                  'mobile' => array(),
                  'desktop' => array()
            );
            if (isset($this->data['lcp']['desktop'])){
                  foreach ((array)$this->data['lcp']['desktop'] as $preload){
                        echo self::get_preload_tag($preload, 'image', '(min-width:768px)');
                        $this->data['preloaded_images']['desktop'][] = $preload;
                  }
            }

            if (isset($this->data['lcp']['mobile'])){
                  foreach ((array)$this->data['lcp']['mobile'] as $preload){
                        echo self::get_preload_tag($preload, 'image', '(max-width:767px)');
                        $this->data['preloaded_images']['mobile'][] = $preload;
                  }
            }
            if (swift3_check_option('lazyload-images', 'on') && isset($this->data['bgi']) && !empty($this->data['bgi'])){
                  echo Swift3_Helper::get_style_tag('[style*=background]:not(.swift-in-viewport){background-image: none !important;}');
            }
            if (isset($this->lazy_elements) && !empty($this->lazy_elements)){
                  $lazy_element_css = apply_filters('swift3_lazy_element_css', array('desktop' => '', 'mobile' => ''), $this->lazy_elements);
                  foreach ($lazy_element_css as $device => $css){
                        if (!empty($css)){
                              $media = ($device == 'desktop' ? '(min-width:768px)' : '(max-width:767px)');
                              echo Swift3_Helper::get_style_tag($css, array('media' => $media, 'data-type' => 'swift3-lazy-element'));
                        }
                  }
            }

            do_action('swift_after_header');
            echo $this->add_scripts();
            if ($this->has_webp_css()){
                  $webp_css = file_get_contents($this->data_dir . 'webp.css');
                  echo Swift3_Helper::get_style_tag($this->image_handler($webp_css, false), array('id' => 'swift3-webp', 'media' => 'swift/lazystyle'));
            }
      }

      public function assets_optimizer_callback($buffer){
            $buffer = apply_filters('swift3_before_assets_optimizer', $buffer);
            if (swift3_check_option('optimize-css', 0, '>')){
                  $styles = self::get_nodes('style', $buffer);
                  foreach ($styles as $style){
                        $tag = new Swift3_HTML_Tag($style);
                        // Embed huge inlined styles
                        if (strlen($tag->inner_html) > 1000){
                              $maybe_enqued = $this->enqueue_asset($tag->inner_html, 'style', $tag->attributes);
                              if (!empty($maybe_enqued)){
                                    $json_encoded = $tag->json_encoded;
                                    $tag = new Swift3_HTML_Tag($maybe_enqued);
                                    $tag->json_encoded = $json_encoded;
                                    $tag->remove_attribute('type');
                              }
                              else {
                                    $tag->attributes['data-s3style'] = hash('crc32', $tag->inner_html);
                              }
                        }
                        else {
                              $tag->attributes['data-s3style'] = hash('crc32', $tag->inner_html);
                        }

                        $buffer = str_replace($style, (string)$tag, $buffer);
                  }
            }
            if (swift3_check_option('optimize-css', 1, '>') && isset($this->data['shrinked_fonts']) && !empty($this->data['shrinked_fonts'])){
                  $buffer = str_replace('</body>', Swift3_Helper::get_style_tag(implode("\n", $this->data['shrinked_fonts']), array('media' => (Swift3_Helper::is_prebuild() ? 'swift/lazystyle' : 'all'))) . '</body>', $buffer);
            }
            if (swift3_check_option('js-delivery', 0, '>')){
                  $lazyscript_regex = (isset($this->data['lazyscript']) && !empty($this->data['lazyscript']) ? '~(' . implode('|', array_map(function($e){ return preg_quote($e, '~');}, (array)$this->data['lazyscript'])) . ')~' : '');
                  $scripts = self::get_nodes('script', $buffer);
                  foreach ($scripts as $script){
                        $tag = apply_filters('swift3_js_delivery_tag', new Swift3_HTML_Tag($script));
                        if (apply_filters('swift3_skip_js_optimization', false, $tag)){
                              $tag->attributes['type'] = 'swift/normalscript';
                              $buffer = str_replace($script, (string)$tag, $buffer);
                              continue;
                        }


                        if (!isset($tag->attributes['type']) || strpos($tag->attributes['type'], 'javascript') !== false || $tag->attributes['type'] == 'module'){
                              if (!empty($tag->attributes['data-s3waitfor'])){
                                    $script_type = apply_filters('swift3_script_type', 'swift/lazyscript', $tag);
                              }
                              else if (isset($tag->attributes['type']) && $tag->attributes['type'] == 'module'){
                                    $script_type = apply_filters('swift3_script_type', 'swift/module', $tag);
                              }
                              else {
                                    $script_type = apply_filters('swift3_script_type', (!empty($lazyscript_regex) && preg_match($lazyscript_regex, $tag->outer_html) ? 'swift/lazyscript' : 'swift/javascript'), $tag);
                              }
                              $tag->attributes['type'] = $script_type;

                              if (isset($tag->attributes['src'])){
                                    $tag->attributes['data-src'] = $tag->attributes['src'];
                                    $tag->attributes['src'] = '';
                              }

                              $buffer = str_replace($script, (string)$tag, $buffer);
                        }
                  }
                  $buffer = preg_replace('~\stype=(\'|")(text|application)/javascript(\'|")~', ' type="swift/javascript"', $buffer);
                  $buffer = preg_replace('~swift/normalscript~', 'text/javascript', $buffer);
                  $js_loader = Swift3_Helper::get_script_tag('swift_event("js", true);setTimeout(function(){document.body.classList.remove(\'swift-nojs\', \'swift-noui\');document.body.classList.add(\'swift-js\');},500);', array('type' => 'swift/javascript'));

                  // Add loader script
                  $js_loader .= Swift3_Helper::get_script_tag(str_replace('SWIFT3_ASSET_URI', SWIFT3_URL . 'assets', file_get_contents(SWIFT3_DIR . 'templates/script/loader.js')));

            }
            else {
                  $js_loader = Swift3_Helper::get_script_tag('document.dispatchEvent(new Event("swift/beforejs"));document.dispatchEvent(new Event("swift/js"));setTimeout(function(){document.body.classList.remove(\'swift-nojs\', \'swift-noui\');document.body.classList.add(\'swift-js\');},500);');
            }

            $buffer = str_replace('</body>', $js_loader . '</body>', $buffer);
            if (swift3_check_option('optimize-iframes', 0, '>')){
                  $iframes = self::get_nodes('iframe', $buffer);
                  foreach ($iframes as $iframe){
                        $tag = new Swift3_HTML_Tag($iframe);
                        if (apply_filters('swift3_skip_iframe_optimization', false, $tag)){
                              continue;
                        }
                        if (isset($tag->attributes['style']) && preg_match('~(display:(\s+)?none|width:(\s+)?(0|1)px)~', $tag->attributes['style'])){
                              continue;
                        }
                        if (!isset($tag->attributes['data-src']) && !empty($tag->attributes['src']) && $tag->attributes['src'] != 'about:blank'){
                              $tag->attributes['data-src'] = $tag->attributes['src'];
                              if(preg_match('~youtu\.?be~', $tag->attributes['src'])){
                                    $tag->attributes['data-src'] = add_query_arg('enablejsapi', 1, $tag->attributes['data-src']);
                              }

                              $tag->attributes['src'] = '';
                              $tag->attributes['loading'] = 'lazy';

                              $buffer = str_replace($iframe, (string)$tag, $buffer);
                        }
                  }
            }
            if (swift3_check_option('optimize-cls', 'on')){
                  $buffer = preg_replace_callback('~<(div|article|section|main|header|footer)([^>]+)~', function($matches){
                        Swift3_Optimizer::$container_id++;
                        preg_match('~=(\\\\?(\'|"))~', $matches[2], $quote_match);
                        $quote = (isset($quote_match[1]) ? $quote_match[1] : '');

                        return '<' . $matches[1] . ' data-s3cid=' . $quote .Swift3_Optimizer::$container_id. $quote . $matches[2];

                  }, $buffer);
            }

            if ($this->has_critical_css()){
                  if (isset($this->data['inline_styles']) && !empty($this->data['inline_styles'])){
                        $buffer = preg_replace_callback('~<style([^>]*)>~', function($styles){
                              if (preg_match('~('.implode('|', $this->data['inline_styles']).')~', $styles[0])){
                                    $styles[0] = str_replace('media=', 'data-media=', $styles[0]);
                                    $styles[0] = str_replace('<style', '<style media="swift/lazystyle"', $styles[0]);
                              }
                              return $styles[0];
                        }, $buffer);
                  }
                  if (isset($this->data['collected_styles'])){
                        $collected_styles = array_map(function($style) {
                              return preg_quote(preg_replace('~^https?:~','',$style), '~');
                        }, array_filter((array)$this->data['collected_styles']));
                        if (!empty($collected_styles)){
                              $buffer = preg_replace_callback('~<link[^>]+?rel=(\'|")stylesheet(\'|")[^>]*>~s', function($styles) use ($collected_styles){
                                    if (preg_match('~('.implode('|', $collected_styles).')~', htmlspecialchars_decode($styles[0]))){
                                          return preg_replace('~rel=(\'|")stylesheet(\'|")~', 'rel="swift/stylesheet"', $styles[0]);
                                    }
                                    return $styles[0];
                              }, $buffer);
                        }
                  }
            }

            return apply_filters('swift3_after_assets_optimizer', $buffer);
      }

      public function add_scripts(){
            $scripts = array();
            $scripts['defs'] = Swift3_Helper::get_template('script/defs', 'js');
            $scripts['dsfl'] = Swift3_Helper::get_template('script/dsfl', 'js');
            $scripts['events'] = Swift3_Helper::get_template('script/events', 'js');

            $avblb = array_map(function($str){return preg_quote($str);}, apply_filters('swift3_avoid_blob', array('blob:')));
            $avlif = array_map(function($str){return preg_quote($str);}, apply_filters('swift3_avoid_lazy_iframes', array()));
            $loader_vars = apply_filters('swift3_js_loader_vars', array(
                  'avblb' => implode('|', $avblb),
                  'avlif' => implode('|', $avlif),
                  'wv' => md5_file(SWIFT3_DIR . 'assets/js/worker.js')
            ));
            $scripts['loader_vars'] = 'window.s3loader_vars = ' . json_encode($loader_vars) . ';';
            $scripts['cfg'] = 'var _cfg = ' . Swift3_Config::get_json(array('optimize-iframes', 'optimize-js', 'js-delivery')) . ';';
            $scripts['analytics/1'] = 'var _anep = "' . Swift3_REST::get_url('analytics/record') . '";';
            $scripts['analytics/2'] = Swift3_Helper::get_template('script/analytics', 'js');
            if (!empty($this->placeholders)){
                  $scripts['plc'] = 'var _plc = ' . json_encode($this->placeholders) . ';';
            }
            if (!empty($this->cors)){
                  $scripts['cors'] = 'window.s3_cors = ' . json_encode($this->cors) . ';';
            }
            $aiscripts = '';
            if (isset($this->data['aiscripts']['all']) && !empty($this->data['aiscripts']['all'])){
                  $aiscripts .= implode("\n", $this->data['aiscripts']['all']);
            }
            if (isset($this->data['aiscripts']['desktop']) && !empty($this->data['aiscripts']['desktop'])){
                  $aiscripts .= 'if(window.innerWidth >= 768){'.implode("\n", $this->data['aiscripts']['desktop']) . '}';
            }
            if (isset($this->data['aiscripts']['mobile']) && !empty($this->data['aiscripts']['mobile'])){
                  $aiscripts .= 'if(window.innerWidth < 768){'.implode("\n", $this->data['aiscripts']['mobile']) . '}';
            }
            if (!empty($aiscripts)){
                  if (strpos($aiscripts, '__wf') !== false){
                        $scripts['wf'] = Swift3_Helper::get_template('script/waitfor', 'js');
                  }

                  $scripts['aiscripts'] = $aiscripts;
            }
            $scripts['iv'] = Swift3_Helper::get_template('script/iv', 'js');
            if (swift3_check_options(array('optimize-js' => 'on', 'optimize-iframes' => array(0, '>'), 'js-delivery' => array(0, '>')))){
                  $scripts['lazy-nodes'] = Swift3_Helper::get_template('script/lazy-nodes', 'js');;
            }
            if (swift3_check_option('optimize-iframes', 0, '>')){
                  $scripts['iframes'] = Swift3_Helper::get_template('script/iframes', 'js');
            }
            if (swift3_check_options(array('optimize-iframes' => array(0, '>'), 'optimize-css' => array(0, '>'), 'js-delivery' => array(0, '>'), 'optimize-js' => 'on'))){
                  $scripts['ui'] = Swift3_Helper::get_template('script/ui', 'js');
            }
            if (swift3_check_option('optimize-js', 'on')){
                  $scripts['jqo'] = Swift3_Helper::get_template('script/jqo', 'js');
            }
            $scripts['bgi_loader'] = Swift3_Helper::get_template('script/bgi-loader', 'js');
            $scripts['fragment/1'] = 'var _frep = "' . Swift3_REST::get_url('fragments') . '";';
            $scripts['fragment/2'] = Swift3_Helper::get_template('script/fragment', 'js');
            $scripts['self_loaded'] = 'swift_event("jsengine", true);';

            $html = '(function(s,w,i,f,t){' . implode("\n", apply_filters('swift3_optimizer_scripts', $scripts)). '})(12, window, document, "currentScript", -1);';
            $script_type = (swift3_check_option('js-delivery', 0, '>') ? 'swift/normalscript' : 'text/javascript');
            $maybe_enqued = $this->enqueue_asset($html, 'script', array('async' => '', 'type' => $script_type));
            if (!empty($maybe_enqued)){
                  return $maybe_enqued;
            }
            else {
                  return '<script type="'.$script_type.'">' . $html . '</script>';
            }
      }
      public function reset_assets_folder(){
            if (Swift3_Helper::is_prebuild()){
                  if (file_exists($this->data_dir . 'assets/')){
                        Swift3_Helper::delete_files($this->data_dir . 'assets');
                  }

                  mkdir($this->data_dir . 'assets/', 0777, true);
            }
      }
      public function enqueue_asset($content, $type, $attributes = array()){
            if ($type == 'script') {
                  $basename   = hash('crc32', $content) . '.js';
                  $file_path  = $this->data_dir . 'assets/' . $basename;

                  $tag = new Swift3_HTML_Tag('<script></script>');
                  $tag->attributes['src'] = self::get_data_url($file_path);
            }
            else {
                  $basename   = hash('crc32', $content) . '.css';
                  $file_path  = $this->data_dir . 'assets/' . $basename;

                  $tag = new Swift3_HTML_Tag('<link>');
                  $tag->attributes['rel'] = 'stylesheet';
                  $tag->attributes['href'] = self::get_data_url($file_path);
            }
            if (Swift3_Helper::is_prebuild()){
                  @file_put_contents($file_path, $content);
            }
            if (file_exists($file_path)){
                  foreach ($attributes as $key => $value){
                        $tag->attributes[$key] = $value;
                  }
                  return (string)$tag;
            }
            return false;

      }

      public function enqueue_frontend_scripts(){
            if (swift3_check_option('onsite-navigation', 'on')){
                  $prefetch_ver = hash('crc32', md5_file(SWIFT3_DIR . 'assets/js/prefetch.js'));
                  $ignored_by_user = explode("\n", swift3_get_option('ignored-prefetch-urls'));
                  $ignored_urls = array_filter(
                        array_merge(
                              array_map(
                                    function($e){
                                          return preg_quote($e, '/');
                                    },
                                    $ignored_by_user
                              ),
                              apply_filters('swift3_prefetch_ignore', array(
                                    'wp-login\.php',
                                    'logout',
                                    preg_quote(parse_url(admin_url(), PHP_URL_PATH), '/'),
                                    'nonce',
                                    '[0-9abcdef]{10}'
                              ))
                        )
                  );
                  sort($ignored_urls);
                  wp_enqueue_script('swift3-prefetch', SWIFT3_URL . 'assets/js/prefetch.js', array(), $prefetch_ver, true);
                  wp_localize_script('swift3-prefetch', 'swift3_prefetch_ignore', $ignored_urls);
            }

      }

      public function get_priority(){
            if (is_home() || is_front_page()){
                  return 1;
            }
            else if (is_page()){
                  return 2;
            }
            else if (is_post_type_archive() || is_category()){
                  return 3;
            }
            else if (is_archive()){
                  return 4;
            }
            else if (is_singular()){
                  return 5;
            }
            else {
                  return 6;
            }
      }
      public function has_data($postfix = ''){
            return (!empty($this->data_dir) && file_exists($this->data_dir . 'data.json'));
      }
      public function has_critical_css($postfix = ''){

            //We bypass Critical CSS on POST request, because HTML is usually different, and Critical CSS is not accurate
            if (!Swift3_Helper::is_prebuild() && $_SERVER['REQUEST_METHOD'] == 'POST'){
                  return false;
            }

            return (!empty($this->data_dir) && file_exists($this->data_dir . 'critical' . $postfix . '.css'));
      }
      public function has_webp_css(){
            return (!empty($this->data_dir) && file_exists($this->data_dir . 'webp.css'));
      }
      public static function get_data_dir($url){
            $dir = Swift3::get_module('cache')->get_cache_path($url) . '__data/';
            if (!file_exists($dir)){
                  if (is_writable(WP_CONTENT_DIR)){
                        @mkdir ($dir, 0777, true);
                  }
                  else {
                        Swift3_Logger::log(array('message' => sprintf(__('WP content directory (%s) is not writable for WordPress. Please change the permissions and try again.', 'swift3'), WP_CONTENT_DIR)), 'cache-folder-not-writable', 'wp-content-folder');
                  }
            }
            return $dir;
      }
      public static function delete_data_dir($url){
            $dir = self::get_data_dir($url);
            if (file_exists($dir)){
                  Swift3_Helper::delete_files($dir);
            }
      }
      public static function get_data_url($file){
            return str_replace(ABSPATH, site_url('/'), $file);
      }
      public static function get_preload_tag($href, $as, $media = false, $extra = array()){
            if (strpos($href, 'data:') === 0){
                  return '';
            }

            $extra_attributes = array();
            if (!empty($extra)){
                  foreach ($extra as $key => $value){
                        $extra_attributes[] = esc_attr($key) . '="' . esc_attr($value) . '"';
                  }
            }

            if ($as == 'style' && !empty($media)){
                  $rel = 'swift/preload';
            }
            else {
                  $rel = 'preload';
            }

            return '<link rel="' . $rel . '" href="' . esc_attr($href) . '" as="' . esc_attr($as) . '"' . (!empty($media) ? ' media="' . esc_attr($media) .'"' : '') . (!empty($extra) ? ' ' . implode(' ', $extra_attributes) : '') . ' crossorigin>';
      }
      public static function get_nodes($tag_name, $html){
            if (is_array($tag_name)){
                  $nodes = array();
                  foreach ($tag_name as $_tag_name){
                        $nodes = array_merge(self::get_nodes($_tag_name, $html), $nodes);
                  }

                  return $nodes;
            }

            if (in_array($tag_name, array('img', 'br', 'link', 'meta'))){
                  preg_match_all('~<'.$tag_name.'(.*?)>~s', $html, $matches);
                  return $matches[0];
            }
            else {
                  $nodes = array();
                  $in_tag = $in_escape = $in_regex = $start = false;
                  $in_quote = $in_comment = '';

                  for ($i = 0; $i < strlen($html); $i++){
                        if (!$in_tag){
                              $maybe_open = '';
                              for ($j = $i; $j < min(strlen($html), $i + strlen($tag_name)+1); $j++){
                                    $maybe_open .= $html[$j];
                              }
                              if ($maybe_open == '<' . $tag_name){
                                    $in_tag = true;
                                    $start = $i;
                              }
                        }
                        if ($in_tag && empty($in_comment) && empty($in_regex) && in_array($html[$i], array('"', "'"))){
                              if (empty($in_quote)){
                                    $in_quote = $html[$i];
                              }
                              else if ($in_quote == $html[$i]){
                                    $in_quote = '';
                              }
                        }
                        if (in_array($tag_name, array('script', 'style'))){
                              if ($in_tag && empty($in_quote) && empty($in_comment) && $html[$i] == '\\' && isset($html[$i+1]) && $html[$i+1] == '/' && isset($html[$i+2]) && $html[$i+2] == '*'){
                                    $in_comment = 'full_json';
                              }
                              else if ($in_tag && empty($in_quote) && empty($in_comment) && $html[$i] == '/' && isset($html[$i+1]) && $html[$i+1] == '*'){
                                    $in_comment = 'full';
                              }
                              else if ($tag_name == 'script' && $in_tag && empty($in_quote) && empty($in_comment) && $html[$i] == '/' && isset($html[$i+1]) && $html[$i+1] == '/'){
                                    $in_comment = 'line';
                              }

                              if ($in_tag && $in_comment == 'full_json' && $html[$i] == '*' && isset($html[$i+1]) && $html[$i+1] == '\\' && isset($html[$i+2]) && $html[$i+2] == '/'){
                                    $in_comment = '';
                              }
                              else if ($in_tag && $in_comment == 'full' && $html[$i] == '*' && isset($html[$i+1]) && $html[$i+1] == '/'){
                                    $in_comment = '';
                              }
                              else if ($tag_name == 'script' && $in_tag && $in_comment == 'line' && $html[$i] == "\n"){
                                    $in_comment = '';
                              }
                        }
                        if ($tag_name == 'script' && $in_tag && empty($in_comment) && empty($in_quote) && $html[$i] == '/'){
                              if ($in_regex){
                                    $in_regex = false;
                              }
                              else {
                                    // Check if it's the start of a regex
                                    $j = $i - 1;
                                    while ($j >= 0 && $html[$j] == ' ') {
                                          $j--;
                                    }
                                    if ($j >= 0 && empty($in_regex) && in_array($html[$j], array('=', '('))){
                                          $in_regex = true;
                                    }
                              }
                        }
                        if ($tag_name == 'script' && $in_tag && (!empty($in_quote) || !empty($in_regex)) && $html[$i] == '\\'){
                              $i++;
                              continue;
                        }
                        if ($in_tag && empty($in_quote) && empty($in_regex) && empty($in_comment)){
                              $maybe_close = '';
                              for ($j = $i; $j < min(strlen($html), $i + strlen($tag_name)+4); $j++){
                                    $maybe_close .= $html[$j];
                              }
                              if (preg_match('~' . '<(\\\\)?/' . $tag_name . '>' . '~', $maybe_close)){
                                    $i = $j-1;
                                    $nodes[] = substr($html, $start, $j - $start);
                                    $in_tag = $in_escape = $start = false;
                              }
                        }
                  }

                  return $nodes;
            }
      }
      public static function is_ignored(){
            return apply_filters('swift3_ignore_optimizer', (is_404() || parse_url(Swift3_Helper::get_current_url(), PHP_URL_HOST) !== parse_url(home_url(), PHP_URL_HOST)));
      }
      public static function early_nodes($buffer){
            if (preg_match('~\.(xsl)$~', parse_url(Swift3_Helper::get_current_url(), PHP_URL_PATH))){
                  return $buffer;
            }
            if (strpos($buffer, '<html') === false){
                  return $buffer;
            }
            if (defined('REST_REQUEST')){
                  return $buffer;
            }
            if (swift3_check_option('js-delivery', 0, '>') && stripos($buffer, 'content-security-policy')){
                  $meta_tags = Swift3_Optimizer::get_nodes('meta', $buffer);
                  foreach ($meta_tags as $meta_tag) {
                        $tag = new Swift3_Html_Tag($meta_tag);
                        if (isset($tag->attributes['http-equiv']) && preg_match('~content\-security\-policy~i', $tag->attributes['http-equiv']) && isset($tag->attributes['content'])){
                              $csp = explode(';',$tag->attributes['content']);
                              foreach ($csp as $key => $policy){
                                    $rules = preg_split('~\s~', trim($policy));
                                    if ($rules[0] == 'connect-src' && strpos($policy, 'data:') === false){
                                          $rules[] = 'data:';
                                    }

                                    if ($rules[0] == 'script-src' && strpos($policy, 'blob:') === false){
                                          $rules[] = 'blob:';
                                    }
                                    $csp[$key] = implode(' ', $rules);
                              }
                              $tag->attributes['content'] = implode(';', $csp);

                              if (Swift3_Helper::is_prebuild()){
                                    $tag->attributes['http-equiv'] = 'swift3-csp';
                              }
                              $buffer = str_replace($meta_tag, (string)$tag, $buffer);
                        }
                  }
            }

            $viewport = '<meta id="swift3-viewport" name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n";
            $observer =  Swift3_Helper::get_script_tag(apply_filters('swift3_observer_script', Swift3_Helper::get_template('script/observer','js')));

            $buffer = preg_replace('~<head\b(?!er\b)([^>]*?)?>~', '$0' . "\n" . $viewport . $observer, $buffer);

            return $buffer;
      }
}
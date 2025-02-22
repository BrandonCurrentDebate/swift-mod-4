<?php

class Swift3_Dashboard {

      public static $status = array(
            'cache' => array(
                  'total' => 0,
                  'cached' => 0,
                  'percentage' => 0
            ),
            'main-thread' => 0,
            'fine-tuning' => 0,
            'images' => ''
      );
      public function __construct(){
            self::get_status();
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('admin_menu', function(){
                  add_submenu_page('tools.php', __('Swift Performance', 'swift3'), __('Swift Performance', 'swift3'), 'manage_options', 'swift3', array($this, 'panel'));
            });

            if (!Swift3_Helper::check_constant('DISABLE_TOOLBAR')){
                  add_action('wp_enqueue_scripts', array($this, 'enqueue_toolbar_assets'));
                  add_action('admin_enqueue_scripts', array($this, 'enqueue_toolbar_assets'));

                  add_action('admin_bar_menu', array($this, 'toolbar_items'), 100);
            }

            add_filter('plugin_action_links_' . plugin_basename(SWIFT3_FILE), array(__CLASS__, 'plugin_links') );

      }
      public function enqueue_admin_assets($hook){
            if ($hook == 'tools_page_swift3'){
                  add_action('admin_notices', function(){
                        remove_all_actions('admin_notices');
                        remove_all_actions('all_admin_notices');
                  }, -PHP_INT_MAX);
                  $css_ver = hash('crc32', md5_file(SWIFT3_DIR . 'assets/css/admin.css'));
                  $js_ver = hash('crc32', md5_file(SWIFT3_DIR . 'assets/js/admin.js'));
                  wp_enqueue_style('swift3', SWIFT3_URL . 'assets/css/admin.css', array(), $css_ver);
                  wp_enqueue_script('swift3', SWIFT3_URL . 'assets/js/admin.js', array('jquery'), $js_ver, true);
                  wp_localize_script('swift3', 'swift3_admin', array('i18n' => self::i18n(), 'nonce' => wp_create_nonce('swift3-admin'), 'site_icon' => get_site_icon_url(512, SWIFT3_URL . 'assets/images/wp.png'), 'pause_status' => false, 'plugin_url' => SWIFT3_URL));

                  wp_enqueue_style('swift3-select2', SWIFT3_URL . 'assets/css/select2.min.css', array(), $css_ver);
                  wp_enqueue_script('swift3-select2', SWIFT3_URL . 'assets/js/select2.min.js', array(), $css_ver);
            }
      }
      public function enqueue_toolbar_assets(){
            if ($this->show_toolbar()){
                  $css_ver = hash('crc32', md5_file(SWIFT3_DIR . 'assets/css/toolbar.css'));
                  $js_ver = hash('crc32', md5_file(SWIFT3_DIR . 'assets/js/toolbar.js'));
                  wp_enqueue_style('swift3-toolbar', SWIFT3_URL . 'assets/css/toolbar.css', array(), $css_ver);
                  wp_enqueue_script('swift3-toolbar', SWIFT3_URL . 'assets/js/toolbar.js', array('jquery'), $js_ver);
                  wp_localize_script('swift3-toolbar', 'swift3_toolbar', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('swift3-admin'), 'is_admin' => is_admin()));
            }
      }
      public function panel(){
            include SWIFT3_DIR . 'templates/dashboard.tpl.php';
      }

      public function toolbar_items($admin_bar){
            if ($this->show_toolbar()){
                  $admin_bar->add_menu(array(
                        'id'    => 'swift3',
                        'title' => '<img id="swift3-toolbar-icon" src="' . SWIFT3_URL . 'assets/images/toolbar-ai-icon.png">' . (Swift3_Config::$is_development_mode ? __('Development Mode', 'swift3') : ''),
                        'href'  => esc_url(add_query_arg('page','swift3', admin_url('tools.php'))),
                        'meta'  => (Swift3_Config::$is_development_mode ? array('class' => 'devmode') : array())
                  ));

                  if (is_admin()){
                        $admin_bar->add_menu(array(
                              'id'    => 'swift3-dropdown',
                              'parent' => 'swift3',
                              'meta'  => array('html' => Swift3_Helper::get_template('toolbar-admin'))
                        ));
                  }
                  else {
                        $admin_bar->add_menu(array(
                              'id'    => 'swift3-dropdown',
                              'parent' => 'swift3',
                              'meta'  => array('html' => Swift3_Helper::get_template('toolbar'))
                        ));
                  }
            }
      }
      public function show_toolbar(){
            global $post;
            $show_admin_bar = swift3_get_option('adminbar');
            switch ($show_admin_bar){
                  case 'hide':
                        return false;
                  case 'frontend':
                        return (!is_admin() && (current_user_can('manage_options') || (!empty($post) && current_user_can('edit_post', $post->ID))));
                  case 'backend':
                        return (is_admin() && (current_user_can('manage_options') || (!empty($post) && current_user_can('edit_post', $post->ID))));
                  case 'everywhere':
                  default:
                        return (current_user_can('manage_options') || (!empty($post) && current_user_can('edit_post', $post->ID)));
            }
      }

      public static function get_status(){
            $status = Swift3_Warmup::get_cache_status();

            if (!isset($status->total) || empty($status->total)){
                  return;
            }

            $priority_ids = Swift3_Helper::$db->get_col("SELECT -1 as id UNION SELECT id FROM " . Swift3_Helper::$db->swift3_warmup . " WHERE ppts > (SELECT AVG(ppts) + (STDDEV(ppts) / 2) FROM " . Swift3_Helper::$db->swift3_warmup . ") LIMIT 100");
            $priority_count = count($priority_ids);
            if ($priority_count < ($status->total * 0.1) && $status->total < 1000){
                  $complementary_count = min(100, $status->total * 0.1) - $priority_count;
                  $complementary_ids = Swift3_Helper::$db->get_col(Swift3_Helper::$db->prepare("SELECT id FROM " . Swift3_Helper::$db->swift3_warmup . " WHERE id NOT IN (" . implode(',', array_map(function($val){
                        return "'" . esc_sql($val) . "'";
                  }, $priority_ids)) . ") ORDER BY ppts DESC, priority ASC LIMIT %d", $complementary_count));
                  $priority_ids = array_merge($priority_ids, $complementary_ids);
            }

            $main_thread_status = Swift3_Helper::$db->get_row("SELECT COUNT(*) 'total', COUNT(IF(status = -1, 1, NULL)) 'invalid', COUNT(IF(status = 0, 1, NULL)) 'uncached', COUNT(IF(status IN (-2, -3, 1, 2, 3), 1, NULL)) 'cached', COUNT(IF(status IN (2, -3), 1, NULL)) 'queued', COUNT(IF(status IN (-2, -3, -1, 0), 1, NULL)) 'revisit', COUNT(IF(status IN (-4, 3), 1, NULL)) 'optimized' FROM " . Swift3_Helper::$db->swift3_warmup . " WHERE id IN(" . implode(',', array_map(function($val){
                  return "'" . esc_sql($val) . "'";
            }, $priority_ids)) . ")");
            $fine_tuning_status = Swift3_Helper::$db->get_row("SELECT COUNT(*) 'total', COUNT(IF(status = -1, 1, NULL)) 'invalid', COUNT(IF(status = 0, 1, NULL)) 'uncached', COUNT(IF(status IN (-2, -3, 1, 2, 3), 1, NULL)) 'cached', COUNT(IF(status IN (2, -3), 1, NULL)) 'queued', COUNT(IF(status IN (-2, -3, -1, 0), 1, NULL)) 'revisit', COUNT(IF(status IN (-4, 3), 1, NULL)) 'optimized' FROM " . Swift3_Helper::$db->swift3_warmup . " WHERE id NOT IN(" . implode(',', array_map(function($val){
                  return "'" . esc_sql($val) . "'";
            }, $priority_ids)) . ")");

            self::$status = array(
                  'cache' => array(
                        'info' => (swift3_check_option('caching', 'on') ? sprintf(esc_html__('%d/%d pages are cached', 'swift3'), $status->cached, $status->total) : sprintf(esc_html__('%d/%d pages are preloaded', 'swift3'), $status->cached, $status->total)),
                        'cached' => $status->cached,
                        'percentage' => ceil(($status->cached/$status->total) * 100)
                  ),
                  'main-thread' => ceil($main_thread_status->optimized/max(1,$main_thread_status->total) * 100),
                  'fine-tuning' => ceil($fine_tuning_status->optimized/max(1,$fine_tuning_status->total) * 100),
                  'images' => (swift3_check_option('optimize-images', 'on') ? Swift3_Analytics::get('image') : esc_html__('OFF', 'swift3'))
            );
      }

      public static function i18n(){
            return array(
                  'Unknown error. Please refresh the page.' => __('Unknown error. Please refresh the page.', 'swift3'),
                  'Unknown error. Please refresh the page and try again.' => __('Unknown error. Please refresh the page and try again.', 'swift3'),
                  'All optimized images has been deleted.' => __('All optimized images has been deleted.', 'swift3'),
                  'Reconnecting API' => __('Reconnecting API', 'swift3'),
                  'clear-cache-done' => __('Cache has been cleared.', 'swift3'),
                  'purge-cache-done' => __('Cache has been purged.', 'swift3'),
                  'reset-cache-done' => __('Cache has been reset.', 'swift3'),
            );
      }
      public static function plugin_links($links){
            $links[] = '<a href="' . esc_url(add_query_arg('page','swift3', admin_url('tools.php'))) . '">' . __('Dashboard', 'swift3') . '</a>';
        	return $links;
      }

      public static function get_messages(){
            $messages = array(
                  "With Swift Performance AI's font optimization, your website's fonts will be optimized to load faster, resulting in improved LCP and FCP. This means that your website will load quickly and keep users engaged.",
                  "Say goodbye to unexpected layout shifts with Swift Performance AI's automatic CLS reduction. This feature ensures that your website's layout remains stable, leading to improved user experience and SEO.",
                  "Improve your website's rendering speed with Swift Performance AI's javascript optimization feature. By offloading javascript tasks from the main thread, your website will render faster, leading to better user engagement and increased conversions.",
                  "Swift Performance AI's CSS optimization feature generates critical CSS, which ensures that above-the-fold content is loaded quickly. This results in faster page loading times and improved user experience.",
                  "With Swift Performance AI's iframe optimization, your website's iframes will be smart-lazyloaded for faster loading times. This feature ensures that your website's embedded content is loaded quickly and keeps users engaged.",
                  "Swift Performance AI optimizes Google Maps by lazy loading them on your website. This ensures that the maps are only loaded when they are needed, leading to faster page loading times and improved user experience.",
                  "Keep your website's cache optimized with Swift Performance AI's automatic cache clearing feature. This ensures that your website's cache remains optimized, leading to faster page loading times and better user experience.",
                  "Swift Performance AI's dynamic fragments feature loads dynamic parts of a cached page, such as the admin toolbar or product prices, leading to improved user experience and faster page loading times.",
                  "Checkout and cart pages can be improved for faster loading times. This leads to a smoother user experience, increased conversions, and ultimately, a better overall website performance.",
                  "Improve your website's loading speed and decrease the impact of third-party resources with Swift Performance AI's lazyloading feature for videos and Google Maps. This feature loads a placeholder for these resources and only loads them when they are needed, leading to faster page loading times and a better user experience.",
                  "With Swift Performance AI's \"WebP first\" technology, your website's images will be optimized in the WebP format whenever possible, resulting in faster loading times and a better overall user experience.",
                  "Save time and effort with Swift Performance AI's automatic image resizing feature. This feature automatically resizes images to serve them in the correct dimensions, ensuring that they load quickly and correctly on all devices.",
                  "Speed up your website's navigation with Swift Performance AI's prefetching feature. This feature automatically prefetches pages that a user is likely to visit next, leading to faster page loading times and a better user experience.",
                  "Swift Performance AI's Proxy caching feature can replace Cloudflare APO, but it also supports APO if you prefer to use it. This feature improves website performance by caching content on Cloudflare's edge servers, leading to faster page loading times and a better user experience.",
                  "With a focus on real user experience and field data, Swift Performance AI ensures that your website is optimized for the best possible performance. This means that your website will load quickly, keep users engaged, and ultimately drive conversions and revenue.",
                  "With Swift Performance AI, you can save time and avoid the hassle of manual configuration. Swift AI automatically optimizes your website's performance by configuring itself, allowing you to focus on creating high-quality content and providing the best possible user experience.",
                  "Swift Performance AI optimizes images on the fly, meaning that all of your images (including background images) will be optimized. However, it won't optimize unused thumbnails, so it won't waste space.",
                  "Swift Performance first generates the cache and applies basic optimization to every page. Meanwhile, optimization is being carried out in parallel. Swift Performance identifies the most important resources and optimizes them first, with the remaining resources being optimized towards the end of the process.",
                  "To improve user experience and SEO, it is important that the most popular pages are optimized first. Less popular pages, (such as Terms ¶&¶ Conditions, Privacy Policy, etc) can be optimized later.",
                  "Swift Performance AI is designed to focus on real user experience and field data, ensuring that your website is optimized for your users.",
                  "Logged in cache feature allows for serving cached pages even for logged-in users, improving user experience and decreasing load times.",
                  "Swift Performance AI offers a smart image sizing feature, which can automatically resize images based on viewport size for optimal performance.",
                  "Optimize Rendering feature automatically preloads important images to improve LCP and FCP scores.",
                  "With Swift Performance AI's WooCommerce integration, you can enable features like Prebuild Variations and Checkout Booster to improve performance on your online store.",
                  "With Cloudflare integration Swift Performance AI can autopurging Cloudflare cache when it is necessary.",
                  "By enhancing the native lazyloading for images, Swift Performance AI loads images in the appropriate size depending on the viewport size, without utilizing JavaScript. This optimization results in faster loading times for your website, without compromising the quality of your images.",
                  "Join the Swift Performance ¶<a href=\"https://www.facebook.com/groups/193893071378140\" target=\"_blank\">Facebook community</a>¶ and connect with other website owners and developers to share tips and best practices for optimizing website performance.",
                  "By subscribing to our Multi or Developer plan, you are not only improving your website's speed but also helping the environment. We plant one tree for each Multi subscription and three trees for each Developer subscription.",
                  "Did you know that optimizing your website's pages can help reduce its carbon footprint? By reducing page load times, you can reduce the amount of energy and resources used to serve your website, resulting in a greener web.",
                  "With Swift Performance's advanced optimization features, even non-cacheable pages such as Cart or Checkout can be optimized for faster loading times, providing a seamless user experience.",
                  "With ¶<a href=\"" . admin_url('/tools.php?page=swift3#uth-caching') . "\" target=\"_blank\">Keep Original Headers feature</a>¶, you can ensure that the custom headers sent by your plugins are preserved even for cached pages. This means that your website will maintain its unique functionality and optimized speed, without sacrificing any important customizations.",
                  "With ¶<a href=\"" . admin_url('/tools.php?page=swift3#uth-fragments') . "\" target=\"_blank\">Shortcode Fragments feature</a>¶, you can select specific shortcodes and load them via AJAX, allowing for faster loading times and smoother page transitions.",
                  "With Swift Performance's advanced optimization features, even non-cacheable pages such as Cart or Checkout can be optimized for faster loading times, providing a seamless user experience.",
                  "With Swift Performance AI, you can optimize the loading speed of your website by lazyloading any Gutenberg block or Elementor widget. By doing so, you can load dynamic content while the rest of the page is being loaded from cache, resulting in faster loading times and a better user experience.",
                  "Swift Performance AI can optimize the lazyloading of background images without any manual configuration required. This feature enables the website to load faster by only loading the images when they become visible on the screen, providing a seamless user experience."
            );

            shuffle($messages);

            // Get the first 3 elements
            return array_slice($messages, 0, 4);
      }
}

?>
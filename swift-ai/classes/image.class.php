<?php

class Swift3_Image {

      public static function optimize_queue(){
            $start = time();
            $image_queue = (array)get_option('swift3_image_queue');
            foreach ($image_queue as $priority_batch_key => $priority_batch){
                  foreach ((array)$priority_batch as $page_batch_key => $page_batch){
                        foreach ((array)$page_batch as $image_key => $image){
                              $webp = self::get_webp_path($image);
                              if (file_exists($webp)){
                                    unset($image_queue[$priority_batch_key][$page_batch_key][$image_key]);
                              }
                              else {
                                    self::webp($image, $webp, $page_batch_key);
                                    Swift3_Analytics::record('image');
                                    unset($image_queue[$priority_batch_key][$page_batch_key][$image_key]);
                              }
                              update_option('swift3_image_queue', $image_queue, false);
                        }
                        unset($image_queue[$priority_batch_key][$page_batch_key]);
                        Swift3_Warmup::update_url($page_batch_key, array('status' => -2));
                        update_option('swift3_image_queue', $image_queue, false);
                  }
                  unset($image_queue[$priority_batch_key]);
                  update_option('swift3_image_queue', $image_queue, false);
            }
      }


      public static function webp($file, $output_file, $page_id){
            if (preg_match('~^(https?:)?//~', $file)){
                  $file = preg_replace('~^//~', 'http://', $file);
                  $rel_path = parse_url($file, PHP_URL_PATH);
                  $root_path = Swift3_Helper::get_root_path();

                  if (file_exists($root_path . $rel_path)){
                        $file = $root_path . $rel_path;
                  }
                  else {
                        $response = wp_remote_get(html_entity_decode($file), array('sslverify' => false, 'timeout' => 15,'user-agent' => Swift3_Helper::ua_string('image-optimizer'), 'headers' => array('Referer' => home_url())));
                        if (!is_wp_error($response)){

                              if ($response['response']['code'] != 200){
                                    if ($response['response']['code'] == 404){
                                          Swift3_Logger::log(array('file' => $file, 'error' => '404', 'page_id' => $page_id), 'image-optimization-failed', $file);
                                          self::copy(SWIFT3_DIR . 'assets/images/missing.webp', $output_file);
                                    }
                                    else {
                                          Swift3_Logger::log(array('file' => $file, 'error' => 'HTTP error: ' . $response['response']['code'], 'page_id' => $page_id), 'image-optimization-failed', $file);
                                          self::copy(SWIFT3_DIR . 'assets/images/encode-error.webp', $output_file);
                                    }
                                    return false;
                              }

                              $type = wp_remote_retrieve_header($response, 'content-type');

                              if (strpos($type, 'image/') !== false){
                                    $ext = preg_replace('~^image/~', '', $type);
                                    $tmp_file = trailingslashit(sys_get_temp_dir()) . hash('crc32', $file) . '.' .$ext;
                                    file_put_contents($tmp_file, $response['body']);

                                    $file = $tmp_file;
                              }
                              else {
                                    Swift3_Logger::log(array('file' => $file, 'error' => 'bad encoding', 'page_id' => $page_id), 'image-optimization-failed', $file);
                                    self::copy(SWIFT3_DIR . 'assets/images/encode-error.webp', $output_file);
                                    return false;
                              }
                        }
                        else {
                              Swift3_Logger::log(array('file' => $file, 'error' => 'Network error: ' .  $response->get_error_message(), 'page_id' => $page_id), 'image-optimization-failed', $file);
                              self::copy(SWIFT3_DIR . 'assets/images/encode-error.webp', $output_file);
                              return false;
                        }
                  }
            }
            else {
                  $root_path = Swift3_Helper::get_root_path();
                  $file = $root_path . parse_url($file, PHP_URL_PATH);
            }

            $file = urldecode($file);
            $basedir = dirname($output_file);
            if (!file_exists($basedir)){
                  mkdir($basedir, 0777, true);
            }
            if (!file_exists($file)) {
                  // Not exists
                  Swift3_Logger::log(array('file' => $file, 'error' => 'file does not exist', 'page_id' => $page_id), 'image-optimization-failed', $file);
                  self::copy(SWIFT3_DIR . 'assets/images/missing.webp', $output_file);
                  return false;
            }

            if (!empty($file) && file_exists($file . '.webp') && is_file($file . '.webp')){
                  self::copy($file . '.webp', $output_file);
                  return true;
            }
            else if (file_exists(preg_replace('~\.(png|jpe?g|gif)$~', 'webp', $file))){
                  self::copy(preg_replace('~\.(png|jpe?g|gif)$~', 'webp', $file), $output_file);
                  return true;
            }

            $file_type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($file_type == 'webp'){
                  self::copy($file, $output_file);
                  return true;
            }
            if ($file_type == 'gif'){
                  $response = Swift3::get_module('api')->request('gif2webp', array(
                        'data' => base64_encode(file_get_contents($file))
                  ));

                  if (!is_wp_error($response)){
                        file_put_contents($output_file, base64_decode($response['body']));
                        return true;
                  }
                  else {
                        Swift3_Logger::log(array('file' => $file, 'error' => 'API error: ' .  $response->get_error_message()), 'image-optimization-failed', $file);
                        self::copy(SWIFT3_DIR . 'assets/images/convert-error.webp', $output_file);
                        return false;
                  }
            }

            if (!Swift3_Helper::check_constant('DISABLE_LOCAL_WEBP') && class_exists('Imagick')){
                  $supported_types = \Imagick::queryformats('*');

                  if (in_array('WEBP', $supported_types)){
                        $optimizer = 'imagick';
                  }
            }

            if (!isset($optimizer)){
                  if (!Swift3_Helper::check_constant('DISABLE_LOCAL_WEBP') && function_exists('imagewebp')){
                        $optimizer = 'gd';
                  }
                  else {
                        $optimizer = 'api';
                  }
            }
            if ($optimizer == 'gd') {
                  self::intermission();

                  switch ($file_type) {
                        case 'jpeg':
                        case 'jpg':
                        $image = imagecreatefromjpeg($file);
                        break;

                        case 'png':
                        $image = imagecreatefrompng($file);
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                        break;

                        default:
                        Swift3_Logger::log(array('file' => $file, 'error' => 'unknown type', 'page_id' => $page_id), 'image-optimization-failed', $file);
                        self::copy(SWIFT3_DIR . 'assets/images/convert-error.webp', $output_file);
                        return false;
                  }

                  if (isset($tmp_file) && file_exists($tmp_file)){
                        unlink($tmp_file);
                  }
                  if (empty($image)){
                        Swift3_Logger::log(array('file' => $file, 'error' => 'conversion #1', 'page_id' => $page_id), 'image-optimization-failed', $file);
                        self::copy(SWIFT3_DIR . 'assets/images/convert-error.webp', $output_file);
                        return false;
                  }
                  $result = imagewebp($image, $output_file, SWIFT3_WEBP_QUALITY);
                  if (false === $result) {
                        Swift3_Logger::log(array('file' => $file, 'error' => 'conversion #2', 'page_id' => $page_id), 'image-optimization-failed', $file);
                        self::copy(SWIFT3_DIR . 'assets/images/convert-error.webp', $output_file);
                        return false;
                  }
                  imagedestroy($image);
                  return true;
            }
            else if ($optimizer == 'imagick') {
                  self::intermission();

                  $image = new Imagick();
                  $image->readImage($file);

                  if (isset($tmp_file) && file_exists($tmp_file)){
                        unlink($tmp_file);
                  }

                  if ($file_type === 'png') {
                        $image->setImageFormat('webp');
                        $image->setImageCompressionQuality(SWIFT3_WEBP_QUALITY);
                        if (SWIFT3_WEBP_QUALITY < 100){
                              $image->setOption('webp:lossless', 'true');
                        }
                  }

                  $image->writeImage($output_file);
                  return true;
            }
            else {
                  $response = Swift3::get_module('api')->request('webp', array(
                        'data' => base64_encode(file_get_contents($file))
                  ));

                  if (!is_wp_error($response)){
                        file_put_contents($output_file, base64_decode($response['body']));
                        return true;
                  }
                  else {
                        Swift3_Logger::log(array('file' => $file, 'error' => 'API error: ' .  $response->get_error_message(), 'page_id' => $page_id), 'image-optimization-failed', $file);
                  }
            }

            self::copy(SWIFT3_DIR . 'assets/images/convert-error.webp', $output_file);
            return false;
      }
      public static function delete_image($file){
            $maybe_webp = self::get_webp_path(str_replace(ABSPATH, '', $file));
            if (file_exists($maybe_webp)){
                  @unlink($maybe_webp);
            }

            return $file;
      }
      public static function delete_images(){
            Swift3_Logger::rlogs('image-optimization-failed');
            Swift3_Helper::delete_files(self::get_image_basedir());
      }
      public static function get_webp_path($path){
            $path = Swift3_Helper::normalize_url($path);
            $url_parts = parse_url(urldecode($path));
            $postfix = (!empty($url_parts['query']) ? '__pf_' . hash('crc32', $url_parts['query']) : '');

            if ($url_parts !== false && isset($url_parts['path'])){
                  if (preg_match('~^(https?:)?//~', $path)){
                        return self::get_image_basedir() . '__e/' . $url_parts['host'] . preg_replace('~\.(png|jpe?g|gif)~', $postfix . '-$1', html_entity_decode($url_parts['path'])) . '.webp';
                  }
                  else {
                        return self::get_image_basedir() . preg_replace('~\.(png|jpe?g|gif)~', $postfix . '-$1', $url_parts['path']) . '.webp';
                  }
            }
            return $path;
      }

      public static function get_image_basedir(){
            return apply_filters('swift3_image_basedir', WP_CONTENT_DIR . '/swift-ai/images/');
      }

      public static function get_image_reluri($file = ''){
            $basedir = self::get_image_basedir();
            return apply_filters('swift3_image_reluri', WP_CONTENT_URL . '/swift-ai/images/') . ltrim(str_replace('%2F','/',rawurlencode(str_replace($basedir, '', $file))), '/');
      }

      public static function queue_image($path, $priority = 6, $page_id = -1){
            $path = Swift3_Helper::normalize_url(trim($path));
            $image_id = hash('crc32', $path);
            $image_queue = (array)get_option('swift3_image_queue');
            $image_queue[$priority][$page_id][$image_id] = $path;
            update_option('swift3_image_queue', $image_queue, false);
      }

      public static function has_queued($key){
            $id = Swift3_Warmup::get_id($key);
            $queue = (array)get_option('swift3_image_queue');
            return (isset($queue[$id]) && !empty($queue[$id]));
      }

      public static function is_image_dir_exists(){
            $dir = self::get_image_basedir();
            if (file_exists($dir)){
                  return true;
            }
            else if (is_writable(WP_CONTENT_DIR)){
                  mkdir ($dir, 0777, true);
                  return true;
            }
            else {
                  Swift3_Logger::log(array('message' => sprintf(__('WP content directory (%s) is not writable for WordPress. Please change the permissions and try again.', 'swift3'), WP_CONTENT_DIR)), 'cache-folder-not-writable', 'wp-content-folder');
                  return false;
            }
      }

      public static function handle_upload($upload){
            $file = (is_array($upload) ? $upload['file'] : $upload);
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), array('jpg', 'jpeg', 'gif', 'png'))){
                  $path = str_replace(ABSPATH, '', $file);
                  self::queue_image($path);
            }

            return $upload;
      }

      public static function copy($source, $dest){
            if (!file_exists($source) || !is_file($source)){
                  return;
            }
            $basedir = dirname($dest);
            if (!file_exists($basedir)){
                  mkdir($basedir, 0777, true);
            }
            @$symlink = (!function_exists('symlink') || Swift3_Helper::check_constant('DISABLE_SYMLINK') ? false : symlink($source, $dest));
            if (!$symlink){
                  @copy($source, $dest);
            }
      }

      public static function intermission(){
            if (SWIFT3_IMAGE_INTERMISSION >= 1000000){
                  sleep(round(SWIFT3_IMAGE_INTERMISSION/1000000));
            }
            else {
                  usleep(SWIFT3_IMAGE_INTERMISSION);
            }
      }
}

?>
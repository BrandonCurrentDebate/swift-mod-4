<?php

/**
 * Limited resources shorthand
 */

if (defined('SWIFT3_LIMITED_RESOURCES')){
      define('SWIFT3_DISABLE_LOCAL_WEBP', true);
      define('SWIFT3_PREBUILD_INTERMISSION', 2500000);
}

/**
 * Plugin slug
 * default: swift3
 */
if (!defined('SWIFT3_SLUG')){
      define('SWIFT3_SLUG', 'swift3');
}

/**
 * API timeout (in seconds)
 * default: 600
 * Wait for the API poll response. If value increased, plugin will be more tolerable with API usage spikes. Too low value can cause worse results.
 */
if (!defined('SWIFT3_PPTO')){
      define ('SWIFT3_PPTO', 600);
}

/**
 * Max number for initial Warmup Table size per post type
 * default: 1000
 * Warmup table can grow later if new pages has been discovered
 */
if (!defined('SWIFT3_WARMUP_POST_TYPE_LIMIT')){
      define('SWIFT3_WARMUP_POST_TYPE_LIMIT', 1000);
}

/**
 * Max number for initial Warmup Table size
 * default: 5000
 * Warmup table can grow later if new pages has been discovered
 */
if (!defined('SWIFT3_WARMUP_LIMIT')){
      define('SWIFT3_WARMUP_LIMIT', 5000);
}

/**
 * Max URL length
 * default: 255
 * URLs which exceed the max URL length won't be cached
 */
if (!defined('SWIFT3_MAX_URL_LENGTH')){
      define('SWIFT3_MAX_URL_LENGTH', 255);
}

/**
 * Cache lifespan (in seconds)
 * default: 43200 (12 hours)
 */
if (!defined('SWIFT3_CACHE_LIFESPAN')){
      define('SWIFT3_CACHE_LIFESPAN', 43200);
}

/**
 * Wait time between requests during prebuild (in microseconds)
 * default: 50000
 */
if (!defined('SWIFT3_PREBUILD_INTERMISSION')){
      define('SWIFT3_PREBUILD_INTERMISSION', 50000);
}


/**
 * Wait time between image optimization during prebuild (in microseconds)
 * default: 100000
 */
if (!defined('SWIFT3_IMAGE_INTERMISSION')){
      define('SWIFT3_IMAGE_INTERMISSION', 100000);
}

/**
 * WebP Quality
 * default: 100 (lossless)
 */
if (!defined('SWIFT3_WEBP_QUALITY')){
      define('SWIFT3_WEBP_QUALITY', 83);
}

/**
 * API URL
 * default: https://api.swiftperformance.io/
 */
if (!defined('SWIFT3_API_URL')){
      define('SWIFT3_API_URL', 'https://api.swiftperformance.io/');
}

/**
 *
 * HTTP Request Cache max size
 * Maximum size of cached HTTP response (in bytes). Default: 1024
 */
if (!defined('SWIFT3_HTTP_REQUEST_CACHE_MAX_SIZE')){
      define('SWIFT3_HTTP_REQUEST_CACHE_MAX_SIZE', 1024);
}


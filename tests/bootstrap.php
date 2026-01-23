<?php
/**
 * PHPUnit Bootstrap
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Initialize WP_Mock
WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();

// Define test environment constant
define('WP_INT_TEST', true);

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

// Mock essential WP functions
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file)
    {
        return 'http://example.com/wp-content/plugins/cardcrafter-data-grids/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file)
    {
        return __DIR__ . '/../';
    }
}

// Mock WP Core functions
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
    {
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback)
    {
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = [])
    {
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = [])
    {
        return false;
    }
}

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null)
    {
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $function)
    {
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default')
    {
        echo $text;
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = 'default')
    {
        echo $text;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return $text;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return $url;
    }
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url($url)
    {
        return parse_url($url);
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing)
    {
        return false;
    }
}

if (!function_exists('shortcode_atts')) {
    function shortcode_atts($pairs, $atts, $shortcode = '')
    {
        $atts = (array) $atts;
        $out = array();
        foreach ($pairs as $name => $default) {
            if (array_key_exists($name, $atts)) {
                $out[$name] = $atts[$name];
            } else {
                $out[$name] = $default;
            }
        }
        return $out;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value)
    {
        return $value;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        return strtolower($key);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str)
    {
        return trim($str);
    }
}

if (!function_exists('absint')) {
    function absint($maybeint)
    {
        return abs(intval($maybeint));
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url)
    {
        return $url;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($transient)
    {
        return false;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false)
    {
        return $default;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false)
    {
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
    {
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '')
    {
        return 'http://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1)
    {
        return 'nonce';
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512)
    {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($v)
    {
        return $v;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1)
    {
        return true;
    }
}

// Load the plugin file
require_once plugin_dir_path(__FILE__) . 'cardcrafter.php';

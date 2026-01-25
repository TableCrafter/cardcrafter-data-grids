<?php
/**
 * AJAX Endpoints Debug Tool
 * 
 * Test CardCrafter AJAX endpoints to diagnose JSON/HTML response issues
 * Run this directly to test endpoints without WordPress interference
 */

// Simple demo data validation test without WordPress
echo "📁 Testing Demo Data Files:\n";
echo "===========================\n\n";

// Mock AJAX request parameters
$_REQUEST['action'] = 'cardcrafter_proxy_fetch';
$_REQUEST['url'] = plugin_dir_url(__FILE__) . 'demo-data/team.json';
$_REQUEST['nonce'] = wp_create_nonce('cardcrafter_proxy_nonce');

// Include the main plugin file
require_once __DIR__ . '/cardcrafter.php';

// Test the AJAX endpoint directly
echo "🧪 Testing CardCrafter AJAX Endpoint...\n";
echo "=====================================\n\n";

try {
    // Create CardCrafter instance
    $cardcrafter = CardCrafter::get_instance();
    
    echo "✅ CardCrafter instance created successfully\n";
    
    // Test the proxy fetch method
    echo "🔄 Testing ajax_proxy_fetch method...\n";
    
    // Capture output
    ob_start();
    $cardcrafter->ajax_proxy_fetch();
    $output = ob_get_clean();
    
    echo "📤 Raw output: " . substr($output, 0, 200) . "...\n";
    
    // Try to decode JSON
    $decoded = json_decode($output, true);
    if ($decoded !== null) {
        echo "✅ Valid JSON response received\n";
        echo "📊 Response structure:\n";
        print_r(array_keys($decoded));
    } else {
        echo "❌ Invalid JSON response - this is the problem!\n";
        echo "🔍 Response starts with: " . substr($output, 0, 50) . "\n";
        
        if (strpos($output, '<!DOCTYPE') === 0 || strpos($output, '<html') !== false) {
            echo "🚨 Response is HTML instead of JSON - WordPress error detected!\n";
        }
    }
    
} catch (Exception $e) {
    echo "💥 Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
} catch (Error $e) {
    echo "💥 Fatal Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🔧 WordPress Debug Info:\n";
echo "========================\n";
echo "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF') . "\n";
echo "WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'ON' : 'OFF') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "WordPress Version: " . (function_exists('get_bloginfo') ? get_bloginfo('version') : 'Unknown') . "\n";

// Test demo data URL directly
echo "\n📁 Testing Demo Data URL Access:\n";
echo "================================\n";

$demo_url = plugin_dir_url(__FILE__) . 'demo-data/team.json';
echo "Demo URL: " . $demo_url . "\n";

$response = wp_remote_get($demo_url);
if (is_wp_error($response)) {
    echo "❌ Demo data fetch failed: " . $response->get_error_message() . "\n";
} else {
    $body = wp_remote_retrieve_body($response);
    $json_data = json_decode($body, true);
    if ($json_data !== null) {
        echo "✅ Demo data accessible and valid JSON\n";
        echo "📊 Items count: " . count($json_data) . "\n";
    } else {
        echo "❌ Demo data is not valid JSON\n";
        echo "🔍 First 100 chars: " . substr($body, 0, 100) . "\n";
    }
}

echo "\n✅ Diagnostic complete!\n";
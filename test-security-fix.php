<?php
/**
 * Quick Security Fix Verification Test
 * Run this to verify the error sanitization is working
 */

// Define WordPress constants if not defined
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

// Mock WordPress functions
if (!function_exists('current_user_can')) {
    function current_user_can($capability) { return false; }
}

if (!function_exists('error_log')) {
    function error_log($message) { echo "LOG: $message\n"; }
}

// Mock WP_Error class if not available
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code;
        private $message;
        
        public function __construct($code, $message) {
            $this->code = $code;
            $this->message = $message;
        }
        
        public function get_error_code() {
            return $this->code;
        }
        
        public function get_error_message() {
            return $this->message;
        }
    }
}

// Include the CardCrafter class definition directly
require_once 'cardcrafter.php';

echo "🔒 Testing CardCrafter Security Fix\n";
echo "====================================\n\n";

try {
    // Get CardCrafter instance
    $cardcrafter = CardCrafter::get_instance();
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($cardcrafter);
    $method = $reflection->getMethod('sanitize_error_message');
    $method->setAccessible(true);
    
    echo "✅ Successfully loaded CardCrafter class and method\n\n";
    
    // Test 1: cURL error sanitization
    echo "Test 1: cURL Error Sanitization\n";
    $curl_error = new WP_Error(
        'http_request_failed',
        'cURL error 28: Connection timed out after 5001 milliseconds for /secret/internal/path'
    );
    
    $sanitized = $method->invoke($cardcrafter, $curl_error);
    echo "Original: " . $curl_error->get_error_message() . "\n";
    echo "Sanitized: $sanitized\n";
    
    if (strpos($sanitized, '/secret/') === false && strpos($sanitized, 'milliseconds') === false) {
        echo "✅ PASS: Sensitive details removed\n\n";
    } else {
        echo "❌ FAIL: Sensitive details still exposed\n\n";
        exit(1);
    }
    
    // Test 2: SSL error sanitization  
    echo "Test 2: SSL Error Sanitization\n";
    $ssl_error = new WP_Error(
        'http_request_failed',
        'SSL certificate problem: unable to get local issuer certificate'
    );
    
    $sanitized = $method->invoke($cardcrafter, $ssl_error);
    echo "Original: " . $ssl_error->get_error_message() . "\n";
    echo "Sanitized: $sanitized\n";
    
    if (strpos($sanitized, 'certificate problem') === false) {
        echo "✅ PASS: SSL details sanitized\n\n";
    } else {
        echo "❌ FAIL: SSL details still exposed\n\n";
        exit(1);
    }
    
    // Test 3: HTTP error code mapping
    echo "Test 3: HTTP Error Code Mapping\n";
    $http_error = new WP_Error('http_404', 'Not found at /secret/internal/url');
    $sanitized = $method->invoke($cardcrafter, $http_error);
    echo "Original: " . $http_error->get_error_message() . "\n";
    echo "Sanitized: $sanitized\n";
    
    if ($sanitized === 'Data source not found. Please verify the URL is correct.' && strpos($sanitized, '/secret/') === false) {
        echo "✅ PASS: HTTP error properly mapped\n\n";
    } else {
        echo "❌ FAIL: HTTP error mapping failed\n\n";
        exit(1);
    }
    
    // Test 4: Database credentials sanitization
    echo "Test 4: Database Credentials Sanitization\n";
    $db_error = new WP_Error(
        'database_error',
        'Connection failed: mysql://username:password123@internal.db.server:3306/production_db'
    );
    
    $sanitized = $method->invoke($cardcrafter, $db_error);
    echo "Original: " . $db_error->get_error_message() . "\n";
    echo "Sanitized: $sanitized\n";
    
    if (strpos($sanitized, 'password123') === false && strpos($sanitized, 'internal.db.server') === false) {
        echo "✅ PASS: Database credentials protected\n\n";
    } else {
        echo "❌ FAIL: Database credentials still exposed\n\n";
        exit(1);
    }
    
    echo "🎉 ALL TESTS PASSED! Security fix is working correctly.\n";
    echo "\n📋 Summary:\n";
    echo "- Sensitive file paths are hidden\n";
    echo "- Technical details are sanitized\n";
    echo "- HTTP errors are properly mapped\n";
    echo "- Database credentials are protected\n";
    echo "- Users get helpful, safe error messages\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
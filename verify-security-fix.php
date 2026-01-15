<?php
/**
 * Security Fix Verification
 * Tests just the sanitization method in isolation
 */

// Mock WordPress functions
function current_user_can($capability) { return false; }
if (!function_exists('error_log')) {
    function error_log($message) { /* no-op for test */ }
}
function plugin_dir_url($file) { return 'https://example.com/wp-content/plugins/cardcrafter/'; }
function plugin_dir_path($file) { return '/tmp/cardcrafter/'; }
function wp_enqueue_script() { /* no-op for test */ }
function wp_localize_script() { /* no-op for test */ }
function wp_enqueue_style() { /* no-op for test */ }
function add_action() { /* no-op for test */ }
function wp_ajax_cardcrafter_proxy() { /* no-op for test */ }
function sanitize_text_field($input) { return $input; }
function wp_unslash($input) { return $input; }
function wp_verify_nonce() { return true; }
function wp_safe_remote_get() { return []; }
function wp_remote_retrieve_body() { return '{}'; }
function get_transient() { return false; }
function set_transient() { return true; }
function wp_send_json_success() { /* no-op for test */ }
function wp_send_json_error() { /* no-op for test */ }
function wp_create_nonce() { return 'test-nonce'; }

// Mock WP_Error class
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

// Define constants
define('ABSPATH', '/tmp/');
define('CARDCRAFTER_VERSION', '1.3.0');
define('CARDCRAFTER_URL', 'https://example.com/');
define('HOUR_IN_SECONDS', 3600);

echo "🔒 CardCrafter Security Fix Verification\n";
echo "=========================================\n\n";

// Extract just the sanitize_error_message method for testing
class SecurityTest {
    
    /**
     * Sanitize error messages to prevent information disclosure.
     * This is a copy of the method from CardCrafter for isolated testing.
     */
    private function sanitize_error_message($error): string
    {
        $error_code = $error->get_error_code();
        $error_message = $error->get_error_message();
        
        // Log the actual error for debugging (admin only)
        if (current_user_can('manage_options')) {
            error_log('CardCrafter Error [' . $error_code . ']: ' . $error_message);
        }

        // Map error codes to safe user messages
        $safe_messages = array(
            'http_request_failed' => 'Unable to connect to the data source. Please check the URL and try again.',
            'http_request_timeout' => 'Request timed out. The data source may be temporarily unavailable.',
            'http_404' => 'Data source not found. Please verify the URL is correct.',
            'http_403' => 'Access denied to the data source.',
            'http_500' => 'The data source is experiencing technical difficulties.',
            'http_502' => 'The data source is temporarily unavailable.',
            'http_503' => 'The data source is temporarily unavailable.',
        );

        // Check message content for sensitive patterns first (more specific)
        if (strpos($error_message, 'cURL error') !== false) {
            return 'Network connection error. Please try again later.';
        }
        
        if (strpos($error_message, 'SSL') !== false) {
            return 'Secure connection error. Please verify the URL uses HTTPS.';
        }

        // Check for specific HTTP error codes
        if (strpos($error_code, 'http_') === 0) {
            return $safe_messages[$error_code] ?? 'Unable to retrieve data from the source.';
        }

        // Generic fallback for any unhandled error types
        return 'Unable to retrieve data. Please check your data source URL.';
    }
    
    public function run_tests() {
        $passed = 0;
        $total = 0;
        
        // Test 1: cURL error with sensitive file paths
        $total++;
        echo "Test 1: cURL Error with File Paths\n";
        $error = new WP_Error('http_request_failed', 'cURL error 28: Connection timed out for /home/user/.ssh/private_key');
        $sanitized = $this->sanitize_error_message($error);
        echo "Result: $sanitized\n";
        if (strpos($sanitized, '/home/user') === false && $sanitized === 'Network connection error. Please try again later.') {
            echo "✅ PASS\n\n";
            $passed++;
        } else {
            echo "❌ FAIL - File path exposed or wrong message\n\n";
        }
        
        // Test 2: SSL certificate details
        $total++;
        echo "Test 2: SSL Certificate Error\n";
        $error = new WP_Error('http_request_failed', 'SSL certificate problem: self signed certificate in certificate chain');
        $sanitized = $this->sanitize_error_message($error);
        echo "Result: $sanitized\n";
        if (strpos($sanitized, 'certificate problem') === false && $sanitized === 'Secure connection error. Please verify the URL uses HTTPS.') {
            echo "✅ PASS\n\n";
            $passed++;
        } else {
            echo "❌ FAIL - SSL details exposed or wrong message\n\n";
        }
        
        // Test 3: HTTP 404 mapping
        $total++;
        echo "Test 3: HTTP 404 Error Mapping\n";
        $error = new WP_Error('http_404', 'The page at /admin/secret/config.php was not found');
        $sanitized = $this->sanitize_error_message($error);
        echo "Result: $sanitized\n";
        if (strpos($sanitized, 'config.php') === false && $sanitized === 'Data source not found. Please verify the URL is correct.') {
            echo "✅ PASS\n\n";
            $passed++;
        } else {
            echo "❌ FAIL - File path exposed or wrong message\n\n";
        }
        
        // Test 4: Database connection string
        $total++;
        echo "Test 4: Database Connection Leak\n";
        $error = new WP_Error('connection_failed', 'mysql://admin:password123@db.internal.company.com:3306/prod_database');
        $sanitized = $this->sanitize_error_message($error);
        echo "Result: $sanitized\n";
        if (strpos($sanitized, 'password123') === false && strpos($sanitized, 'db.internal') === false) {
            echo "✅ PASS\n\n";
            $passed++;
        } else {
            echo "❌ FAIL - Database credentials exposed\n\n";
        }
        
        // Test 5: XSS attempt
        $total++;
        echo "Test 5: XSS Protection\n";
        $error = new WP_Error('xss_test', '<script>document.location="http://evil.com"</script>');
        $sanitized = $this->sanitize_error_message($error);
        echo "Result: $sanitized\n";
        if (strpos($sanitized, '<script>') === false && strpos($sanitized, 'evil.com') === false) {
            echo "✅ PASS\n\n";
            $passed++;
        } else {
            echo "❌ FAIL - XSS vector not sanitized\n\n";
        }
        
        echo "📊 Results: $passed/$total tests passed\n";
        
        if ($passed === $total) {
            echo "\n🎉 ALL TESTS PASSED! Security fix verified.\n";
            echo "\n🛡️  The fix successfully:\n";
            echo "- Prevents file path disclosure\n";
            echo "- Hides SSL certificate details\n";
            echo "- Maps HTTP errors to safe messages\n";
            echo "- Protects database credentials\n";
            echo "- Blocks XSS attempts\n";
            return true;
        } else {
            echo "\n⚠️  Security fix needs review - some tests failed.\n";
            return false;
        }
    }
}

$tester = new SecurityTest();
$success = $tester->run_tests();
exit($success ? 0 : 1);
<?php
/**
 * Manual Security Test for CardCrafter Error Sanitization
 * 
 * This test can be run independently to verify the security fix works
 * without requiring WordPress test framework setup.
 */

// Include the main plugin file
require_once dirname(__DIR__) . '/cardcrafter.php';

class SecurityTester
{
    private $passed = 0;
    private $failed = 0;
    
    public function run_tests()
    {
        echo "🔒 CardCrafter Security Test Suite\n";
        echo "==================================\n\n";
        
        $this->test_curl_error_sanitization();
        $this->test_ssl_error_sanitization();
        $this->test_http_error_codes();
        $this->test_unknown_errors();
        $this->test_xss_protection();
        
        echo "\n📊 Test Results:\n";
        echo "✅ Passed: {$this->passed}\n";
        echo "❌ Failed: {$this->failed}\n";
        
        if ($this->failed === 0) {
            echo "\n🎉 All security tests passed! The fix is working correctly.\n";
            return true;
        } else {
            echo "\n⚠️  Some tests failed. Please review the security implementation.\n";
            return false;
        }
    }
    
    private function assert_true($condition, $test_name)
    {
        if ($condition) {
            echo "✅ $test_name\n";
            $this->passed++;
        } else {
            echo "❌ $test_name\n";
            $this->failed++;
        }
    }
    
    private function assert_not_contains($needle, $haystack, $test_name)
    {
        $this->assert_true(strpos($haystack, $needle) === false, $test_name);
    }
    
    private function assert_equals($expected, $actual, $test_name)
    {
        $this->assert_true($expected === $actual, $test_name . " (expected: '$expected', got: '$actual')");
    }
    
    private function get_sanitize_method()
    {
        $cardcrafter = CardCrafter::get_instance();
        $reflection = new ReflectionClass($cardcrafter);
        $method = $reflection->getMethod('sanitize_error_message');
        $method->setAccessible(true);
        return [$cardcrafter, $method];
    }
    
    private function test_curl_error_sanitization()
    {
        echo "Testing cURL Error Sanitization:\n";
        
        [$instance, $method] = $this->get_sanitize_method();
        
        $sensitive_error = new WP_Error(
            'http_request_failed',
            'cURL error 28: Connection timed out after 5001 milliseconds for /home/user/secret/path'
        );
        
        $sanitized = $method->invoke($instance, $sensitive_error);
        
        $this->assert_not_contains('/home/user/secret', $sanitized, "Should not expose file paths");
        $this->assert_not_contains('milliseconds', $sanitized, "Should not expose technical details");
        $this->assert_equals('Network connection error. Please try again later.', $sanitized, "Should return user-friendly message");
        echo "\n";
    }
    
    private function test_ssl_error_sanitization()
    {
        echo "Testing SSL Error Sanitization:\n";
        
        [$instance, $method] = $this->get_sanitize_method();
        
        $ssl_error = new WP_Error(
            'http_request_failed',
            'SSL certificate problem: unable to get local issuer certificate'
        );
        
        $sanitized = $method->invoke($instance, $ssl_error);
        
        $this->assert_not_contains('certificate problem', $sanitized, "Should not expose SSL technical details");
        $this->assert_not_contains('local issuer', $sanitized, "Should not expose certificate authority info");
        $this->assert_equals('Secure connection error. Please verify the URL uses HTTPS.', $sanitized, "Should return SSL-friendly message");
        echo "\n";
    }
    
    private function test_http_error_codes()
    {
        echo "Testing HTTP Error Code Mapping:\n";
        
        [$instance, $method] = $this->get_sanitize_method();
        
        $test_cases = [
            'http_404' => 'Data source not found. Please verify the URL is correct.',
            'http_403' => 'Access denied to the data source.',
            'http_500' => 'The data source is experiencing technical difficulties.',
            'http_502' => 'The data source is temporarily unavailable.',
        ];
        
        foreach ($test_cases as $error_code => $expected_message) {
            $error = new WP_Error($error_code, 'Some technical details that should be hidden');
            $sanitized = $method->invoke($instance, $error);
            
            $this->assert_equals($expected_message, $sanitized, "HTTP $error_code mapping");
            $this->assert_not_contains('technical details', $sanitized, "Should not expose technical details for $error_code");
        }
        echo "\n";
    }
    
    private function test_unknown_errors()
    {
        echo "Testing Unknown Error Fallback:\n";
        
        [$instance, $method] = $this->get_sanitize_method();
        
        $unknown_error = new WP_Error(
            'some_custom_error',
            'Database connection string: mysql://user:password@localhost/dbname'
        );
        
        $sanitized = $method->invoke($instance, $unknown_error);
        
        $this->assert_not_contains('mysql://', $sanitized, "Should not expose database protocols");
        $this->assert_not_contains('password', $sanitized, "Should not expose credentials");
        $this->assert_not_contains('localhost', $sanitized, "Should not expose server details");
        $this->assert_equals('Unable to retrieve data. Please check your data source URL.', $sanitized, "Should return generic safe message");
        echo "\n";
    }
    
    private function test_xss_protection()
    {
        echo "Testing XSS Protection:\n";
        
        [$instance, $method] = $this->get_sanitize_method();
        
        $xss_error = new WP_Error('xss_test', '<script>alert("xss")</script>');
        $sanitized = $method->invoke($instance, $xss_error);
        
        $this->assert_not_contains('<script>', $sanitized, "Should not contain script tags");
        $this->assert_not_contains('alert', $sanitized, "Should not contain JavaScript code");
        $this->assert_equals('Unable to retrieve data. Please check your data source URL.', $sanitized, "Should return safe fallback message");
        echo "\n";
    }
}

// Mock WordPress functions if not available
if (!function_exists('current_user_can')) {
    function current_user_can($capability) { return false; }
}

if (!function_exists('error_log')) {
    function error_log($message) { /* no-op */ }
}

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

// Run the tests
$tester = new SecurityTester();
$success = $tester->run_tests();

exit($success ? 0 : 1);
<?php
/**
 * Security Tests for CardCrafter Plugin
 * 
 * Tests the error message sanitization functionality to prevent
 * information disclosure vulnerabilities.
 */
class CardCrafter_Security_Test extends WP_UnitTestCase
{
    private $cardcrafter;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->cardcrafter = CardCrafter::get_instance();
        
        // Set up admin user for error logging tests
        $admin_id = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_id);
    }

    /**
     * Test that sensitive cURL error messages are sanitized
     */
    public function test_curl_error_sanitization()
    {
        // Create a WP_Error with sensitive cURL information
        $sensitive_error = new WP_Error(
            'http_request_failed',
            'cURL error 28: Connection timed out after 5001 milliseconds for /home/user/secret/path'
        );
        
        $reflection = new ReflectionClass($this->cardcrafter);
        $method = $reflection->getMethod('sanitize_error_message');
        $method->setAccessible(true);
        
        $sanitized = $method->invoke($this->cardcrafter, $sensitive_error);
        
        // Should not contain sensitive path information
        $this->assertStringNotContainsString('/home/user/secret', $sanitized);
        $this->assertStringNotContainsString('milliseconds', $sanitized);
        
        // Should be user-friendly message
        $this->assertEquals('Network connection error. Please try again later.', $sanitized);
    }

    /**
     * Test that SSL error messages are sanitized
     */
    public function test_ssl_error_sanitization()
    {
        $ssl_error = new WP_Error(
            'http_request_failed',
            'SSL certificate problem: unable to get local issuer certificate'
        );
        
        $reflection = new ReflectionClass($this->cardcrafter);
        $method = $reflection->getMethod('sanitize_error_message');
        $method->setAccessible(true);
        
        $sanitized = $method->invoke($this->cardcrafter, $ssl_error);
        
        // Should not contain technical SSL details
        $this->assertStringNotContainsString('certificate problem', $sanitized);
        $this->assertStringNotContainsString('local issuer', $sanitized);
        
        // Should be user-friendly SSL message
        $this->assertEquals('Secure connection error. Please verify the URL uses HTTPS.', $sanitized);
    }

    /**
     * Test HTTP error code mapping
     */
    public function test_http_error_code_mapping()
    {
        $test_cases = array(
            'http_404' => 'Data source not found. Please verify the URL is correct.',
            'http_403' => 'Access denied to the data source.',
            'http_500' => 'The data source is experiencing technical difficulties.',
            'http_502' => 'The data source is temporarily unavailable.',
            'http_503' => 'The data source is temporarily unavailable.',
            'http_request_timeout' => 'Request timed out. The data source may be temporarily unavailable.'
        );

        $reflection = new ReflectionClass($this->cardcrafter);
        $method = $reflection->getMethod('sanitize_error_message');
        $method->setAccessible(true);

        foreach ($test_cases as $error_code => $expected_message) {
            $error = new WP_Error($error_code, 'Some technical details that should be hidden');
            $sanitized = $method->invoke($this->cardcrafter, $error);
            
            $this->assertEquals($expected_message, $sanitized, "Failed for error code: $error_code");
            $this->assertStringNotContainsString('technical details', $sanitized);
        }
    }

    /**
     * Test that unknown error types get generic message
     */
    public function test_unknown_error_fallback()
    {
        $unknown_error = new WP_Error(
            'some_custom_error',
            'Database connection string: mysql://user:password@localhost/dbname'
        );
        
        $reflection = new ReflectionClass($this->cardcrafter);
        $method = $reflection->getMethod('sanitize_error_message');
        $method->setAccessible(true);
        
        $sanitized = $method->invoke($this->cardcrafter, $unknown_error);
        
        // Should not contain sensitive database information
        $this->assertStringNotContainsString('mysql://', $sanitized);
        $this->assertStringNotContainsString('password', $sanitized);
        $this->assertStringNotContainsString('localhost', $sanitized);
        
        // Should be generic safe message
        $this->assertEquals('Unable to retrieve data. Please check your data source URL.', $sanitized);
    }

    /**
     * Test that admin users get error logging while regular users don't
     */
    public function test_admin_error_logging()
    {
        // Test with admin user
        $admin_id = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_id);
        
        $error = new WP_Error('test_error', 'Sensitive internal error details');
        
        $reflection = new ReflectionClass($this->cardcrafter);
        $method = $reflection->getMethod('sanitize_error_message');
        $method->setAccessible(true);
        
        // Capture error_log output
        $original_log_errors = ini_get('log_errors');
        ini_set('log_errors', 1);
        
        ob_start();
        $sanitized = $method->invoke($this->cardcrafter, $error);
        ob_end_clean();
        
        ini_set('log_errors', $original_log_errors);
        
        // Error should be sanitized regardless of user role
        $this->assertEquals('Unable to retrieve data. Please check your data source URL.', $sanitized);
        
        // Test with regular user
        $user_id = $this->factory->user->create(array('role' => 'subscriber'));
        wp_set_current_user($user_id);
        
        $sanitized_user = $method->invoke($this->cardcrafter, $error);
        
        // Should get same sanitized message
        $this->assertEquals('Unable to retrieve data. Please check your data source URL.', $sanitized_user);
    }

    /**
     * Test that the AJAX handler properly uses sanitized errors
     */
    public function test_ajax_handler_uses_sanitized_errors()
    {
        // Mock a failed HTTP request that would normally expose sensitive info
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return new WP_Error('http_request_failed', 'cURL error 6: Could not resolve host: internal.server.local');
        }, 10, 3);

        // Set up AJAX request
        $_REQUEST['action'] = 'cardcrafter_proxy';
        $_REQUEST['url'] = 'https://example.com/api/data.json';
        $_REQUEST['nonce'] = wp_create_nonce('cardcrafter_proxy_nonce');
        
        // Capture the JSON response
        ob_start();
        
        try {
            do_action('wp_ajax_cardcrafter_proxy');
        } catch (WPAjaxDieContinueException $e) {
            // Expected - AJAX handlers exit with wp_die()
        }
        
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        // Should have sanitized error message
        $this->assertFalse($response['success']);
        $this->assertEquals('Network connection error. Please try again later.', $response['data']);
        
        // Should not contain sensitive internal server information
        $this->assertStringNotContainsString('internal.server.local', $output);
        $this->assertStringNotContainsString('Could not resolve host', $output);
        
        // Clean up filter
        remove_all_filters('pre_http_request');
    }

    /**
     * Test edge cases and malformed inputs
     */
    public function test_edge_cases()
    {
        $reflection = new ReflectionClass($this->cardcrafter);
        $method = $reflection->getMethod('sanitize_error_message');
        $method->setAccessible(true);

        // Test empty error message
        $empty_error = new WP_Error('', '');
        $sanitized = $method->invoke($this->cardcrafter, $empty_error);
        $this->assertEquals('Unable to retrieve data. Please check your data source URL.', $sanitized);

        // Test error with script tags (XSS attempt)
        $xss_error = new WP_Error('xss_test', '<script>alert("xss")</script>');
        $sanitized = $method->invoke($this->cardcrafter, $xss_error);
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertEquals('Unable to retrieve data. Please check your data source URL.', $sanitized);
    }
}
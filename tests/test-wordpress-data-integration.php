<?php
/**
 * Test WordPress Native Data Integration
 * 
 * @package CardCrafter
 * @subpackage Tests
 */

class TestWordPressDataIntegration extends WP_UnitTestCase
{
    private $cardcrafter;
    private $test_posts = array();

    public function setUp(): void
    {
        parent::setUp();
        $this->cardcrafter = CardCrafter::get_instance();
        
        // Create test posts
        $this->test_posts[] = $this->factory->post->create(array(
            'post_title' => 'Test Product 1',
            'post_content' => 'This is a great product for testing our card display.',
            'post_excerpt' => 'A great testing product',
            'post_status' => 'publish',
            'post_type' => 'product'
        ));
        
        $this->test_posts[] = $this->factory->post->create(array(
            'post_title' => 'Test Product 2', 
            'post_content' => 'Another excellent product for comprehensive testing.',
            'post_excerpt' => 'Another testing product',
            'post_status' => 'publish',
            'post_type' => 'product'
        ));
    }

    public function tearDown(): void
    {
        // Clean up test posts
        foreach ($this->test_posts as $post_id) {
            wp_delete_post($post_id, true);
        }
        parent::tearDown();
    }

    /**
     * Test WordPress data mode activation
     */
    public function test_wordpress_data_mode_activation()
    {
        $output = $this->cardcrafter->render_cards(array(
            'post_type' => 'product',
            'posts_per_page' => 5
        ));
        
        // Should contain WordPress data banner
        $this->assertStringContainsString('cardcrafter-wp-banner', $output);
        $this->assertStringContainsString('📝 WordPress Data', $output);
        $this->assertStringContainsString('product(s) from your site', $output);
    }

    /**
     * Test WordPress query integration
     */
    public function test_wordpress_query_integration()
    {
        $output = $this->cardcrafter->render_cards(array(
            'post_type' => 'product',
            'posts_per_page' => 10
        ));
        
        // Should use WordPress data config
        $this->assertStringContainsString('"wpDataMode":true', $output);
        $this->assertStringContainsString('Loading WordPress content', $output);
    }

    /**
     * Test custom wp_query parameter parsing
     */
    public function test_custom_wp_query_parameters()
    {
        $output = $this->cardcrafter->render_cards(array(
            'post_type' => 'product',
            'wp_query' => 'orderby=title&order=ASC',
            'posts_per_page' => 5
        ));
        
        // Should contain WordPress data banner with count
        $this->assertStringContainsString('cardcrafter-wp-banner', $output);
        $this->assertStringNotContainsString('No WordPress posts found', $output);
    }

    /**
     * Test fallback when no WordPress posts found
     */
    public function test_no_posts_found_fallback()
    {
        $output = $this->cardcrafter->render_cards(array(
            'post_type' => 'nonexistent_type',
            'posts_per_page' => 5
        ));
        
        // Should show no results message
        $this->assertStringContainsString('No WordPress posts found matching your criteria', $output);
    }

    /**
     * Test WordPress data structure generation
     */
    public function test_wordpress_data_structure()
    {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->cardcrafter);
        $method = $reflection->getMethod('render_wordpress_data');
        $method->setAccessible(true);
        
        $output = $method->invoke($this->cardcrafter, array(
            'post_type' => 'product',
            'posts_per_page' => 5,
            'layout' => 'grid',
            'columns' => 3,
            'items_per_page' => 12,
            'image_field' => 'image',
            'title_field' => 'title',
            'subtitle_field' => 'subtitle', 
            'description_field' => 'description',
            'link_field' => 'link'
        ));

        // Should contain proper data config structure
        $this->assertStringContainsString('"layout":"grid"', $output);
        $this->assertStringContainsString('"columns":3', $output);
        $this->assertStringContainsString('"wpDataMode":true', $output);
    }

    /**
     * Test Gutenberg block integration with WordPress data
     */
    public function test_gutenberg_block_wordpress_integration()
    {
        $output = $this->cardcrafter->render_block_callback(array(
            'post_type' => 'product',
            'posts_per_page' => 5,
            'layout' => 'masonry',
            'cards_per_row' => 4
        ));
        
        // Should activate WordPress mode via render_cards()
        $this->assertStringContainsString('cardcrafter-wp-banner', $output);
        $this->assertStringContainsString('"layout":"masonry"', $output);
    }

    /**
     * Test placeholder image generation
     */
    public function test_placeholder_image_generation()
    {
        $reflection = new ReflectionClass($this->cardcrafter);
        $method = $reflection->getMethod('get_placeholder_image');
        $method->setAccessible(true);
        
        $placeholder = $method->invoke($this->cardcrafter, 'Test Product Title');
        
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $placeholder);
        $this->assertStringContainsString('Test Product', base64_decode(substr($placeholder, 26)));
    }

    /**
     * Test business impact metrics
     */
    public function test_business_impact_wordpress_integration()
    {
        // Before: Only external JSON sources supported
        $external_only_output = $this->cardcrafter->render_cards(array(
            'source' => 'https://example.com/data.json'
        ));
        
        // After: WordPress native data supported
        $wordpress_output = $this->cardcrafter->render_cards(array(
            'post_type' => 'product'
        ));
        
        // Should be completely different experiences
        $this->assertNotEquals($external_only_output, $wordpress_output);
        $this->assertStringContainsString('WordPress Data', $wordpress_output);
        $this->assertStringNotContainsString('WordPress Data', $external_only_output);
    }

    /**
     * Test shortcode attribute sanitization for WordPress data
     */
    public function test_wordpress_data_sanitization()
    {
        $output = $this->cardcrafter->render_cards(array(
            'post_type' => '<script>alert("xss")</script>product',
            'wp_query' => 'orderby=title&<script>alert("test")</script>',
            'posts_per_page' => 999 // Should be clamped to 100
        ));
        
        // Should sanitize malicious input
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('alert', $output);
    }
}
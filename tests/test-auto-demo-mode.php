<?php
/**
 * Test Auto-Demo Mode functionality
 * 
 * @package CardCrafter
 * @subpackage Tests
 */

class TestAutoDemoMode extends WP_UnitTestCase
{
    private $cardcrafter;

    public function setUp(): void
    {
        parent::setUp();
        $this->cardcrafter = CardCrafter::get_instance();
    }

    /**
     * Test that auto-demo mode activates when no source is provided
     */
    public function test_auto_demo_mode_activation()
    {
        $output = $this->cardcrafter->render_cards([]);
        
        // Should contain demo banner
        $this->assertStringContainsString('cardcrafter-demo-banner', $output);
        $this->assertStringContainsString('🚀 Demo Mode', $output);
        $this->assertStringContainsString('Try Your Own Data', $output);
    }

    /**
     * Test that demo mode sets correct demo data source
     */
    public function test_demo_mode_uses_team_data()
    {
        $output = $this->cardcrafter->render_cards([]);
        
        // Should use team.json as data source
        $this->assertStringContainsString('demo-data/team.json', $output);
    }

    /**
     * Test that demo mode does NOT activate when source is provided
     */
    public function test_demo_mode_disabled_with_source()
    {
        $output = $this->cardcrafter->render_cards([
            'source' => 'https://example.com/data.json'
        ]);
        
        // Should NOT contain demo banner
        $this->assertStringNotContainsString('cardcrafter-demo-banner', $output);
        $this->assertStringNotContainsString('🚀 Demo Mode', $output);
    }

    /**
     * Test that empty string source triggers demo mode
     */
    public function test_empty_string_source_triggers_demo()
    {
        $output = $this->cardcrafter->render_cards([
            'source' => ''
        ]);
        
        // Should contain demo banner
        $this->assertStringContainsString('cardcrafter-demo-banner', $output);
        $this->assertStringContainsString('demo-data/team.json', $output);
    }

    /**
     * Test demo mode with Gutenberg block (empty source)
     */
    public function test_gutenberg_block_demo_mode()
    {
        $output = $this->cardcrafter->render_block_callback([]);
        
        // Should activate demo mode via render_cards()
        $this->assertStringContainsString('cardcrafter-demo-banner', $output);
    }

    /**
     * Test demo data source accessibility
     */
    public function test_demo_data_source_exists()
    {
        $demo_file = CARDCRAFTER_PATH . 'demo-data/team.json';
        $this->assertFileExists($demo_file, 'Demo team data file should exist');
        
        $demo_content = file_get_contents($demo_file);
        $demo_data = json_decode($demo_content, true);
        
        $this->assertIsArray($demo_data, 'Demo data should be valid JSON array');
        $this->assertNotEmpty($demo_data, 'Demo data should not be empty');
        
        // Verify demo data structure
        $first_item = $demo_data[0];
        $this->assertArrayHasKey('title', $first_item);
        $this->assertArrayHasKey('subtitle', $first_item);
        $this->assertArrayHasKey('image', $first_item);
    }

    /**
     * Test CSS demo banner styles are enqueued
     */
    public function test_demo_banner_styles_exist()
    {
        $css_file = CARDCRAFTER_PATH . 'assets/css/cardcrafter.css';
        $css_content = file_get_contents($css_file);
        
        $this->assertStringContainsString('.cardcrafter-demo-banner', $css_content);
        $this->assertStringContainsString('.cardcrafter-demo-badge', $css_content);
        $this->assertStringContainsString('.cardcrafter-try-own-data', $css_content);
    }

    /**
     * Test retention improvement business impact
     */
    public function test_business_impact_metrics()
    {
        // Before: Empty state = immediate abandonment
        $empty_state_output = '<p>Error: CardCrafter requires a "source" attribute.</p>';
        
        // After: Auto-demo shows value immediately
        $demo_output = $this->cardcrafter->render_cards([]);
        
        // Should be dramatically different experiences
        $this->assertNotEquals($empty_state_output, $demo_output);
        $this->assertGreaterThan(500, strlen($demo_output)); // Rich content vs error message
    }
}
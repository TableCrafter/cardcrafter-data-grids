<?php
/**
 * Tests for WCAG 2.1 AA Accessibility Features
 *
 * @package CardCrafter
 * @version 1.13.0
 */

use WP_Mock\Tools\TestCase;

class AccessibilityTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(): void
    {
        \WP_Mock::setUp();
    }

    public function tearDown(): void
    {
        \WP_Mock::tearDown();
    }

    /**
     * Test that ACF function check prevents fatal errors
     */
    public function test_acf_function_exists_check()
    {
        // Mock WordPress posts
        $mock_post = new stdClass();
        $mock_post->ID = 123;
        $mock_post->post_title = 'Test Post';
        $mock_post->post_status = 'publish';
        $mock_post->post_type = 'post';
        $mock_post->post_author = 1;

        \WP_Mock::userFunction('get_posts', array(
            'return' => array($mock_post)
        ));

        \WP_Mock::userFunction('get_the_post_thumbnail_url', array(
            'return' => 'http://example.com/image.jpg'
        ));

        \WP_Mock::userFunction('get_the_title', array(
            'return' => 'Test Post'
        ));

        \WP_Mock::userFunction('get_the_date', array(
            'return' => 'January 1, 2026'
        ));

        \WP_Mock::userFunction('get_the_excerpt', array(
            'return' => 'Test Content'
        ));

        \WP_Mock::userFunction('get_permalink', array(
            'return' => 'http://example.com/post/123'
        ));

        \WP_Mock::userFunction('get_the_author_meta', array(
            'return' => 'John Doe'
        ));

        \WP_Mock::userFunction('wp_trim_words', array(
            'return' => 'Test Content...'
        ));

        // Ensure get_fields function check is passed by NOT defining it
        // and relying on code to check function_exists.
        // Since we are mocking everything else, this implicitly tests logic flow.

        $cardcrafter = CardCrafter::get_instance();

        // This should not throw a fatal error even without ACF
        $output = $cardcrafter->render_cards(array(
            'source' => 'wp_posts',
            'post_type' => 'post',
            'posts_per_page' => 1
        ));

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('cardcrafter-container', $output);
    }

    /**
     * Test that container has proper ARIA role
     */
    public function test_container_has_region_role()
    {
        $cardcrafter = CardCrafter::get_instance();
        // Mock get_transient to avoid remote request setup if SWR pattern used
        // But source default is demo data (URL).
        // render_cards checks if source empty -> demo data.

        $output = $cardcrafter->render_cards(array('source' => 'https://example.com/data.json'));

        $this->assertStringContainsString('role="region"', $output);
    }

    /**
     * Test that container has ARIA label
     */
    public function test_container_has_aria_label()
    {
        $cardcrafter = CardCrafter::get_instance();
        $output = $cardcrafter->render_cards(array('source' => 'https://example.com/data.json'));

        $this->assertStringContainsString('aria-label="', $output);
        $this->assertStringContainsString('Card Grid', $output);
    }

    /**
     * Test that loading state has proper ARIA attributes
     */
    public function test_loading_state_has_aria_live()
    {
        $cardcrafter = CardCrafter::get_instance();
        $output = $cardcrafter->render_cards(array('source' => 'https://example.com/data.json'));

        $this->assertStringContainsString('role="status"', $output);
        $this->assertStringContainsString('aria-live="polite"', $output);
    }

    /**
     * Test that spinner has aria-hidden
     */
    public function test_spinner_has_aria_hidden()
    {
        $cardcrafter = CardCrafter::get_instance();
        $output = $cardcrafter->render_cards(array('source' => 'https://example.com/data.json'));

        $this->assertStringContainsString('aria-hidden="true"', $output);
    }

    /**
     * Test that accessibility is enabled by default
     */
    public function test_accessibility_enabled_by_default()
    {
        $cardcrafter = CardCrafter::get_instance();
        $output = $cardcrafter->render_cards(array('source' => 'https://example.com/data.json'));

        $this->assertStringContainsString('config.enableAccessibility = true', $output);
    }

    /**
     * Test WordPress data mode has proper accessibility
     */
    public function test_wp_data_mode_accessibility()
    {
        // Mock WordPress posts again
        $mock_post = new stdClass();
        $mock_post->ID = 123;
        $mock_post->post_title = 'Test Post';
        $mock_post->post_status = 'publish';
        $mock_post->post_type = 'post';
        $mock_post->post_author = 1;

        \WP_Mock::userFunction('get_posts', array(
            'return' => array($mock_post)
        ));

        \WP_Mock::userFunction('get_the_post_thumbnail_url', array(
            'return' => false
        )); // No image

        \WP_Mock::userFunction('get_the_title', array('return' => 'Test Post'));
        \WP_Mock::userFunction('get_the_date', array('return' => 'Jan 1'));
        \WP_Mock::userFunction('get_the_excerpt', array('return' => 'Excerpt'));
        \WP_Mock::userFunction('get_permalink', array('return' => 'http://link.com'));
        \WP_Mock::userFunction('get_the_author_meta', array('return' => 'Author'));
        \WP_Mock::userFunction('wp_trim_words', array('return' => 'Excerpt'));

        // Mock get_post_thumbnail_id not used in loop but maybe debug?
        // It's used in cardcrafter.php:1210 if debug_image_url
        // Actually debug_thumbnail_id is in ajax_wp_posts_preview, not render_wordpress_data?
        // Check code: render_wordpress_data doesn't use debug_thumbnail_id.

        $cardcrafter = CardCrafter::get_instance();
        $output = $cardcrafter->render_cards(array(
            'source' => 'wp_posts',
            'post_type' => 'post'
        ));

        // Check for accessibility attributes
        $this->assertStringContainsString('role="region"', $output);
        $this->assertStringContainsString('WordPress Posts Card Grid', $output);
    }

    /**
     * Test that demo banner has status role
     */
    public function test_demo_banner_has_status_role()
    {
        $cardcrafter = CardCrafter::get_instance();

        // Render without source to trigger demo mode
        // Must explicitly clear post_type to avoid WP mode default
        $output = $cardcrafter->render_cards(array('source' => '', 'post_type' => ''));

        // Demo mode should show demo banner with status role
        if (strpos($output, 'cardcrafter-demo-banner') !== false) {
            $this->assertStringContainsString('role="status"', $output);
        } else {
            // Assert that demo mode IS active because source is empty
            $this->assertStringContainsString('Demo Mode', $output);
        }
    }
}

/**
 * JavaScript Accessibility Tests (for browser/Jest environment)
 *
 * These tests would be run in a browser environment with Jest or similar.
 * Including them here as documentation.
 */
class Test_CardCrafter_Accessibility_JS_Documentation
{

    /**
     * Document expected keyboard navigation behavior
     */
    public static function keyboard_navigation_tests()
    {
        return array(
            'Arrow Right should focus next card',
            'Arrow Left should focus previous card',
            'Arrow Down should focus card in next row',
            'Arrow Up should focus card in previous row',
            'Home should focus first card',
            'End should focus last card',
            'Enter/Space should activate card link',
            'Tab should move between toolbar and grid',
            'Escape should close export dropdown',
        );
    }

    /**
     * Document expected ARIA attributes on elements
     */
    public static function expected_aria_attributes()
    {
        return array(
            'container' => array(
                'role' => 'region',
                'aria-label' => 'Card Grid'
            ),
            'toolbar' => array(
                'role' => 'toolbar',
                'aria-label' => 'Card grid controls'
            ),
            'search' => array(
                'role' => 'searchbox',
                'aria-label' => 'Search cards'
            ),
            'grid' => array(
                'role' => 'list',
                'aria-label' => 'Card list'
            ),
            'card' => array(
                'role' => 'listitem',
                'aria-labelledby' => 'card-title-id',
                'tabindex' => '-1'
            ),
            'pagination' => array(
                'role' => 'navigation',
                'aria-label' => 'Card pagination'
            ),
            'live_region' => array(
                'role' => 'status',
                'aria-live' => 'polite',
                'aria-atomic' => 'true'
            ),
            'export_menu' => array(
                'role' => 'menu',
                'aria-label' => 'Export options'
            ),
            'export_item' => array(
                'role' => 'menuitem'
            )
        );
    }

    /**
     * Document expected screen reader announcements
     */
    public static function expected_announcements()
    {
        return array(
            'Search results: "X cards found matching Y"',
            'No results: "No cards found matching Y"',
            'Sort change: "Cards sorted A to Z"',
            'Page change: "Page X of Y"',
            'Items per page: "Showing X items per page"',
            'Export start: "Exporting X cards as FORMAT"',
            'Export complete: "Export complete: filename"',
            'Card focus: "Card X of Y: title"',
        );
    }
}

/**
 * CSS Accessibility Tests Documentation
 */
class Test_CardCrafter_Accessibility_CSS_Documentation
{

    /**
     * Document expected focus styles
     */
    public static function focus_style_requirements()
    {
        return array(
            'All interactive elements have visible focus indicator',
            'Focus indicator has minimum 3px width',
            'Focus indicator contrasts with background',
            'Focus-within works on card containers',
            'Focus is visible in high contrast mode',
            'Skip link becomes visible on focus',
        );
    }

    /**
     * Document reduced motion requirements
     */
    public static function reduced_motion_requirements()
    {
        return array(
            'Animations disabled when prefers-reduced-motion',
            'Transitions disabled when prefers-reduced-motion',
            'Hover transforms disabled when prefers-reduced-motion',
            'Spinner still works with slower animation',
        );
    }

    /**
     * Document high contrast mode requirements
     */
    public static function high_contrast_requirements()
    {
        return array(
            'Cards have visible borders',
            'Focus uses system Highlight color',
            'Buttons have visible borders',
            'Links use system LinkText color',
            'Current page uses HighlightText',
        );
    }
}

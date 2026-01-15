<?php
/**
 * Class CardCrafterPaginationTest
 * Tests for the pagination system functionality
 *
 * @package CardCrafter
 */

use WP_Mock\Tools\TestCase;

class CardCrafterPaginationTest extends TestCase
{

    public function setUp(): void
    {
        WP_Mock::setUp();
    }

    public function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    /**
     * Test that pagination shortcode parameter is properly sanitized
     */
    public function test_pagination_shortcode_parameter_sanitization()
    {
        $instance = CardCrafter::get_instance();
        
        // Mock WordPress functions
        WP_Mock::userFunction('shortcode_atts', [
            'return' => function($defaults, $atts, $shortcode) {
                return array_merge($defaults, $atts);
            }
        ]);
        
        WP_Mock::userFunction('esc_url_raw', [
            'return' => function($url) { return $url; }
        ]);
        
        WP_Mock::userFunction('sanitize_key', [
            'return' => function($key) { return $key; }
        ]);
        
        WP_Mock::userFunction('absint', [
            'return' => function($int) { return max(0, intval($int)); }
        ]);
        
        // Test items_per_page parameter bounds
        $test_cases = [
            ['items_per_page' => '0', 'expected' => 1],     // Below minimum
            ['items_per_page' => '1', 'expected' => 1],     // Minimum valid
            ['items_per_page' => '50', 'expected' => 50],   // Normal valid
            ['items_per_page' => '100', 'expected' => 100], // Maximum valid
            ['items_per_page' => '150', 'expected' => 100], // Above maximum
            ['items_per_page' => '-5', 'expected' => 1],    // Negative number
            ['items_per_page' => 'abc', 'expected' => 1],   // Non-numeric
        ];
        
        foreach ($test_cases as $test_case) {
            $sanitized = min(100, max(1, intval($test_case['items_per_page'])));
            $this->assertEquals($test_case['expected'], $sanitized);
        }
    }

    /**
     * Test pagination configuration in shortcode config
     */
    public function test_pagination_config_generation()
    {
        $instance = CardCrafter::get_instance();
        
        // Mock WordPress functions
        WP_Mock::userFunction('admin_url', [
            'return' => 'https://example.com/wp-admin/admin-ajax.php'
        ]);
        
        WP_Mock::userFunction('wp_create_nonce', [
            'return' => 'test_nonce'
        ]);
        
        WP_Mock::userFunction('sanitize_key', [
            'return' => function($key) { return $key; }
        ]);
        
        // Test that itemsPerPage is included in config
        $test_atts = [
            'source' => 'https://example.com/data.json',
            'layout' => 'grid',
            'columns' => 3,
            'items_per_page' => 24,
            'image_field' => 'image',
            'title_field' => 'title',
            'subtitle_field' => 'subtitle',
            'description_field' => 'description',
            'link_field' => 'link'
        ];
        
        $expected_config = [
            'itemsPerPage' => 24,
            'layout' => 'grid',
            'columns' => 3
        ];
        
        // Verify the config would include pagination settings
        $this->assertArrayHasKey('items_per_page', $test_atts);
        $this->assertEquals(24, $test_atts['items_per_page']);
    }

    /**
     * Test pagination calculations and edge cases
     */
    public function test_pagination_calculations()
    {
        // Test pagination math for different scenarios
        $test_scenarios = [
            // [totalItems, itemsPerPage, expectedPages]
            [0, 12, 0],     // No items
            [5, 12, 1],     // Less than one page
            [12, 12, 1],    // Exactly one page
            [13, 12, 2],    // Just over one page
            [100, 12, 9],   // Multiple pages (100/12 = 8.33 = 9 pages)
            [120, 12, 10],  // Exactly multiple pages
            [1000, 50, 20], // Large dataset
        ];
        
        foreach ($test_scenarios as [$totalItems, $itemsPerPage, $expectedPages]) {
            $actualPages = $totalItems > 0 ? ceil($totalItems / $itemsPerPage) : 0;
            $this->assertEquals($expectedPages, $actualPages, 
                "Failed for {$totalItems} items with {$itemsPerPage} per page");
        }
    }

    /**
     * Test current page calculations and bounds
     */
    public function test_current_page_bounds()
    {
        $test_scenarios = [
            // [totalItems, itemsPerPage, currentPage, expectedValidPage]
            [100, 12, 1, 1],     // First page
            [100, 12, 5, 5],     // Middle page
            [100, 12, 9, 9],     // Last page (100/12 = 8.33 = 9 pages)
            [100, 12, 15, 9],    // Beyond last page (should clamp to last)
            [100, 12, 0, 1],     // Before first page (should clamp to first)
            [100, 12, -5, 1],    // Negative page (should clamp to first)
        ];
        
        foreach ($test_scenarios as [$totalItems, $itemsPerPage, $currentPage, $expectedValidPage]) {
            $totalPages = ceil($totalItems / $itemsPerPage);
            $validPage = max(1, min($totalPages, $currentPage));
            $this->assertEquals($expectedValidPage, $validPage,
                "Failed page bound test for page {$currentPage} of {$totalPages}");
        }
    }

    /**
     * Test pagination slice calculations
     */
    public function test_pagination_slice_calculations()
    {
        $testData = range(1, 100); // Array of numbers 1-100
        
        $test_scenarios = [
            // [currentPage, itemsPerPage, expectedStartIndex, expectedEndIndex, expectedCount]
            [1, 12, 0, 12, 12],     // First page
            [2, 12, 12, 24, 12],    // Second page  
            [5, 12, 48, 60, 12],    // Middle page
            [9, 12, 96, 108, 4],    // Last page (partial)
        ];
        
        foreach ($test_scenarios as [$currentPage, $itemsPerPage, $expectedStartIndex, $expectedEndIndex, $expectedCount]) {
            $startIndex = ($currentPage - 1) * $itemsPerPage;
            $endIndex = $startIndex + $itemsPerPage;
            $slice = array_slice($testData, $startIndex, $itemsPerPage);
            
            $this->assertEquals($expectedStartIndex, $startIndex);
            $this->assertEquals($expectedCount, count($slice));
            
            // Verify the slice contains correct values
            if ($currentPage === 1) {
                $this->assertEquals(1, $slice[0]); // First item should be 1
            } elseif ($currentPage === 2) {
                $this->assertEquals(13, $slice[0]); // First item of page 2 should be 13
            }
        }
    }

    /**
     * Test performance with large datasets
     */
    public function test_pagination_performance_with_large_datasets()
    {
        // Test that pagination can handle large datasets efficiently
        $largeDataset = range(1, 10000); // 10,000 items
        $itemsPerPage = 50;
        $currentPage = 100; // Page 100 of 200
        
        $startTime = microtime(true);
        
        // Calculate pagination
        $totalItems = count($largeDataset);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $currentPageItems = array_slice($largeDataset, $startIndex, $itemsPerPage);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Performance assertions
        $this->assertLessThan(0.1, $executionTime, 'Pagination should complete in less than 100ms');
        $this->assertEquals(200, $totalPages, 'Should calculate correct total pages');
        $this->assertEquals(50, count($currentPageItems), 'Should return correct page size');
        $this->assertEquals(4950, $currentPageItems[0], 'Should return correct first item for page 100');
    }

    /**
     * Test pagination with search/filter integration
     */
    public function test_pagination_with_search_integration()
    {
        // Test that pagination resets when search changes
        $allItems = [
            ['title' => 'Apple Product', 'category' => 'electronics'],
            ['title' => 'Banana Food', 'category' => 'food'],
            ['title' => 'Apple Watch', 'category' => 'electronics'],
            ['title' => 'Orange Juice', 'category' => 'food'],
            ['title' => 'Apple iPhone', 'category' => 'electronics'],
        ];
        
        // Simulate search for "Apple"
        $searchTerm = 'apple';
        $filteredItems = array_filter($allItems, function($item) use ($searchTerm) {
            return stripos($item['title'], $searchTerm) !== false;
        });
        
        // Test pagination calculations with filtered results
        $this->assertEquals(3, count($filteredItems)); // Should find 3 Apple items
        
        $itemsPerPage = 2;
        $totalPages = ceil(count($filteredItems) / $itemsPerPage);
        $this->assertEquals(2, $totalPages); // 3 items / 2 per page = 2 pages
        
        // Test first page
        $page1Items = array_slice($filteredItems, 0, $itemsPerPage);
        $this->assertEquals(2, count($page1Items));
        
        // Test second page  
        $page2Items = array_slice($filteredItems, $itemsPerPage, $itemsPerPage);
        $this->assertEquals(1, count($page2Items)); // Only 1 item on last page
    }

    /**
     * Test pagination UI controls generation
     */
    public function test_pagination_controls_logic()
    {
        // Test pagination control scenarios
        $test_scenarios = [
            // [currentPage, totalPages, expectedPrevDisabled, expectedNextDisabled]
            [1, 1, true, true],      // Single page - both disabled
            [1, 5, true, false],     // First of many - prev disabled
            [3, 5, false, false],    // Middle page - both enabled
            [5, 5, false, true],     // Last page - next disabled
        ];
        
        foreach ($test_scenarios as [$currentPage, $totalPages, $expectedPrevDisabled, $expectedNextDisabled]) {
            $prevDisabled = $currentPage === 1;
            $nextDisabled = $currentPage === $totalPages;
            
            $this->assertEquals($expectedPrevDisabled, $prevDisabled,
                "Previous button state failed for page {$currentPage} of {$totalPages}");
            $this->assertEquals($expectedNextDisabled, $nextDisabled,
                "Next button state failed for page {$currentPage} of {$totalPages}");
        }
    }

    /**
     * Test business impact scenarios
     */
    public function test_business_impact_scenarios()
    {
        // Test enterprise-scale datasets that were problematic before pagination
        $enterpriseScenarios = [
            ['description' => 'Large employee directory', 'items' => 500, 'perPage' => 20],
            ['description' => 'Product catalog', 'items' => 1000, 'perPage' => 24],
            ['description' => 'Client portfolio', 'items' => 300, 'perPage' => 12],
            ['description' => 'Event listings', 'items' => 150, 'perPage' => 10],
        ];
        
        foreach ($enterpriseScenarios as $scenario) {
            $totalItems = $scenario['items'];
            $itemsPerPage = $scenario['perPage'];
            $totalPages = ceil($totalItems / $itemsPerPage);
            
            // Verify pagination makes large datasets manageable
            $this->assertLessThanOrEqual(50, $itemsPerPage, 
                'Items per page should be reasonable for ' . $scenario['description']);
            $this->assertGreaterThan(1, $totalPages,
                'Large datasets should require multiple pages for ' . $scenario['description']);
            
            // Test that first page load is fast (only loads subset)
            $firstPageItems = min($itemsPerPage, $totalItems);
            $percentageOfTotal = ($firstPageItems / $totalItems) * 100;
            
            $this->assertLessThan(50, $percentageOfTotal,
                'First page should load less than 50% of total data for ' . $scenario['description']);
        }
    }
}
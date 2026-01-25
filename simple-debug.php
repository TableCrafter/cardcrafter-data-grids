<?php
/**
 * Simple Demo Data Validation
 */

echo "🧪 CardCrafter Demo Data Validation\n";
echo "====================================\n\n";

$demo_files = [
    'team.json',
    'products.json', 
    'portfolio.json'
];

foreach ($demo_files as $file) {
    $filepath = __DIR__ . '/demo-data/' . $file;
    echo "📄 Testing {$file}:\n";
    
    if (!file_exists($filepath)) {
        echo "  ❌ File not found\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    if ($content === false) {
        echo "  ❌ Could not read file\n";
        continue;
    }
    
    $json_data = json_decode($content, true);
    if ($json_data === null) {
        echo "  ❌ Invalid JSON: " . json_last_error_msg() . "\n";
        continue;
    }
    
    if (!is_array($json_data)) {
        echo "  ❌ Not an array\n";
        continue;
    }
    
    echo "  ✅ Valid JSON with " . count($json_data) . " items\n";
    
    // Check first item structure
    if (!empty($json_data[0])) {
        $first_item = $json_data[0];
        $required_fields = ['title', 'image'];
        $has_required = true;
        
        foreach ($required_fields as $field) {
            if (!isset($first_item[$field])) {
                echo "  ⚠️  Missing required field: {$field}\n";
                $has_required = false;
            }
        }
        
        if ($has_required) {
            echo "  ✅ Required fields present\n";
        }
    }
    
    echo "\n";
}

echo "🔧 PHP Configuration:\n";
echo "=====================\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "JSON Extension: " . (extension_loaded('json') ? 'LOADED' : 'MISSING') . "\n";
echo "cURL Extension: " . (extension_loaded('curl') ? 'LOADED' : 'MISSING') . "\n";

echo "\n✅ Validation complete!\n";
<?php
if (!defined('ABSPATH')) {
    exit;
}

$team_url = CARDCRAFTER_URL . 'demo-data/team.json';
$products_url = CARDCRAFTER_URL . 'demo-data/products.json';
$portfolio_url = CARDCRAFTER_URL . 'demo-data/portfolio.json';
?>
<div class="wrap cc-welcome-wrap">
    <style>
        .cc-welcome-wrap {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8fafc;
            margin: 0 -20px;
            min-height: 100vh;
        }
        .cc-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 80px 40px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .cc-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50%" cy="0%" r="100%"><stop offset="0%" style="stop-color:rgba(255,255,255,0.1)"/><stop offset="100%" style="stop-color:rgba(255,255,255,0.05)"/></radialGradient></defs><rect width="100" height="20" fill="url(%23a)"/></svg>') repeat;
            opacity: 0.3;
        }
        .cc-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin: 0 0 20px 0;
            position: relative;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cc-hero p {
            font-size: 1.25rem;
            margin: 0 0 40px 0;
            opacity: 0.95;
            position: relative;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .cc-hero-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .cc-main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 40px;
        }
        .cc-demo-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 60px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .cc-demo-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .cc-demo-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 0 15px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .cc-demo-header p {
            font-size: 1.1rem;
            color: #6b7280;
            margin: 0;
        }
        .cc-demo-controls {
            background: #f8fafc;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }
        .cc-controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .cc-control-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .cc-control-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        .cc-control-input {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .cc-control-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .cc-toggle-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .cc-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 8px 16px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
        }
        .cc-toggle:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        .cc-toggle input[type="checkbox"] {
            margin: 0;
            accent-color: #667eea;
        }
        .cc-refresh-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
            margin-top: 20px;
        }
        .cc-refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        .cc-demo-preview {
            background: #f8fafc;
            border-radius: 16px;
            padding: 30px;
            min-height: 500px;
            border: 2px dashed #e5e7eb;
            position: relative;
        }
        .cc-demo-banner {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            color: white;
            text-align: center;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .cc-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin: 60px 0;
        }
        .cc-feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-align: center;
        }
        .cc-feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        .cc-feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }
        .cc-feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 15px 0;
            color: #1f2937;
        }
        .cc-feature-desc {
            color: #6b7280;
            line-height: 1.6;
            margin: 0;
        }
        .cc-sidebar {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .cc-cta-section {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.3);
        }
        .cc-cta-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        .cc-cta-subtitle {
            font-size: 1rem;
            margin: 0 0 25px 0;
            opacity: 0.9;
        }
        .cc-feature-list {
            list-style: none;
            margin: 25px 0;
            padding: 0;
            text-align: left;
        }
        .cc-feature-list li {
            margin-bottom: 8px;
            padding-left: 25px;
            position: relative;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.9);
        }
        .cc-feature-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 1rem;
        }
        .cc-lead-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .cc-email-input {
            padding: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 12px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 1rem;
            backdrop-filter: blur(10px);
        }
        .cc-email-input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        .cc-email-input:focus {
            outline: none;
            border-color: white;
            background: rgba(255,255,255,0.15);
        }
        .cc-submit-btn {
            background: white;
            color: #059669;
            border: none;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .cc-submit-btn:hover {
            background: #f3f4f6;
            transform: translateY(-1px);
        }
        .cc-trust-text {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 10px;
        }
        .cc-dashboard-btn {
            display: block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 20px 30px;
            border-radius: 16px;
            font-weight: 700;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            font-size: 1.1rem;
        }
        .cc-dashboard-btn:hover {
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        .cc-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            flex-direction: column;
            gap: 20px;
        }
        .cc-loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: cc-spin 1s linear infinite;
        }
        @keyframes cc-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .cc-two-column {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            align-items: start;
        }
        @media (max-width: 1024px) {
            .cc-two-column {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .cc-hero {
                padding: 60px 20px;
            }
            .cc-hero h1 {
                font-size: 2.5rem;
            }
            .cc-main-content {
                padding: 40px 20px;
            }
        }
    </style>

    <!-- Hero Section -->
    <div class="cc-hero">
        <div class="cc-hero-badge">🚀 Just Activated</div>
        <h1>Welcome to CardCrafter</h1>
        <p>Transform any data into beautiful, responsive card layouts. See it in action below!</p>
    </div>

    <div class="cc-main-content">
        <div class="cc-two-column">
            <div class="cc-main">
                <!-- Interactive Demo -->
                <div class="cc-demo-section">
                    <div class="cc-demo-header">
                        <h2>✨ Live Interactive Demo</h2>
                        <p>Try different layouts, datasets, and settings to see CardCrafter in action</p>
                    </div>

                    <div class="cc-demo-controls">
                        <div class="cc-controls-grid">
                            <div class="cc-control-group">
                                <label class="cc-control-label">📊 Dataset</label>
                                <select id="cc-demo-dataset" class="cc-control-input">
                                    <option value="<?php echo esc_url($team_url); ?>">👥 Team Directory</option>
                                    <option value="<?php echo esc_url($products_url); ?>">🛍️ Product Showcase</option>
                                    <option value="<?php echo esc_url($portfolio_url); ?>">🎨 Portfolio Gallery</option>
                                </select>
                            </div>

                            <div class="cc-control-group">
                                <label class="cc-control-label">🎨 Layout Style</label>
                                <select id="cc-demo-layout" class="cc-control-input">
                                    <option value="grid">Grid Layout</option>
                                    <option value="masonry">Masonry Layout</option>
                                    <option value="list">List Layout</option>
                                </select>
                            </div>

                            <div class="cc-control-group">
                                <label class="cc-control-label">📐 Columns</label>
                                <select id="cc-demo-columns" class="cc-control-input">
                                    <option value="2">2 Columns</option>
                                    <option value="3" selected>3 Columns</option>
                                    <option value="4">4 Columns</option>
                                </select>
                            </div>
                        </div>

                        <div class="cc-toggle-group">
                            <label class="cc-toggle">
                                <input type="checkbox" id="cc-demo-search" checked>
                                <span>🔍 Enable Search</span>
                            </label>
                        </div>

                        <button id="cc-refresh-demo" class="cc-refresh-btn">🔄 Generate Cards</button>
                    </div>

                    <div class="cc-demo-banner">
                        🎯 This is a fully functional CardCrafter instance - try searching, clicking, and exploring!
                    </div>

                    <div id="cc-demo-container" class="cc-demo-preview">
                        <div class="cc-loading">
                            <div class="cc-loading-spinner"></div>
                            <p>Click "Generate Cards" to load the demo</p>
                        </div>
                    </div>
                </div>

                <!-- Features Grid -->
                <div class="cc-features-grid">
                    <div class="cc-feature-card">
                        <span class="cc-feature-icon">🎨</span>
                        <h3 class="cc-feature-title">Multiple Layouts</h3>
                        <p class="cc-feature-desc">Choose from Grid, Masonry, or List layouts. All fully responsive and customizable to match your design.</p>
                    </div>
                    
                    <div class="cc-feature-card">
                        <span class="cc-feature-icon">📊</span>
                        <h3 class="cc-feature-title">Any Data Source</h3>
                        <p class="cc-feature-desc">JSON APIs, WordPress posts, WooCommerce products, or CSV files. Built-in caching for fast performance.</p>
                    </div>
                    
                    <div class="cc-feature-card">
                        <span class="cc-feature-icon">🔍</span>
                        <h3 class="cc-feature-title">Live Search & Filter</h3>
                        <p class="cc-feature-desc">Real-time search and filtering. Users find what they need instantly with smart search algorithms.</p>
                    </div>
                    
                    <div class="cc-feature-card">
                        <span class="cc-feature-icon">📱</span>
                        <h3 class="cc-feature-title">Mobile Optimized</h3>
                        <p class="cc-feature-desc">Perfect on all devices. Responsive breakpoints and touch-friendly interactions included.</p>
                    </div>
                    
                    <div class="cc-feature-card">
                        <span class="cc-feature-icon">🔧</span>
                        <h3 class="cc-feature-title">WordPress Native</h3>
                        <p class="cc-feature-desc">Display WordPress posts, custom post types, and ACF fields as cards with zero configuration.</p>
                    </div>
                    
                    <div class="cc-feature-card">
                        <span class="cc-feature-icon">⚡</span>
                        <h3 class="cc-feature-title">Performance First</h3>
                        <p class="cc-feature-desc">Smart caching, lazy loading, and optimized code ensure lightning-fast load times.</p>
                    </div>
                </div>
            </div>

            <div class="cc-sidebar">
                <!-- Lead Magnet -->
                <div class="cc-cta-section">
                    <div style="font-size: 2.5rem; margin-bottom: 15px;">🎨</div>
                    <h3 class="cc-cta-title">25+ Card Design Templates</h3>
                    <p class="cc-cta-subtitle">Professional layouts + CSS code - Ready to use!</p>
                    
                    <ul class="cc-feature-list">
                        <li>Team directory designs</li>
                        <li>Product showcase templates</li>
                        <li>Portfolio gallery layouts</li>
                        <li>Custom CSS snippets included</li>
                    </ul>
                    
                    <form id="cc-lead-form" class="cc-lead-form">
                        <input type="email" id="cc-email" placeholder="Enter your email address" required class="cc-email-input">
                        <button type="submit" class="cc-submit-btn">Get Free Templates →</button>
                        <p class="cc-trust-text">Instant download • No spam • 1,500+ designers trust us</p>
                    </form>
                    
                    <div id="cc-lead-success" style="display: none; padding: 20px; background: rgba(255,255,255,0.2); border-radius: 12px; text-align: center; margin-top: 16px;">
                        <p style="margin: 0; font-size: 14px; font-weight: 600;">✅ Check your email!</p>
                        <p style="margin: 8px 0 0; font-size: 12px; line-height: 1.5;">Your design templates are on their way</p>
                    </div>
                </div>
                
                <!-- Quick Action -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=cardcrafter')); ?>" class="cc-dashboard-btn">
                    🚀 Start Creating Cards
                </a>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            let demoInstance = null;
            const demoContainer = $('#cc-demo-container');
            
            function loadDemo() {
                const dataset = $('#cc-demo-dataset').val();
                const layout = $('#cc-demo-layout').val();
                const columns = $('#cc-demo-columns').val();
                const enableSearch = $('#cc-demo-search').is(':checked');
                
                demoContainer.html('<div class="cc-loading"><div class="cc-loading-spinner"></div><p>Loading cards...</p></div>');
                
                if (typeof window.CardCrafter === 'undefined') {
                    demoContainer.html('<div class="cc-loading"><p style="color: #ef4444;">⚠️ CardCrafter library not loaded. Please refresh the page.</p></div>');
                    return;
                }
                
                // Add a unique ID to the demo container for CardCrafter
                demoContainer.attr('id', 'cc-demo-' + Date.now());
                const containerId = '#' + demoContainer.attr('id');
                
                // Create CardCrafter instance with correct options format
                const config = {
                    selector: containerId,
                    source: dataset,
                    layout: layout,
                    columns: parseInt(columns),
                    itemsPerPage: 6,
                    search: enableSearch,
                    fields: {
                        image: 'image',
                        title: 'title',
                        subtitle: 'subtitle',
                        description: 'description',
                        link: 'link'
                    }
                };
                
                if (demoInstance) {
                    demoInstance.destroy();
                }
                
                // Clear container first
                demoContainer.empty();
                
                // Initialize CardCrafter with correct options format
                demoInstance = new CardCrafter(config);
            }
            
            // Event handlers
            $('#cc-refresh-demo').on('click', loadDemo);
            
            // Auto-load first demo after a short delay
            setTimeout(loadDemo, 1000);

            // Lead form submission
            $('#cc-lead-form').on('submit', function(e) {
                e.preventDefault();
                const email = $('#cc-email').val();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();
                
                submitBtn.text('Sending...').prop('disabled', true);
                
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    method: 'POST',
                    data: {
                        action: 'cc_subscribe_lead',
                        email: email,
                        nonce: '<?php echo wp_create_nonce('cc_lead_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#cc-lead-form').hide();
                            $('#cc-lead-success').show();
                        } else {
                            alert('Error: ' + response.data);
                            submitBtn.text(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Connection error. Please try again.');
                        submitBtn.text(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>
</div>

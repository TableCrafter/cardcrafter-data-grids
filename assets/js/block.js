/**
 * CardCrafter Gutenberg Block
 * 
 * Provides a visual block editor interface for creating card grids
 * from JSON data sources with client-side preview and configuration options.
 */
(function (blocks, editor, components, element) {
    const el = element.createElement;
    const { useState, useEffect } = element;
    const { InspectorControls } = editor;
    const { PanelBody, TextControl, ToggleControl, SelectControl, ExternalLink, Spinner } = components;

    // Client-side preview component
    const CardCrafterPreview = function(props) {
        const { attributes } = props;
        const [isLoading, setIsLoading] = useState(false);
        const [data, setData] = useState(null);
        const [error, setError] = useState(null);

        useEffect(() => {
            if (!attributes.source || attributes.source.trim() === '') {
                setData(null);
                setError(null);
                return;
            }

            setIsLoading(true);
            setError(null);

            // Fetch data with timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

            fetch(attributes.source, {
                signal: controller.signal,
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            })
            .then(response => {
                clearTimeout(timeoutId);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(jsonData => {
                setIsLoading(false);
                setData(Array.isArray(jsonData) ? jsonData.slice(0, 6) : []); // Limit to 6 for preview
            })
            .catch(err => {
                clearTimeout(timeoutId);
                setIsLoading(false);
                if (err.name === 'AbortError') {
                    setError('Request timed out. Please check your data source URL.');
                } else {
                    setError(`Failed to load data: ${err.message}`);
                }
            });

            return () => {
                clearTimeout(timeoutId);
                controller.abort();
            };
        }, [attributes.source]);

        // Loading state
        if (isLoading) {
            return el('div', { 
                style: { 
                    padding: '40px', 
                    textAlign: 'center', 
                    background: '#f9f9f9',
                    border: '1px solid #ddd',
                    borderRadius: '4px'
                }
            },
                el(Spinner),
                el('p', { style: { marginTop: '16px', color: '#666' } }, 'Loading preview data...')
            );
        }

        // Error state
        if (error) {
            return el('div', { 
                style: { 
                    padding: '20px', 
                    textAlign: 'center', 
                    background: '#fef2f2',
                    border: '1px solid #fecaca',
                    borderRadius: '4px',
                    color: '#dc2626'
                }
            },
                el('p', null, '❌ ' + error),
                el('p', { style: { fontSize: '12px', marginTop: '8px' } }, 'Try selecting a demo data source or check your URL.')
            );
        }

        // Empty state
        if (!data || data.length === 0) {
            return el('div', { 
                style: { 
                    padding: '20px', 
                    textAlign: 'center', 
                    border: '1px dashed #ccc', 
                    borderRadius: '4px',
                    background: '#f9f9f9'
                }
            },
                el('div', { 
                    className: 'dashicons dashicons-grid-view',
                    style: { fontSize: '48px', marginBottom: '10px', color: '#666' }
                }),
                el('p', { style: { color: '#666' } }, '📝 Configure your data source to see live preview'),
                el('p', { style: { fontSize: '12px', color: '#999' } }, 'Select a demo data source to get started quickly.')
            );
        }

        // Preview with data
        const layoutClass = 'cardcrafter-preview-' + (attributes.layout || 'grid');
        const colsClass = 'cardcrafter-preview-cols-' + (attributes.cards_per_row || 3);

        return el('div', { 
            className: `cardcrafter-preview ${layoutClass} ${colsClass}`,
            style: { 
                background: '#fff',
                border: '1px solid #ddd',
                borderRadius: '4px',
                padding: '16px'
            }
        },
            el('div', { style: { marginBottom: '12px', fontSize: '12px', color: '#666', textAlign: 'center' } },
                `Preview: ${data.length} items • ${attributes.layout || 'grid'} layout • ${attributes.cards_per_row || 3} columns`
            ),
            el('div', { 
                className: 'cardcrafter-preview-grid',
                style: {
                    display: 'grid',
                    gap: '12px',
                    gridTemplateColumns: attributes.layout === 'list' ? '1fr' : 
                        `repeat(${Math.min(attributes.cards_per_row || 3, 3)}, 1fr)`
                }
            },
                data.map((item, index) => 
                    el('div', { 
                        key: index,
                        className: 'cardcrafter-preview-card',
                        style: {
                            background: '#fff',
                            border: '1px solid #e5e7eb',
                            borderRadius: '8px',
                            overflow: 'hidden',
                            boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
                            display: attributes.layout === 'list' ? 'flex' : 'block'
                        }
                    },
                        // Image
                        item.image && el('div', {
                            style: {
                                background: item.image ? `url(${item.image})` : '#f3f4f6',
                                backgroundSize: 'cover',
                                backgroundPosition: 'center',
                                height: attributes.layout === 'list' ? '80px' : '120px',
                                width: attributes.layout === 'list' ? '80px' : '100%',
                                flexShrink: 0
                            }
                        }),
                        // Content
                        el('div', { style: { padding: '12px', flex: 1 } },
                            item.title && el('h4', { 
                                style: { 
                                    margin: '0 0 4px 0', 
                                    fontSize: '14px', 
                                    fontWeight: '600',
                                    overflow: 'hidden',
                                    textOverflow: 'ellipsis',
                                    whiteSpace: 'nowrap'
                                } 
                            }, item.title),
                            item.subtitle && el('p', { 
                                style: { 
                                    margin: '0 0 8px 0', 
                                    fontSize: '12px', 
                                    color: '#6b7280',
                                    overflow: 'hidden',
                                    textOverflow: 'ellipsis',
                                    whiteSpace: 'nowrap'
                                } 
                            }, item.subtitle),
                            item.description && el('p', { 
                                style: { 
                                    margin: '0', 
                                    fontSize: '11px', 
                                    color: '#9ca3af',
                                    overflow: 'hidden',
                                    textOverflow: 'ellipsis',
                                    display: '-webkit-box',
                                    WebkitLineClamp: 2,
                                    WebkitBoxOrient: 'vertical'
                                } 
                            }, item.description)
                        )
                    )
                )
            )
        );
    };

    blocks.registerBlockType('cardcrafter/data-grid', {
        title: 'CardCrafter',
        description: 'Transform JSON data into beautiful, responsive card grids for portfolios, team directories, and product showcases.',
        icon: el('svg', { 
            width: 24, 
            height: 24, 
            viewBox: '0 0 24 24', 
            fill: 'none',
            style: { color: '#e11d48' }
        },
            // Card grid outline
            el('rect', { 
                x: 3, y: 3, width: 8, height: 8, 
                rx: 1, 
                stroke: 'currentColor', 
                strokeWidth: 2,
                fill: 'currentColor',
                fillOpacity: 0.1
            }),
            el('rect', { 
                x: 13, y: 3, width: 8, height: 8, 
                rx: 1, 
                stroke: 'currentColor', 
                strokeWidth: 2,
                fill: 'currentColor',
                fillOpacity: 0.1
            }),
            el('rect', { 
                x: 3, y: 13, width: 8, height: 8, 
                rx: 1, 
                stroke: 'currentColor', 
                strokeWidth: 2,
                fill: 'currentColor',
                fillOpacity: 0.1
            }),
            el('rect', { 
                x: 13, y: 13, width: 8, height: 8, 
                rx: 1, 
                stroke: 'currentColor', 
                strokeWidth: 2,
                fill: 'currentColor',
                fillOpacity: 0.1
            })
        ),
        category: 'widgets',

        // Define block attributes to persist in database
        attributes: {
            source: { type: 'string', default: '' },
            layout: { type: 'string', default: 'grid' },
            search: { type: 'boolean', default: true },
            sort: { type: 'boolean', default: true },
            cards_per_row: { type: 'number', default: 3 }
        },

        edit: function (props) {
            const { attributes, setAttributes } = props;
            
            const updateSource = (value) => setAttributes({ source: value });
            const updateLayout = (value) => setAttributes({ layout: value });
            const updateSearch = (value) => setAttributes({ search: value });
            const updateSort = (value) => setAttributes({ sort: value });
            const updateCardsPerRow = (value) => setAttributes({ cards_per_row: parseInt(value) || 3 });

            return [
                // Sidebar controls (Inspector)
                el(InspectorControls, { key: 'controls' },
                    el(PanelBody, { title: 'Data Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'JSON Data Source URL',
                            value: attributes.source,
                            onChange: updateSource,
                            help: 'URL to your JSON data source (must be publicly accessible).'
                        }),
                        el(SelectControl, {
                            label: 'Demo Data Sources',
                            value: attributes.source,
                            options: window.cardcrafterData ? window.cardcrafterData.demoUrls : [
                                { label: 'Select a demo...', value: '' },
                                { label: '👥 Team Directory', value: '' },
                                { label: '📦 Product Showcase', value: '' },
                                { label: '🎨 Portfolio Gallery', value: '' }
                            ],
                            onChange: updateSource,
                            help: 'Try different data formats with sample card content.'
                        }),
                        el(SelectControl, {
                            label: 'Layout Type',
                            value: attributes.layout,
                            options: [
                                { label: 'Grid Layout', value: 'grid' },
                                { label: 'Masonry Layout', value: 'masonry' },
                                { label: 'List Layout', value: 'list' }
                            ],
                            onChange: updateLayout,
                            help: 'Choose how cards are arranged: grid, masonry, or vertical list.'
                        }),
                        el(TextControl, {
                            label: 'Cards Per Row',
                            value: attributes.cards_per_row,
                            type: 'number',
                            onChange: updateCardsPerRow,
                            help: 'Number of cards to display per row (1-6, default: 3).'
                        })
                    ),
                    el(PanelBody, { title: 'Interactive Features', initialOpen: false },
                        el(ToggleControl, {
                            label: 'Enable Search',
                            checked: attributes.search,
                            onChange: updateSearch,
                            help: 'Add a search bar to filter cards by content.'
                        }),
                        el(ToggleControl, {
                            label: 'Enable Sorting',
                            checked: attributes.sort,
                            onChange: updateSort,
                            help: 'Allow users to sort cards alphabetically or by custom fields.'
                        }),
                        el('div', { className: 'cc-block-help', style: { marginTop: '20px', borderTop: '1px solid #eee', paddingTop: '15px' } },
                            el('p', null, 'Need help? Check the '),
                            el(ExternalLink, { href: 'https://github.com/TableCrafter/cardcrafter-data-grids' }, 'Documentation')
                        )
                    )
                ),

                // Main visual editor view (Client-side Preview)
                el(CardCrafterPreview, { 
                    key: 'preview',
                    attributes: attributes,
                    className: props.className
                })
            ];
        },

        save: function () {
            // Return null to use PHP render callback
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.element
);
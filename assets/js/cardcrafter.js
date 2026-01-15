/**
 * CardCrafter - JSON to Card Layouts
 * A lightweight JavaScript library for rendering JSON data as beautiful card grids.
 * 
 * @version 1.0.0
 * @license GPLv2 or later
 */

(function (global) {
    'use strict';

    /**
     * CardCrafter Constructor
     * @param {Object} options - Configuration options
     */
    function CardCrafter(options) {
        this.options = Object.assign({
            selector: null,
            source: null,
            layout: 'grid',
            columns: 3,
            fields: {
                image: 'image',
                title: 'title',
                subtitle: 'subtitle',
                description: 'description',
                link: 'link'
            }
        }, options);

        if (!this.options.selector || !this.options.source) {
            console.error('CardCrafter: selector and source are required');
            return;
        }

        this.container = document.querySelector(this.options.selector);
        if (!this.container) {
            console.error('CardCrafter: Container not found:', this.options.selector);
            return;
        }

        this.items = []; // Stores the original fetched data
        this.filteredItems = []; // Stores items after search/filter operations
        this.gridWrapper = null; // Will hold the DOM element for the card grid
        this.searchTimeout = null; // Debounce timeout for search
        this.searchCache = {}; // Cache search results for better performance

        this.init();
    }

    /**
     * DOM Elements creation helper
     */
    CardCrafter.prototype.createEl = function (tag, className, attributes) {
        var el = document.createElement(tag);
        if (className) el.className = className;
        if (attributes) {
            for (var key in attributes) {
                if (attributes.hasOwnProperty(key)) {
                    el.setAttribute(key, attributes[key]);
                }
            }
        }
        return el;
    };

    /**
     * Initialize the card grid
     */
    CardCrafter.prototype.init = function () {
        this.container.innerHTML = '<div class="cardcrafter-loading"><div class="cardcrafter-spinner"></div><p>Loading cards...</p></div>';
        this.fetchData();
    };

    /**
     * Fetch JSON data from source
     */
    CardCrafter.prototype.fetchData = function () {
        var self = this;

        fetch(this.options.source)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function (response) {
                var data;
                // Handle WP AJAX response format
                if (response.success !== undefined) {
                    if (response.success) {
                        data = response.data;
                    } else {
                        throw new Error(response.data || 'Unknown error fetching data');
                    }
                } else {
                    // Standard direct JSON fetch
                    data = response;
                }

                // Store raw items
                self.items = Array.isArray(data) ? data : (data.items || data.data || data.results || [data]);
                self.filteredItems = self.items.slice(); // Copy for filtering

                self.renderLayout();
            })
            .catch(function (error) {
                console.error('CardCrafter fetch error:', error);
                self.renderError(error);
            });
    };

    /**
     * Render the main layout structure (Toolbar + Grid)
     */
    CardCrafter.prototype.renderLayout = function () {
        this.container.innerHTML = '';
        
        // render toolbar
        this.renderToolbar();

        // create grid wrapper
        this.gridWrapper = this.createEl('div', 'cardcrafter-grid-wrapper');
        this.container.appendChild(this.gridWrapper);

        // initial sort and render
        this.sortItems('default');
        this.renderCards(this.filteredItems);
    };

    /**
     * Render the Search and Sort Toolbar
     */
    CardCrafter.prototype.renderToolbar = function () {
        var self = this;
        var toolbar = this.createEl('div', 'cardcrafter-toolbar');

        // Search Input
        var searchWrapper = this.createEl('div', 'cardcrafter-search-wrapper');
        var searchInput = this.createEl('input', 'cardcrafter-search-input', {
            type: 'search',
            placeholder: 'Search items...'
        });

        searchInput.addEventListener('input', function (e) {
            self.debouncedSearch(e.target.value);
        });

        searchWrapper.appendChild(searchInput);
        toolbar.appendChild(searchWrapper);

        // Sort Dropdown
        var sortWrapper = this.createEl('div', 'cardcrafter-sort-wrapper');
        var sortSelect = this.createEl('select', 'cardcrafter-sort-select');
        
        var options = [
            { value: 'default', text: 'Default Order' },
            { value: 'az', text: 'Name (A-Z)' },
            { value: 'za', text: 'Name (Z-A)' }
        ];

        options.forEach(function(opt) {
            var option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.text;
            sortSelect.appendChild(option);
        });

        sortSelect.addEventListener('change', function (e) {
            self.sortItems(e.target.value);
            self.renderCards(self.filteredItems);
        });

        sortWrapper.appendChild(sortSelect);
        toolbar.appendChild(sortWrapper);

        this.container.appendChild(toolbar);
    };

    /**
     * Debounced Search with Performance Optimization
     */
    CardCrafter.prototype.debouncedSearch = function (query) {
        var self = this;
        
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Set new timeout for debouncing
        this.searchTimeout = setTimeout(function() {
            self.handleSearch(query);
        }, 300); // 300ms debounce delay
    };

    /**
     * Handle Search Input
     */
    CardCrafter.prototype.handleSearch = function (query) {
        var self = this;
        var q = query.toLowerCase().trim();

        // Check cache first for performance
        if (this.searchCache[q]) {
            this.filteredItems = this.searchCache[q].slice();
        } else {
            if (!q) {
                this.filteredItems = this.items.slice();
            } else {
                // Performance optimization: pre-compute searchable text for each item
                this.filteredItems = this.items.filter(function (item) {
                    // Cache searchable text for this item if not already cached
                    if (!item._searchableText) {
                        var title = (self.getNestedValue(item, self.options.fields.title) || '').toLowerCase();
                        var desc = (self.getNestedValue(item, self.options.fields.description) || '').toLowerCase();
                        var sub = (self.getNestedValue(item, self.options.fields.subtitle) || '').toLowerCase();
                        item._searchableText = title + ' ' + desc + ' ' + sub;
                    }
                    
                    return item._searchableText.includes(q);
                });
            }
            
            // Cache the results (limit cache size to prevent memory bloat)
            if (Object.keys(this.searchCache).length > 50) {
                this.searchCache = {}; // Reset cache when it gets too large
            }
            this.searchCache[q] = this.filteredItems.slice();
        }
        
        // Re-apply current sort
        var currentSort = this.container.querySelector('.cardcrafter-sort-select').value;
        this.sortItems(currentSort);

        this.renderCards(this.filteredItems);
    };

    /**
     * Sort Items
     */
    CardCrafter.prototype.sortItems = function (sortType) {
        var self = this;
        
        if (sortType === 'default') {
            // If default, we can't easily restore original order unless we stored indices or cloned original.
            // But since handleSearch resets filteredItems from .items (which is original order), 
            // we just need to not sort if default, assuming .items is preserved in order.
            // If the user searches then goes back to default, handleSearch resets from this.items.
            // So we just don't sort here.
            return; 
        }

        this.filteredItems.sort(function (a, b) {
            var titleA = (self.getNestedValue(a, self.options.fields.title) || '').toLowerCase();
            var titleB = (self.getNestedValue(b, self.options.fields.title) || '').toLowerCase();

            if (sortType === 'az') {
                return titleA.localeCompare(titleB);
            } else if (sortType === 'za') {
                return titleB.localeCompare(titleA);
            }
            return 0;
        });
    };

    /**
     * Get nested value from object using dot notation
     */
    CardCrafter.prototype.getNestedValue = function (obj, path) {
        if (!path) return null;
        var keys = path.split('.');
        var value = obj;
        for (var i = 0; i < keys.length; i++) {
            if (value && typeof value === 'object' && keys[i] in value) {
                value = value[keys[i]];
            } else {
                return null;
            }
        }
        return value;
    };

    /**
     * Generate placeholder image URL
     */
    CardCrafter.prototype.getPlaceholderImage = function (text) {
        // Simple SVG placeholder
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">' +
            '<rect fill="#e0e0e0" width="400" height="300"/>' +
            '<text fill="#888" font-family="sans-serif" font-size="24" text-anchor="middle" x="200" y="160">' +
            (text ? text.substring(0, 20) : 'No Image') + '</text></svg>';
        return 'data:image/svg+xml;base64,' + btoa(svg);
    };

    /**
     * Render cards from data
     */
    CardCrafter.prototype.renderCards = function (items) {
        var self = this;
        
        if (!items.length) {
            this.gridWrapper.innerHTML = '<div class="cardcrafter-no-results"><p>No items found matching your search.</p></div>';
            return;
        }

        // Create grid container
        var grid = document.createElement('div');
        grid.className = 'cardcrafter-grid cardcrafter-layout-' + this.options.layout + ' cardcrafter-cols-' + this.options.columns;

        // Performance optimization: Use DocumentFragment for batch DOM operations
        var fragment = document.createDocumentFragment();
        
        // Generate cards
        items.forEach(function (item, index) {
            var card = self.createCard(item, index);
            fragment.appendChild(card);
        });
        
        grid.appendChild(fragment);
        this.gridWrapper.innerHTML = '';
        this.gridWrapper.appendChild(grid);
    };

    /**
     * Create a single card element
     */
    CardCrafter.prototype.createCard = function (item, index) {
        var self = this;
        var fields = this.options.fields;

        // Extract field values
        var image = this.getNestedValue(item, fields.image);
        var title = this.getNestedValue(item, fields.title) || 'Untitled';
        var subtitle = this.getNestedValue(item, fields.subtitle);
        var description = this.getNestedValue(item, fields.description);
        var link = this.getNestedValue(item, fields.link);

        // Create card element
        var card = document.createElement('div');
        card.className = 'cardcrafter-card';
        card.setAttribute('data-index', index);

        // Card inner wrapper (for animations)
        var cardInner = document.createElement('div');
        cardInner.className = 'cardcrafter-card-inner';

        // Image section
        var imageSection = document.createElement('div');
        imageSection.className = 'cardcrafter-card-image';
        var img = document.createElement('img');
        img.src = image || this.getPlaceholderImage(title);
        img.alt = title;
        img.loading = 'lazy';
        img.onerror = function () {
            this.src = self.getPlaceholderImage(title);
        };
        imageSection.appendChild(img);
        cardInner.appendChild(imageSection);

        // Content section
        var content = document.createElement('div');
        content.className = 'cardcrafter-card-content';

        // Title
        var titleEl = document.createElement('h3');
        titleEl.className = 'cardcrafter-card-title';
        titleEl.textContent = title;
        content.appendChild(titleEl);

        // Subtitle
        if (subtitle) {
            var subtitleEl = document.createElement('p');
            subtitleEl.className = 'cardcrafter-card-subtitle';
            subtitleEl.textContent = subtitle;
            content.appendChild(subtitleEl);
        }

        // Description
        if (description) {
            var descEl = document.createElement('p');
            descEl.className = 'cardcrafter-card-description';
            // Truncate if too long
            descEl.textContent = description.length > 150 ? description.substring(0, 147) + '...' : description;
            content.appendChild(descEl);
        }

        // Link/Button
        if (link) {
            var linkEl = document.createElement('a');
            linkEl.className = 'cardcrafter-card-link';
            linkEl.href = link;
            linkEl.textContent = 'Learn More';
            linkEl.target = '_blank';
            linkEl.rel = 'noopener noreferrer';
            content.appendChild(linkEl);
        }

        cardInner.appendChild(content);
        card.appendChild(cardInner);

        return card;
    };

    /**
     * Render Error State
     */
    CardCrafter.prototype.renderError = function (error) {
        var self = this;
        var errorMsg = error.message || 'Check your internet connection or data source URL.';

        this.container.innerHTML =
            '<div class="cardcrafter-error-state" style="padding: 40px; text-align: center; border: 1px solid #fee2e2; background: #fef2f2; border-radius: 8px;">' +
            '<div style="font-size: 24px; margin-bottom: 10px;">⚠️</div>' +
            '<h3 style="margin: 0 0 10px 0; color: #991b1b;">Unable to load cards</h3>' +
            '<p style="margin: 0 0 20px 0; color: #b91c1c; font-size: 14px;">' + errorMsg + '</p>' +
            '<button class="cardcrafter-retry-button" style="background: #991b1b; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">' +
            'Retry Loading' +
            '</button>' +
            '</div>';

        var retryBtn = this.container.querySelector('.cardcrafter-retry-button');
        if (retryBtn) {
            retryBtn.addEventListener('click', function () {
                self.init();
            });
        }
    };

    // Expose to global scope
    global.CardCrafter = CardCrafter;

})(typeof window !== 'undefined' ? window : this);

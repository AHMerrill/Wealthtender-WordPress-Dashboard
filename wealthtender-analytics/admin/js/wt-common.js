/**
 * Wealthtender Analytics - Common JavaScript Utilities
 * Global namespace: WT
 *
 * Requires wtAnalytics object injected via wp_localize_script:
 * {
 *   restUrl: string,
 *   nonce: string,
 *   dimensions: string[],
 *   dimLabels: object,
 *   dimShort: object,
 *   dimColors: object,
 *   dataVizPalette: string[],
 *   colors: object
 * }
 */

const WT = {};

/* ============================================================================
   API Helper - Authenticated REST Calls
   ============================================================================ */

/**
 * Make authenticated REST API call
 * @param {string} endpoint - API endpoint (e.g., 'profiles/123/metrics')
 * @param {object} params - Query parameters
 * @returns {Promise<object>} JSON response
 * @throws {Error} If response is not OK
 */
WT.api = function(endpoint, paramsOrCallback, callbackOrError, errorCallback) {
    // Support multiple calling patterns:
    //   WT.api('endpoint')                              → Promise
    //   WT.api('endpoint', params)                      → Promise
    //   WT.api('endpoint', callback)                    → calls callback
    //   WT.api('endpoint', successCb, errorCb)          → calls callbacks
    //   WT.api('endpoint', params, callback)            → calls callback
    //   WT.api('endpoint', params, callback, errorCb)   → calls callbacks
    var params = {};
    var successCb = null;
    var errCb = null;

    if (typeof paramsOrCallback === 'function') {
        successCb = paramsOrCallback;
        if (typeof callbackOrError === 'function') {
            errCb = callbackOrError;
        }
    } else if (paramsOrCallback && typeof paramsOrCallback === 'object') {
        params = paramsOrCallback;
        if (typeof callbackOrError === 'function') {
            successCb = callbackOrError;
            if (typeof errorCallback === 'function') {
                errCb = errorCallback;
            }
        }
    }

    var url = new URL(wtAnalytics.restUrl + 'wt/v1/' + endpoint);

    // Add parameters to query string
    Object.keys(params).forEach(function(key) {
        if (params[key] !== null && params[key] !== undefined) {
            url.searchParams.set(key, params[key]);
        }
    });

    var promise = fetch(url.toString(), {
        headers: {
            'X-WP-Nonce': wtAnalytics.nonce
        }
    }).then(function(response) {
        if (!response.ok) {
            throw new Error('API error: ' + response.status + ' ' + response.statusText);
        }
        return response.json();
    });

    // If callbacks provided, wire them up; otherwise return the promise
    if (successCb) {
        promise.then(function(data) {
            try {
                successCb(data);
            } catch (renderErr) {
                console.error('WT.api callback error (' + endpoint + '):', renderErr);
                if (errCb) {
                    errCb(renderErr);
                }
            }
        }).catch(function(err) {
            console.error('WT.api fetch error (' + endpoint + '):', err);
            if (errCb) {
                errCb(err);
            }
        });
    }

    return promise;
};

/* ============================================================================
   Number Formatting Utilities
   ============================================================================ */

/**
 * Convert number to ordinal string (1 → "1st", 2 → "2nd", etc.)
 * @param {number} n - Integer to convert
 * @returns {string} Ordinal string
 */
WT.ordinal = function(n) {
    if (!Number.isInteger(n)) return String(n);

    const abs = Math.abs(n);
    const lastTwo = abs % 100;
    const lastOne = abs % 10;

    if (lastTwo > 10 && lastTwo < 20) {
        return n + 'th';
    }

    switch (lastOne) {
        case 1: return n + 'st';
        case 2: return n + 'nd';
        case 3: return n + 'rd';
        default: return n + 'th';
    }
};

/**
 * Format integer with thousands separator
 * @param {number} n - Number to format
 * @returns {string} Formatted number (e.g., "1,234,567")
 */
WT.fmtInt = function(n) {
    if (typeof n !== 'number') return String(n);
    return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};

/**
 * Format percentage with specified decimal places
 * @param {number} n - Decimal value (0-1 or 0-100)
 * @param {number} decimals - Number of decimal places (default 1)
 * @returns {string} Formatted percentage (e.g., "45.2%")
 */
WT.pct = function(n, decimals = 1) {
    if (typeof n !== 'number') return String(n);

    // If value > 1, assume it's already a percentage
    const value = n > 1 ? n : n * 100;
    return value.toFixed(decimals) + '%';
};

/* ============================================================================
   Tier Utilities
   ============================================================================ */

/**
 * Get tier label from percentile
 * @param {number} percentile - Percentile value (0-100)
 * @returns {string} Tier label
 */
WT.getTier = function(percentile) {
    if (typeof percentile !== 'number') return 'Unknown';

    if (percentile >= 75) return 'Very Strong';
    if (percentile >= 50) return 'Strong';
    if (percentile >= 25) return 'Moderate';
    return 'Foundational';
};

/**
 * Get CSS class for tier
 * @param {string} tier - Tier label
 * @returns {string} CSS class name
 */
WT.getTierClass = function(tier) {
    if (typeof tier !== 'string') return 'wt-tier-foundational';
    return 'wt-tier-' + tier.toLowerCase().replace(/\s+/g, '-');
};

/* ============================================================================
   Color Utilities
   ============================================================================ */

/**
 * Convert hex color to RGBA
 * @param {string} hex - Hex color (e.g., "#004C8C")
 * @param {number} alpha - Alpha value 0-1 (default 1)
 * @returns {string} RGBA string (e.g., "rgba(0, 76, 140, 0.5)")
 */
WT.hexToRgba = function(hex, alpha = 1) {
    // Remove hash if present
    hex = hex.replace(/^#/, '');

    // Handle short form (e.g., #fff)
    if (hex.length === 3) {
        hex = hex.split('').map(char => char + char).join('');
    }

    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${Math.min(1, Math.max(0, alpha))})`;
};

/* ============================================================================
   Plotly Chart Helpers
   ============================================================================ */

/**
 * Build Plotly base layout with consistent styling
 * @param {object} overrides - Layout overrides
 * @returns {object} Plotly layout object
 */
WT.baseLayout = function(overrides = {}) {
    return Object.assign({
        font: {
            family: 'Open Sans, Arial, sans-serif',
            color: '#111827'
        },
        paper_bgcolor: 'rgba(0,0,0,0)',
        plot_bgcolor: 'rgba(0,0,0,0)',
        margin: { l: 60, r: 30, t: 40, b: 60 },
        hoverlabel: {
            bgcolor: '#ffffff',
            bordercolor: '#d1d5db',
            font: { family: 'Open Sans', size: 13, color: '#111827' }
        },
    }, overrides);
};

/**
 * Build spider/radar chart trace
 * @param {object} dimValues - Dimension values keyed by dimension name
 * @param {string} name - Series name
 * @param {string} color - Hex color
 * @param {boolean} fill - Whether to fill the polygon (default true)
 * @returns {object} Plotly trace object
 */
WT.buildSpiderTrace = function(dimValues, name, color, fill = true) {
    const dims = wtAnalytics.dimensions;
    const labels = dims.map(d => wtAnalytics.dimShort[d] || d);
    const values = dims.map(d => dimValues[d] || 0);

    // Close the polygon by adding first value at end
    const closedValues = [...values, values[0]];
    const closedLabels = [...labels, labels[0]];

    // Build per-dimension marker colors from dimColors, falling back to trace color
    const dimColors = wtAnalytics.dimColors || {};
    const markerColors = dims.map(d => dimColors[d] || color);
    markerColors.push(markerColors[0]); // close polygon

    return {
        type: 'scatterpolar',
        r: closedValues,
        theta: closedLabels,
        fill: fill ? 'toself' : 'none',
        fillcolor: WT.hexToRgba(color, 0.15),
        line: {
            color: color,
            width: 2
        },
        marker: {
            size: 8,
            color: markerColors
        },
        name: name,
        hovertemplate: '%{theta}: %{r:.1f}<extra></extra>',
    };
};

/**
 * Build Plotly spider/radar chart layout
 * @param {string} title - Chart title
 * @param {number} maxVal - Maximum radial axis value (default 100)
 * @returns {object} Plotly layout object
 */
WT.spiderLayout = function(title = '', maxVal = 100) {
    return WT.baseLayout({
        polar: {
            radialaxis: {
                visible: true,
                range: [0, maxVal],
                tickfont: { size: 10 }
            },
            angularaxis: {
                tickfont: { size: 11 }
            }
        },
        showlegend: true,
        legend: {
            orientation: 'h',
            y: -0.15
        },
        title: {
            text: title,
            font: { size: 15 }
        },
        margin: { l: 60, r: 60, t: 50, b: 60 },
    });
};

/**
 * Build horizontal bar chart for dimensions
 * @param {object} dimValues - Dimension values keyed by dimension name
 * @param {object} colorMap - Color map keyed by dimension name
 * @param {string} title - Chart title
 * @returns {object} Object with data and layout properties
 */
WT.buildDimBars = function(dimValues, colorMap, title = '') {
    const dims = wtAnalytics.dimensions;

    // Sort dimensions by value descending
    const sorted = [...dims].sort((a, b) => {
        return (dimValues[b] || 0) - (dimValues[a] || 0);
    });

    const yLabels = sorted.map(d => wtAnalytics.dimShort[d] || d);
    const xValues = sorted.map(d => dimValues[d] || 0);
    const colors = sorted.map(d => colorMap[d] || '#004C8C');
    const textLabels = sorted.map(d => {
        const val = dimValues[d] || 0;
        return typeof val === 'number' ? val.toFixed(1) : String(val);
    });

    return {
        data: [{
            type: 'bar',
            orientation: 'h',
            y: yLabels,
            x: xValues,
            marker: { color: colors },
            text: textLabels,
            textposition: 'outside',
            hovertemplate: '%{y}: %{x:.3f}<extra></extra>',
        }],
        layout: WT.baseLayout({
            title: {
                text: title,
                font: { size: 15 }
            },
            yaxis: {
                automargin: true
            },
            xaxis: {
                title: 'Score'
            },
            height: 350,
        }),
    };
};

/* ============================================================================
   HTML Component Builders
   ============================================================================ */

/**
 * Create KPI card HTML
 * @param {string} label - KPI label
 * @param {string|number} value - KPI value
 * @returns {string} HTML string
 */
WT.kpiHtml = function(label, value, subtitle) {
    // If called with a single argument (tier badge), render as inline badge
    if (arguments.length === 1 || value === undefined) {
        var tierClass = WT.getTierClass(label);
        return '<span class="wt-tier-badge ' + tierClass + '">' + WT.escapeHtml(String(label)) + '</span>';
    }
    var html = '<div class="wt-kpi-card"><div class="wt-kpi-value">' + WT.escapeHtml(String(value)) + '</div><div class="wt-kpi-label">' + WT.escapeHtml(label) + '</div>';
    if (subtitle) {
        html += '<div class="wt-kpi-subtitle">' + WT.escapeHtml(subtitle) + '</div>';
    }
    html += '</div>';
    return html;
};

/**
 * Create empty state placeholder
 * @param {string} message - Message to display
 * @returns {string} HTML string
 */
WT.emptyState = function(targetOrMessage, message) {
    // If called with one arg, return HTML string
    if (arguments.length === 1 && typeof targetOrMessage === 'string' && targetOrMessage.charAt(0) !== '#') {
        return '<div class="wt-empty-state"><div class="wt-empty-state-icon"></div><p class="wt-empty-state-message">' + WT.escapeHtml(targetOrMessage) + '</p></div>';
    }

    // If called with two args, inject into target
    var html = '<div class="wt-empty-state"><div class="wt-empty-state-icon"></div><p class="wt-empty-state-message">' + WT.escapeHtml(message || targetOrMessage) + '</p></div>';

    if (targetOrMessage instanceof HTMLElement) {
        targetOrMessage.innerHTML = html;
    } else if (typeof targetOrMessage === 'string') {
        var id = targetOrMessage.charAt(0) === '#' ? targetOrMessage.slice(1) : targetOrMessage;
        var el = document.getElementById(id);
        if (el) {
            el.innerHTML = html;
        }
    }

    return html;
};

/**
 * Create loading spinner
 * @returns {string} HTML string
 */
WT.spinner = function() {
    return '<div class="wt-loading-spinner"></div>';
};

/**
 * Show loading spinner in container
 * @param {string} containerId - Element ID
 */
WT.showLoading = function(target) {
    // Handle boolean (global loading toggle — no-op)
    if (typeof target === 'boolean') return;

    // Handle DOM element directly
    if (target instanceof HTMLElement) {
        target.innerHTML = WT.spinner();
        return;
    }

    // Handle string: strip leading '#' for getElementById
    if (typeof target === 'string') {
        var id = target.charAt(0) === '#' ? target.slice(1) : target;
        var el = document.getElementById(id);
        if (el) {
            el.innerHTML = WT.spinner();
        }
    }
};

/* ============================================================================
   Table Builders
   ============================================================================ */

/**
 * Build HTML comparison table
 * @param {Array<object>} entities - Array of entity objects with name and dimension scores
 * @param {Array<string>} dimensions - Array of dimension names
 * @returns {string} HTML table string
 *
 * Entity format: { name: string, scores: { dimName: number }, percentiles: { dimName: number } }
 */
WT.buildComparisonTable = function(entities, dimensions) {
    if (!Array.isArray(entities) || !Array.isArray(dimensions)) {
        return '<p>Invalid data for comparison table</p>';
    }

    let html = '<table class="wt-comparison-table">';

    // Header
    html += '<thead><tr><th>Entity</th>';
    dimensions.forEach(dim => {
        html += `<th>${WT.escapeHtml(wtAnalytics.dimShort[dim] || dim)}</th>`;
    });
    html += '</tr></thead>';

    // Body
    html += '<tbody>';
    entities.forEach(entity => {
        html += '<tr>';
        html += `<td class="wt-entity-name">${WT.escapeHtml(entity.name)}</td>`;

        dimensions.forEach(dim => {
            const score = (entity.scores && entity.scores[dim]) || 0;
            const percentile = (entity.percentiles && entity.percentiles[dim]) || 0;
            const tier = WT.getTier(percentile);
            const tierClass = WT.getTierClass(tier);

            html += `<td><div class="wt-tier-badge ${tierClass}">${WT.escapeHtml(tier)}</div><div style="font-size: 11px; margin-top: 0.25rem; color: #6b7280;">${score.toFixed(1)}</div></td>`;
        });

        html += '</tr>';
    });
    html += '</tbody>';
    html += '</table>';

    return html;
};

/* ============================================================================
   Plotly Integration
   ============================================================================ */

/**
 * Initialize Plotly chart with responsive settings
 * @param {string} divId - Container element ID
 * @param {Array} data - Plotly data array
 * @param {object} layout - Plotly layout object
 * @returns {Promise} Plotly promise
 */
WT.plot = function(divId, data, layout) {
    // Resolve container element once
    var el = (typeof divId === 'string') ? document.getElementById(divId) : divId;

    if (typeof Plotly === 'undefined') {
        console.error('Plotly library not loaded');
        if (el) el.innerHTML = '<div class="wt-empty-state"><p class="wt-empty-state-message">Chart library not loaded</p></div>';
        return Promise.reject(new Error('Plotly not available'));
    }

    // CRITICAL: Remove any loading spinners before Plotly renders.
    // Plotly.newPlot does NOT remove non-Plotly children from the container,
    // so spinner divs persist alongside the chart unless we clear them first.
    if (el) {
        var spinners = el.querySelectorAll('.wt-loading-spinner');
        for (var i = 0; i < spinners.length; i++) {
            spinners[i].remove();
        }
    }

    try {
        return Plotly.newPlot(divId, data, layout, {
            responsive: true,
            displayModeBar: false
        }).catch(function(err) {
            console.error('Plotly render error:', err);
            if (el) el.innerHTML = '<div class="wt-empty-state"><p class="wt-empty-state-message">Chart render error</p></div>';
        });
    } catch (err) {
        console.error('Plotly sync error:', err);
        if (el) el.innerHTML = '<div class="wt-empty-state"><p class="wt-empty-state-message">Chart render error</p></div>';
        return Promise.reject(err);
    }
};

/* ============================================================================
   Form Helpers
   ============================================================================ */

/**
 * Populate select dropdown with options
 * @param {string} selectId - Select element ID
 * @param {Array<object>} options - Array of option objects
 * @param {string} valueKey - Key for option value
 * @param {string} labelKey - Key for option label
 * @param {string} placeholder - Placeholder text (optional)
 */
WT.populateSelect = function(selectId, options, valueKey, labelKey, placeholder) {
    if (placeholder === undefined) placeholder = 'Select...';
    if (!valueKey) valueKey = 'value';
    if (!labelKey) labelKey = 'label';

    // Accept either a string ID or a DOM element
    var select = (typeof selectId === 'string')
        ? document.getElementById(selectId.charAt(0) === '#' ? selectId.slice(1) : selectId)
        : selectId;

    if (!select) {
        console.warn('Select element not found:', selectId);
        return;
    }

    // Clear existing options
    select.innerHTML = '';

    // Add placeholder if provided
    if (placeholder) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        option.disabled = true;
        option.selected = true;
        select.appendChild(option);
    }

    // Add options
    if (Array.isArray(options)) {
        options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt[valueKey] || '';
            option.textContent = opt[labelKey] || '';
            select.appendChild(option);
        });
    }
};

/* ============================================================================
   Utility Helpers
   ============================================================================ */

/**
 * Escape HTML special characters
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
WT.escapeHtml = function(text) {
    if (typeof text !== 'string') return '';

    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
};

/**
 * Debounce function
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
WT.debounce = function(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

/**
 * Check if value is empty
 * @param {*} value - Value to check
 * @returns {boolean}
 */
WT.isEmpty = function(value) {
    return (
        value === null ||
        value === undefined ||
        value === '' ||
        (Array.isArray(value) && value.length === 0) ||
        (typeof value === 'object' && Object.keys(value).length === 0)
    );
};

/**
 * Deep clone an object
 * @param {*} obj - Object to clone
 * @returns {*} Cloned object
 */
WT.deepClone = function(obj) {
    if (obj === null || typeof obj !== 'object') return obj;
    if (obj instanceof Date) return new Date(obj.getTime());
    if (obj instanceof Array) return obj.map(item => WT.deepClone(item));
    if (obj instanceof Object) {
        const cloned = {};
        for (const key in obj) {
            if (obj.hasOwnProperty(key)) {
                cloned[key] = WT.deepClone(obj[key]);
            }
        }
        return cloned;
    }
};

/* ============================================================================
   DOM & Event Helpers
   ============================================================================ */

/**
 * Add event listener with automatic cleanup
 * @param {Element} element - DOM element
 * @param {string} event - Event type
 * @param {Function} handler - Event handler
 * @returns {Function} Function to remove listener
 */
WT.on = function(element, event, handler) {
    if (!element) return () => {};
    element.addEventListener(event, handler);
    return () => element.removeEventListener(event, handler);
};

/**
 * Query selector with null check
 * @param {string} selector - CSS selector
 * @param {Element} context - Context element (default: document)
 * @returns {Element|null}
 */
WT.q = function(selector, context = document) {
    return context.querySelector(selector);
};

/**
 * Query selector all
 * @param {string} selector - CSS selector
 * @param {Element} context - Context element (default: document)
 * @returns {NodeList}
 */
WT.qa = function(selector, context = document) {
    return context.querySelectorAll(selector);
};

/* ============================================================================
   Data Validation
   ============================================================================ */

/**
 * Validate email address
 * @param {string} email - Email address
 * @returns {boolean}
 */
WT.isValidEmail = function(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
};

/**
 * Validate URL
 * @param {string} url - URL
 * @returns {boolean}
 */
WT.isValidUrl = function(url) {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
};

/* ============================================================================
   Initialization
   ============================================================================ */

/**
 * Remove orphaned spinners from the page (safe version)
 * Only clears a spinner if it is the sole child of its container,
 * meaning no real content ever rendered there. Never wipes containers
 * that already hold rendered charts or other content.
 */
WT.clearOrphanedSpinners = function() {
    var spinners = document.querySelectorAll('.wt-loading-spinner');
    var cleared = 0;
    spinners.forEach(function(spinner) {
        var parent = spinner.parentElement;
        if (!parent) return;

        // Only replace if the spinner is the ONLY child (nothing else rendered)
        var siblings = parent.children;
        if (siblings.length === 1 && siblings[0] === spinner) {
            parent.innerHTML = '<div class="wt-empty-state"><p class="wt-empty-state-message">No data available</p></div>';
            cleared++;
        } else {
            // Spinner is alongside other content — just remove the spinner element
            spinner.remove();
            cleared++;
        }
    });
    if (cleared > 0) {
        console.warn('WT: Cleared ' + cleared + ' orphaned spinner(s)');
    }
};

// Safety net: clear any orphaned spinners 15 seconds after page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(WT.clearOrphanedSpinners, 15000);
});

// Verify wtAnalytics is available
if (typeof wtAnalytics === 'undefined') {
    console.warn('wtAnalytics object not found. Please ensure wp_localize_script is called correctly.');
}

// Export for use
if (typeof window !== 'undefined') {
    window.WT = WT;
}

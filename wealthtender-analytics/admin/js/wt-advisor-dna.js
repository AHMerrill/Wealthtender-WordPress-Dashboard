/**
 * Advisor DNA Page - Main JavaScript
 *
 * Complex analytics dashboard with macro and entity views
 * Displays advisor performance dimensions via spider/bar charts
 *
 * Dependencies: wt-common.js (WT global object)
 */

// ============================================================================
// STATE MANAGEMENT
// ============================================================================

const state = {
    view: 'macro',              // 'macro' or 'entity'
    chartType: 'spider',        // 'spider' or 'bars'
    method: 'mean',             // aggregation method
    minPeerReviews: 0,          // 0 = all, 20 = premier
    entityId: null,             // selected entity ID
    entities: null,             // cached entity list
    macroData: null,            // cached macro totals
    entityScores: null,         // cached enriched scores for entity
    entityPercentiles: null,    // cached percentile scores
    entityReviews: null,        // cached reviews
    breakpoints: null,          // cached method breakpoints
};

// ============================================================================
// INITIALIZATION
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    initializeAdvisorDNA();
});

/**
 * Initialize the Advisor DNA page
 * Load entities, set up event listeners, render initial macro view
 */
async function initializeAdvisorDNA() {
    try {
        // Load entity list from API
        const entityResponse = await WT.api('entities');
        // Handle both direct array and wrapped response
        state.entities = Array.isArray(entityResponse) ? entityResponse : (entityResponse.data || entityResponse.firms || []).concat(entityResponse.advisors || []);

        // Populate entity selector
        populateEntitySelect();

        // Set up event listeners
        setupEventListeners();

        // Load and render macro view
        await loadMacroView();

    } catch (error) {
        console.error('Failed to initialize Advisor DNA page:', error);
        showError('Failed to load Advisor DNA data. Please refresh the page.');
    }
}

// ============================================================================
// ENTITY DROPDOWN SETUP
// ============================================================================

/**
 * Populate the entity select dropdown with firms and advisors
 * Firms are listed first, then advisors, with group labels
 */
function populateEntitySelect() {
    if (!state.entities || state.entities.length === 0) {
        return;
    }

    const selectEl = document.getElementById('dna-entity-select');
    if (!selectEl) return;

    // Separate firms and advisors
    const firms = state.entities.filter(e => e.entity_type === 'firm');
    const advisors = state.entities.filter(e => e.entity_type === 'advisor');

    // Build options
    const options = [];

    // Add firms
    if (firms.length > 0) {
        options.push({
            text: '─ Firms ─',
            disabled: true,
            group: true,
        });
        firms.forEach(firm => {
            options.push({
                value: firm.entity_id,
                text: firm.entity_name,
                type: 'firm',
            });
        });
    }

    // Add advisors
    if (advisors.length > 0) {
        options.push({
            text: '─ Advisors ─',
            disabled: true,
            group: true,
        });
        advisors.forEach(advisor => {
            options.push({
                value: advisor.entity_id,
                text: advisor.entity_name,
                type: 'advisor',
            });
        });
    }

    // Use WT helper with custom rendering for groups
    if (typeof WT.populateSelect === 'function') {
        WT.populateSelect('dna-entity-select', options, 'value', 'text', 'Select an entity...');
    } else {
        // Fallback manual population
        selectEl.innerHTML = '<option value="">Select an entity...</option>';
        options.forEach(opt => {
            const optEl = document.createElement('option');
            optEl.value = opt.value || '';
            optEl.textContent = opt.text;
            if (opt.disabled) {
                optEl.disabled = true;
                optEl.style.fontWeight = 'bold';
            }
            selectEl.appendChild(optEl);
        });
    }
}

// ============================================================================
// EVENT LISTENERS
// ============================================================================

/**
 * Set up all event listeners for the page
 */
function setupEventListeners() {
    // View toggle buttons
    document.querySelectorAll('[data-view]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const newView = this.dataset.view;
            switchView(newView);
        });
    });

    // Method dropdown
    const methodSelect = document.getElementById('dna-method-select');
    if (methodSelect) {
        methodSelect.addEventListener('change', function() {
            state.method = this.value;
            handleMethodChange();
        });
    }

    // Pool dropdown
    const poolSelect = document.getElementById('dna-pool-select');
    if (poolSelect) {
        poolSelect.addEventListener('change', function() {
            state.minPeerReviews = parseInt(this.value, 10);
            handlePoolChange();
        });
    }

    // Chart type toggle
    document.querySelectorAll('[data-chart-type]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const chartType = this.dataset.chartType;
            switchChartType(chartType);
        });
    });

    // Entity select
    const entitySelect = document.getElementById('dna-entity-select');
    if (entitySelect) {
        entitySelect.addEventListener('change', function() {
            const entityId = this.value;
            if (entityId) {
                selectEntity(entityId);
            }
        });
    }

    // Dimension card clicks (filter by dimension in entity view)
    document.querySelectorAll('.wt-dimension-card').forEach(card => {
        card.addEventListener('click', function() {
            const dimKey = this.dataset.dimension;
            if (dimKey && state.view === 'entity' && state.entityReviews) {
                scrollToReviewsFilteredByDimension(dimKey);
            }
        });
        card.style.cursor = 'pointer';
    });
}

// ============================================================================
// VIEW SWITCHING
// ============================================================================

/**
 * Switch between macro and entity views
 */
function switchView(newView) {
    const entitySearchGroup = document.querySelector('.wt-entity-search-group');

    if (newView === 'macro') {
        state.view = 'macro';
        state.entityId = null;
        document.getElementById('dna-macro-section').style.display = 'block';
        document.getElementById('dna-entity-section').style.display = 'none';
        if (entitySearchGroup) entitySearchGroup.style.display = 'none';
        // Reset entity select
        const entitySelect = document.getElementById('dna-entity-select');
        if (entitySelect) entitySelect.value = '';
        // Re-render macro view in case data changed
        renderMacroView();
    } else if (newView === 'entity') {
        state.view = 'entity';
        document.getElementById('dna-macro-section').style.display = 'none';
        document.getElementById('dna-entity-section').style.display = 'block';
        if (entitySearchGroup) entitySearchGroup.style.display = '';
        // If no entity selected, prompt user
        const entitySelect = document.getElementById('dna-entity-select');
        if (!state.entityId && entitySelect) {
            entitySelect.focus();
        }
    }

    // Update button active states
    updateViewButtons();
}

/**
 * Update active state on view toggle buttons
 */
function updateViewButtons() {
    document.querySelectorAll('[data-view]').forEach(btn => {
        if (btn.dataset.view === state.view) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

// ============================================================================
// CHART TYPE SWITCHING
// ============================================================================

/**
 * Switch between spider and bar charts
 */
function switchChartType(chartType) {
    state.chartType = chartType;

    // Update button active states
    document.querySelectorAll('[data-chart-type]').forEach(btn => {
        if (btn.dataset.chartType === chartType) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Re-render current view's chart
    if (state.view === 'macro') {
        renderMacroChart();
    } else if (state.view === 'entity') {
        renderEntityChart();
    }
}

// ============================================================================
// MACRO VIEW
// ============================================================================

/**
 * Load macro totals from API and render macro view
 */
async function loadMacroView() {
    try {
        const containerId = 'dna-macro-chart';
        WT.showLoading(containerId);

        const response = await WT.api('advisor-dna/macro-totals', {
            min_peer_reviews: state.minPeerReviews,
        });

        state.macroData = response.data || response || {};

        renderMacroView();
    } catch (error) {
        console.error('Failed to load macro data:', error);
        showError('Failed to load macro data.', 'dna-macro-chart');
        const kpiContainer = document.getElementById('dna-macro-kpis');
        if (kpiContainer) kpiContainer.innerHTML = '';
    }
}

/**
 * Render macro view (KPIs and chart)
 */
function renderMacroView() {
    if (!state.macroData) return;

    // Render KPIs
    renderMacroKPIs();

    // Render chart
    renderMacroChart();
}

/**
 * Render macro KPI cards
 */
function renderMacroKPIs() {
    const container = document.getElementById('dna-macro-kpis');
    if (!container) return;

    const data = state.macroData;
    const totalReviews = data.review_count || 0;
    const numDimensions = wtAnalytics.dimensions.length;

    let html = '';
    html += WT.kpiHtml('Scored Reviews', WT.fmtInt(totalReviews), 'Only reviews with NLP dimension scores');
    html += WT.kpiHtml('Dimensions', WT.fmtInt(numDimensions));

    container.innerHTML = html;
}

/**
 * Render macro chart (spider or bars)
 */
function renderMacroChart() {
    const containerId = 'dna-macro-chart';
    const data = state.macroData;

    if (!data || !data.dimensions) {
        showError('No dimension data available.', containerId);
        return;
    }

    // Build dimension values object
    const dimValues = {};
    wtAnalytics.dimensions.forEach(dim => {
        dimValues[dim] = (data.dimensions[dim] && data.dimensions[dim].mean) || 0;
    });

    if (state.chartType === 'spider') {
        renderMacroSpider(dimValues, containerId);
    } else {
        renderMacroBars(dimValues, containerId);
    }
}

/**
 * Render macro spider chart
 */
function renderMacroSpider(dimValues, containerId) {
    // Build trace with macro data
    const trace = WT.buildSpiderTrace(dimValues, 'Advisor DNA Profile', '#004C8C', true);

    // Calculate max scale
    const maxVal = Math.max(...Object.values(dimValues));
    const scale = maxVal * 1.2;

    // Create layout
    const layout = WT.spiderLayout('Advisor DNA Profile — Mean Scores', scale);

    // Plot
    WT.plot(containerId, [trace], layout);
}

/**
 * Render macro bar chart
 */
function renderMacroBars(dimValues, containerId) {
    // Create bar chart with dimension colors
    const result = WT.buildDimBars(dimValues, wtAnalytics.dimColors, 'Mean Scores');

    WT.plot(containerId, result.data, result.layout);
}

/**
 * Handle method dropdown change in macro view
 */
async function handleMethodChange() {
    if (state.view === 'macro') {
        // Reload macro data
        await loadMacroView();
    } else if (state.view === 'entity' && state.entityId) {
        // Reload entity data
        await loadEntityData(state.entityId);
    }
}

/**
 * Handle pool dropdown change
 */
async function handlePoolChange() {
    if (state.view === 'macro') {
        // Reload macro data with new pool filter
        await loadMacroView();
    } else if (state.view === 'entity' && state.entityId) {
        // Reload entity data with new pool filter
        await loadEntityData(state.entityId);
    }
}

// ============================================================================
// ENTITY VIEW
// ============================================================================

/**
 * Select an entity and load its data
 */
async function selectEntity(entityId) {
    state.entityId = entityId;
    state.view = 'entity';

    // Switch view
    document.getElementById('dna-macro-section').style.display = 'none';
    document.getElementById('dna-entity-section').style.display = 'block';
    updateViewButtons();

    // Load entity data
    await loadEntityData(entityId);
}

/**
 * Load entity data from API
 */
async function loadEntityData(entityId) {
    try {
        const infoContainer = document.getElementById('dna-entity-info');
        const chartContainer = document.getElementById('dna-entity-chart');
        const scoresContainer = document.getElementById('dna-entity-scores');
        const reviewsContainer = document.getElementById('dna-entity-reviews');

        // Show loading states
        if (chartContainer) WT.showLoading('dna-entity-chart');
        if (scoresContainer) scoresContainer.innerHTML = '<div class="wt-loading">Loading...</div>';
        if (reviewsContainer) reviewsContainer.innerHTML = '<div class="wt-loading">Loading...</div>';

        // Fetch all required data in parallel
        const [enrichedRes, percentilesRes, breakpointsRes, reviewsRes] = await Promise.all([
            WT.api('advisor-dna/advisor-scores', {
                entity_id: entityId,
                method: state.method,
            }),
            WT.api('advisor-dna/percentile-scores', {
                entity_id: entityId,
                method: state.method,
                min_peer_reviews: state.minPeerReviews,
            }),
            WT.api('advisor-dna/method-breakpoints', {
                method: state.method,
            }),
            WT.api('advisor-dna/entity-reviews', {
                entity_id: entityId,
            }),
        ]);

        // Cache results (handle both direct response and wrapped in .data)
        state.entityScores = enrichedRes.data || enrichedRes || {};
        state.entityPercentiles = percentilesRes.data || percentilesRes || {};
        state.breakpoints = breakpointsRes.data || breakpointsRes || {};
        state.entityReviews = (reviewsRes.data || reviewsRes || []).length ? (reviewsRes.data || reviewsRes || []) : [];

        // Render entity view
        renderEntityView();

    } catch (error) {
        console.error('Failed to load entity data:', error);
        showError('Failed to load entity data.', 'dna-entity-chart');
        const scoresContainer = document.getElementById('dna-entity-scores');
        if (scoresContainer) scoresContainer.innerHTML = '';
        const reviewsContainer = document.getElementById('dna-entity-reviews');
        if (reviewsContainer) reviewsContainer.innerHTML = '';
    }
}

/**
 * Render entity view (info, chart, scores, reviews)
 */
function renderEntityView() {
    if (!state.entityScores) return;

    // Render entity info
    renderEntityInfo();

    // Render chart
    renderEntityChart();

    // Render scores table
    renderEntityScoresTable();

    // Render reviews
    renderEntityReviews();
}

/**
 * Render entity info (name, review count, confidence badge)
 */
function renderEntityInfo() {
    const container = document.getElementById('dna-entity-info');
    if (!container) return;

    // Find entity from state.entities
    const entity = state.entities.find(e => e.entity_id === state.entityId);
    if (!entity) return;

    const reviewCount = state.entityReviews.length;
    const confidence = getConfidenceBadge(reviewCount);

    let html = `
        <div class="wt-entity-header">
            <h2>${WT.escapeHtml(entity.entity_name)}</h2>
            <div class="wt-entity-meta">
                <span class="wt-review-count">${reviewCount} scored review${reviewCount !== 1 ? 's' : ''}</span>
                <span class="wt-confidence-badge wt-${confidence.class}">${confidence.label}</span>
            </div>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Get confidence badge info based on review count
 */
function getConfidenceBadge(reviewCount) {
    if (reviewCount < 10) {
        return {
            label: 'Directional Insights',
            class: 'directional',
        };
    } else if (reviewCount < 20) {
        return {
            label: 'Growing Dataset',
            class: 'growing',
        };
    } else {
        return {
            label: 'Robust Data',
            class: 'robust',
        };
    }
}

/**
 * Render entity chart (spider or bars)
 */
function renderEntityChart() {
    const containerId = 'dna-entity-chart';

    if (!state.entityPercentiles) {
        showError('No percentile data available.', containerId);
        return;
    }

    // Find entity name
    const entity = state.entities.find(e => e.entity_id === state.entityId);
    const entityName = entity ? entity.entity_name : 'Entity';

    // Build dimension values from percentiles
    const dimValues = {};
    wtAnalytics.dimensions.forEach(dim => {
        dimValues[dim] = state.entityPercentiles[dim] || 0;
    });

    if (state.chartType === 'spider') {
        renderEntitySpider(dimValues, entityName, containerId);
    } else {
        renderEntityBars(dimValues, entityName, containerId);
    }
}

/**
 * Render entity spider chart with percentile values
 */
function renderEntitySpider(dimValues, entityName, containerId) {
    const trace = WT.buildSpiderTrace(dimValues, entityName, '#004C8C', true);

    const layout = WT.spiderLayout(entityName + ' — Percentile Profile', 100);

    WT.plot(containerId, [trace], layout).then(function() {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.on('plotly_click', function(data) {
            if (data.points && data.points.length > 0) {
                var point = data.points[0];
                // theta gives us the short label; find the matching dimension key
                var clickedLabel = point.theta;
                var clickedDim = null;
                wtAnalytics.dimensions.forEach(function(dim) {
                    if (wtAnalytics.dimShort[dim] === clickedLabel) {
                        clickedDim = dim;
                    }
                });
                if (clickedDim) {
                    scrollToReviewsFilteredByDimension(clickedDim);
                }
            }
        });
    });
}

/**
 * Render entity bar chart with percentile values
 */
function renderEntityBars(dimValues, entityName, containerId) {
    const result = WT.buildDimBars(dimValues, wtAnalytics.dimColors, 'Percentile Ranks');

    WT.plot(containerId, result.data, result.layout).then(function() {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.on('plotly_click', function(data) {
            if (data.points && data.points.length > 0) {
                var point = data.points[0];
                // y-axis label is the dimension short label
                var clickedLabel = point.y || point.label;
                var clickedDim = null;
                wtAnalytics.dimensions.forEach(function(dim) {
                    var dimLabel = wtAnalytics.dimShort[dim] || wtAnalytics.dimLabels[dim] || dim;
                    if (dimLabel === clickedLabel) {
                        clickedDim = dim;
                    }
                });
                if (clickedDim) {
                    scrollToReviewsFilteredByDimension(clickedDim);
                }
            }
        });
    });
}

/**
 * Render entity scores table
 */
function renderEntityScoresTable() {
    const container = document.getElementById('dna-entity-scores');
    if (!container) return;

    if (!state.entityScores || !state.entityPercentiles) {
        showError('No scores available.', container.id);
        return;
    }

    let html = '<table class="wt-scores-table">';
    html += '<thead>';
    html += '<tr>';
    html += '<th>Dimension</th>';
    html += '<th>Score</th>';
    html += '<th>Percentile</th>';
    html += '<th>Tier</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';

    // Add rows for each dimension
    wtAnalytics.dimensions.forEach(dim => {
        const dimData = state.entityScores[dim];
        const percentile = state.entityPercentiles[dim];

        if (dimData && typeof percentile !== 'undefined') {
            // Handle both nested object and direct value
            const score = dimData.raw !== undefined ? dimData.raw : dimData;
            const tier = WT.getTier(percentile);
            const tierClass = WT.getTierClass(tier);
            const label = wtAnalytics.dimLabels[dim] || dim;

            html += '<tr>';
            html += `<td>${WT.escapeHtml(label)}</td>`;
            html += `<td>${score.toFixed(2)}</td>`;
            html += `<td>${percentile.toFixed(1)}%</td>`;
            html += `<td class="wt-tier-badge ${tierClass}">${WT.escapeHtml(tier)}</td>`;
            html += '</tr>';
        }
    });

    // Add composite score row if available
    if (state.entityScores.composite !== undefined && state.entityPercentiles.composite !== undefined) {
        const compositeData = state.entityScores.composite;
        const compositeScore = compositeData.raw !== undefined ? compositeData.raw : compositeData;
        const compositePercentile = state.entityPercentiles.composite;
        const compositeTier = WT.getTier(compositePercentile);
        const compositeTierClass = WT.getTierClass(compositeTier);

        html += '<tr class="wt-composite-row">';
        html += '<td><strong>Composite Score</strong></td>';
        html += `<td><strong>${compositeScore.toFixed(2)}</strong></td>`;
        html += `<td><strong>${compositePercentile.toFixed(1)}%</strong></td>`;
        html += `<td class="wt-tier-badge ${compositeTierClass}"><strong>${WT.escapeHtml(compositeTier)}</strong></td>`;
        html += '</tr>';
    }

    html += '</tbody>';
    html += '</table>';

    container.innerHTML = html;
}

/**
 * Render entity reviews section
 */
function renderEntityReviews() {
    const container = document.getElementById('dna-entity-reviews');
    if (!container) return;

    if (!state.entityReviews || state.entityReviews.length === 0) {
        container.innerHTML = WT.emptyState('No reviews available for this entity.');
        return;
    }

    // Show top 3 reviews
    const topReviews = state.entityReviews.slice(0, 3);

    let html = '<div class="wt-reviews-list">';

    topReviews.forEach(review => {
        // Use review_text_raw from the CSV data
        const rawText = review.review_text_raw || review.text || '';
        const truncatedText = rawText.length > 250
            ? rawText.substring(0, 250) + '...'
            : rawText;

        const reviewIdx = review.review_idx || '';

        html += '<div class="wt-review-card">';
        html += `<div class="wt-review-header">`;
        html += `<strong>Review #${reviewIdx}</strong>`;
        html += `</div>`;
        html += `<p class="wt-review-text">${WT.escapeHtml(truncatedText)}</p>`;

        // Show per-dimension similarity scores
        html += '<div class="wt-review-dims">';
        wtAnalytics.dimensions.forEach(dim => {
            const fieldName = DIM_FIELD_MAP[dim];
            const score = fieldName ? parseFloat(review[fieldName]) : NaN;
            if (!isNaN(score)) {
                const label = wtAnalytics.dimShort[dim] || dim;
                html += `<span class="wt-dim-score">${label}: ${score.toFixed(2)}</span>`;
            }
        });
        html += '</div>';

        html += '</div>';
    });

    html += '</div>';

    container.innerHTML = html;
}

/**
 * Build star rating HTML
 */
function buildStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalf = rating % 1 >= 0.5;
    let html = '';

    for (let i = 0; i < 5; i++) {
        if (i < fullStars) {
            html += '<span class="wt-star wt-star-full">★</span>';
        } else if (i === fullStars && hasHalf) {
            html += '<span class="wt-star wt-star-half">★</span>';
        } else {
            html += '<span class="wt-star wt-star-empty">☆</span>';
        }
    }

    return html;
}

/**
 * Map full dimension keys to CSV sim_ field names (module-level)
 */
const DIM_FIELD_MAP = {
    'trust_integrity': 'sim_trust_integrity',
    'listening_personalization': 'sim_listening_personalization',
    'communication_clarity': 'sim_communication_clarity',
    'responsiveness_availability': 'sim_responsiveness_availability',
    'life_event_support': 'sim_life_event_support',
    'investment_expertise': 'sim_investment_expertise',
};

/**
 * Map short dimension keys (used in PHP cards/dropdowns) to full dimension keys
 */
const SHORT_TO_FULL_DIM = {
    'trust_integrity': 'trust_integrity',
    'empathy': 'listening_personalization',
    'communication': 'communication_clarity',
    'responsiveness': 'responsiveness_availability',
    'life_events': 'life_event_support',
    'investment_expertise': 'investment_expertise',
};

/**
 * Scroll to reviews filtered/sorted by dimension
 * Sorts reviews by the chosen dimension's similarity score (highest first)
 * and re-renders the top 3
 */
function scrollToReviewsFilteredByDimension(dimKey) {
    // Convert short key to full key if needed
    const fullDimKey = SHORT_TO_FULL_DIM[dimKey] || dimKey;
    const simField = DIM_FIELD_MAP[fullDimKey];

    if (!simField || !state.entityReviews || state.entityReviews.length === 0) {
        const reviewsSection = document.getElementById('dna-entity-reviews');
        if (reviewsSection) {
            reviewsSection.scrollIntoView({ behavior: 'smooth' });
        }
        return;
    }

    // Sort reviews by this dimension's similarity score (descending)
    const sorted = [...state.entityReviews].sort(function(a, b) {
        const scoreA = parseFloat(a[simField]) || 0;
        const scoreB = parseFloat(b[simField]) || 0;
        return scoreB - scoreA;
    });

    // Render the top 3 reviews for this dimension
    const container = document.getElementById('dna-entity-reviews');
    if (!container) return;

    const topReviews = sorted.slice(0, 3);
    const dimLabel = wtAnalytics.dimLabels[fullDimKey] || fullDimKey;

    let html = '<div class="wt-reviews-filter-label">Top reviews for <strong>' + WT.escapeHtml(dimLabel) + '</strong> <a href="#" class="wt-reviews-clear-filter">show all</a></div>';
    html += '<div class="wt-reviews-list">';

    topReviews.forEach(function(review) {
        var rawText = review.review_text_raw || review.text || '';
        var truncatedText = rawText.length > 250
            ? rawText.substring(0, 250) + '...'
            : rawText;

        var reviewIdx = review.review_idx || '';

        html += '<div class="wt-review-card">';
        html += '<div class="wt-review-header">';
        html += '<strong>Review #' + reviewIdx + '</strong>';

        // Highlight the focused dimension score
        var focusScore = parseFloat(review[simField]);
        if (!isNaN(focusScore)) {
            html += '<span class="wt-dim-score wt-dim-score-highlight">' + WT.escapeHtml(dimLabel) + ': ' + focusScore.toFixed(2) + '</span>';
        }

        html += '</div>';
        html += '<p class="wt-review-text">' + WT.escapeHtml(truncatedText) + '</p>';

        // Show per-dimension similarity scores
        html += '<div class="wt-review-dims">';
        wtAnalytics.dimensions.forEach(function(dim) {
            var fieldName = DIM_FIELD_MAP[dim];
            var score = fieldName ? parseFloat(review[fieldName]) : NaN;
            if (!isNaN(score)) {
                var label = wtAnalytics.dimShort[dim] || dim;
                var cls = (dim === fullDimKey) ? 'wt-dim-score wt-dim-score-highlight' : 'wt-dim-score';
                html += '<span class="' + cls + '">' + label + ': ' + score.toFixed(2) + '</span>';
            }
        });
        html += '</div>';

        html += '</div>';
    });

    html += '</div>';

    container.innerHTML = html;

    // Bind clear filter link
    var clearLink = container.querySelector('.wt-reviews-clear-filter');
    if (clearLink) {
        clearLink.addEventListener('click', function(e) {
            e.preventDefault();
            renderEntityReviews();
        });
    }

    // Scroll to reviews
    container.scrollIntoView({ behavior: 'smooth' });
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Show error message in a container
 */
function showError(message, containerId = null) {
    if (containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `<div class="wt-error-message">${message}</div>`;
        }
    } else {
        console.error(message);
        // Could also show a toast notification
    }
}

/**
 * Update active state on method/pool dropdowns
 */
function updateDropdownStates() {
    const methodSelect = document.getElementById('dna-method-select');
    const poolSelect = document.getElementById('dna-pool-select');

    if (methodSelect) {
        methodSelect.value = state.method;
    }

    if (poolSelect) {
        poolSelect.value = state.minPeerReviews;
    }
}

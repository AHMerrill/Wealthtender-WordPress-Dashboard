/**
 * Wealthtender Analytics - Comparisons Page
 * Handles head-to-head entity comparisons
 */

(function($) {
    'use strict';

    // State management
    const state = {
        // Head-to-head
        h2hEntityType: null,
        h2hMethod: 'mean',
        h2hMinPeerReviews: 0,
        entityA: null,
        entityB: null,
        entities: null,
    };

    /**
     * Initialize the comparisons page
     */
    function init() {
        loadInitialData();
        setupEventListeners();
    }

    /**
     * Load initial data: entities and partner groups
     */
    function loadInitialData() {
        // Load entities
        WT.api('entities')
            .then(function(response) {
                // Flatten firms and advisors into a single array
                state.entities = (response.firms || []).concat(response.advisors || []);
                populateEntityDropdowns();
            })
            .catch(function(error) {
                console.error('Failed to load entities:', error);
            });

    }

    /**
     * Populate entity dropdowns (A and B)
     */
    function populateEntityDropdowns() {
        if (!state.entities) {
            return;
        }

        // Filter entities based on selected entity type
        let filtered = state.entities;
        if (state.h2hEntityType) {
            filtered = state.entities.filter(e => e.entity_type === state.h2hEntityType);
        }

        // Build options
        const options = filtered.map(entity =>
            `<option value="${entity.entity_id}">${entity.entity_name}</option>`
        ).join('');

        $('#comp-entity-a').html('<option value="">Select Entity A</option>' + options);
        $('#comp-entity-b').html('<option value="">Select Entity B</option>' + options);
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Entity type filter
        $('#comp-h2h-entity-type').on('change', function() {
            state.h2hEntityType = $(this).val() || null;
            populateEntityDropdowns();
            clearH2HComparison();
        });

        // H2H method change
        $('#comp-h2h-method').on('change', function() {
            state.h2hMethod = $(this).val();
            if (state.entityA && state.entityB) {
                fetchH2HComparison();
            }
        });

        // H2H pool change
        $('#comp-h2h-pool').on('change', function() {
            state.h2hMinPeerReviews = parseInt($(this).val(), 10) || 0;
            if (state.entityA && state.entityB) {
                fetchH2HComparison();
            }
        });

        // Entity A selection
        $('#comp-entity-a').on('change', function() {
            state.entityA = $(this).val() || null;
            if (state.entityA && state.entityB) {
                fetchH2HComparison();
            } else {
                clearH2HComparison();
            }
        });

        // Entity B selection
        $('#comp-entity-b').on('change', function() {
            state.entityB = $(this).val() || null;
            if (state.entityA && state.entityB) {
                fetchH2HComparison();
            } else {
                clearH2HComparison();
            }
        });

    }

    /**
     * Clear H2H comparison displays
     */
    function clearH2HComparison() {
        $('#comp-h2h-spider').empty();
        $('#comp-h2h-table').empty();
    }

    /**
     * Fetch head-to-head comparison data
     */
    function fetchH2HComparison() {
        WT.showLoading('#comp-h2h-spider');
        WT.showLoading('#comp-h2h-table');

        const params = {
            entity_a: state.entityA,
            entity_b: state.entityB,
            method: state.h2hMethod,
        };

        if (state.h2hMinPeerReviews > 0) {
            params.min_peer_reviews = state.h2hMinPeerReviews;
        }

        WT.api('comparisons/head-to-head', params)
            .then(function(data) {
                renderH2HComparison(data);
            })
            .catch(function(error) {
                console.error('Failed to fetch H2H comparison:', error);
                WT.emptyState('#comp-h2h-spider', 'Failed to load comparison data');
                $('#comp-h2h-table').html('');
            });
    }

    /**
     * Render head-to-head comparison (spider chart and table)
     */
    function renderH2HComparison(data) {
        if (!Array.isArray(data) || data.length < 2) {
            WT.emptyState('#comp-h2h-spider', 'Invalid comparison data');
            $('#comp-h2h-table').html('');
            return;
        }

        const entityA = data[0];
        const entityB = data[1];

        // Render spider chart
        renderH2HSpider(entityA, entityB);

        // Render comparison table
        renderH2HTable(entityA, entityB);
    }

    /**
     * Render H2H spider chart
     */
    function renderH2HSpider(entityA, entityB) {
        const traces = [];

        // Entity A trace
        if (entityA && entityA.enriched) {
            const aDimValues = {};
            wtAnalytics.dimensions.forEach(d => { aDimValues[d] = entityA.enriched[d]?.percentile || 0; });
            const aTrace = WT.buildSpiderTrace(aDimValues, entityA.entity_name, wtAnalytics.dataVizPalette[0]);
            traces.push(aTrace);
        }

        // Entity B trace
        if (entityB && entityB.enriched) {
            const bDimValues = {};
            wtAnalytics.dimensions.forEach(d => { bDimValues[d] = entityB.enriched[d]?.percentile || 0; });
            const bTrace = WT.buildSpiderTrace(bDimValues, entityB.entity_name, wtAnalytics.dataVizPalette[1]);
            traces.push(bTrace);
        }

        WT.plot('comp-h2h-spider', traces, WT.spiderLayout('Head-to-Head Comparison', 100));
    }

    /**
     * Render H2H comparison table
     */
    function renderH2HTable(entityA, entityB) {
        if (!entityA.enriched || !entityB.enriched) {
            $('#comp-h2h-table').html('');
            return;
        }

        let html = '<table class="wt-comparison-table"><thead><tr>';
        html += '<th>Dimension</th>';
        html += `<th>${entityA.entity_name}<br><small>Percentile</small></th>`;
        html += `<th>${entityB.entity_name}<br><small>Percentile</small></th>`;
        html += '<th>Difference</th>';
        html += '</tr></thead><tbody>';

        // Dimension rows
        wtAnalytics.dimensions.forEach(dim => {
            const aPercentile = entityA.enriched[dim]?.percentile || 0;
            const bPercentile = entityB.enriched[dim]?.percentile || 0;
            const diff = aPercentile - bPercentile;

            const aTier = entityA.enriched[dim]?.tier;
            const bTier = entityB.enriched[dim]?.tier;

            const aHtml = `${aPercentile.toFixed(1)} ${aTier ? WT.kpiHtml(aTier) : ''}`;
            const bHtml = `${bPercentile.toFixed(1)} ${bTier ? WT.kpiHtml(bTier) : ''}`;

            let diffColor = '#999';
            if (diff > 0) {
                diffColor = '#28a745';
            } else if (diff < 0) {
                diffColor = '#dc3545';
            }

            const diffHtml = `<span style="color: ${diffColor};">${diff.toFixed(1)}</span>`;

            html += `<tr>`;
            html += `<td><strong>${wtAnalytics.dimLabels[dim]}</strong></td>`;
            html += `<td>${aHtml}</td>`;
            html += `<td>${bHtml}</td>`;
            html += `<td>${diffHtml}</td>`;
            html += `</tr>`;
        });

        // Composite row (bolded)
        const aComposite = entityA.enriched.composite?.percentile || 0;
        const bComposite = entityB.enriched.composite?.percentile || 0;
        const compositeDiff = aComposite - bComposite;

        const aTier = entityA.enriched.composite?.tier;
        const bTier = entityB.enriched.composite?.tier;

        const aHtml = `${aComposite.toFixed(1)} ${aTier ? WT.kpiHtml(aTier) : ''}`;
        const bHtml = `${bComposite.toFixed(1)} ${bTier ? WT.kpiHtml(bTier) : ''}`;

        let compositeColor = '#999';
        if (compositeDiff > 0) {
            compositeColor = '#28a745';
        } else if (compositeDiff < 0) {
            compositeColor = '#dc3545';
        }

        const compositeDiffHtml = `<span style="color: ${compositeColor};">${compositeDiff.toFixed(1)}</span>`;

        html += `<tr style="font-weight: bold; border-top: 2px solid #ddd;">`;
        html += `<td>Composite Score</td>`;
        html += `<td>${aHtml}</td>`;
        html += `<td>${bHtml}</td>`;
        html += `<td>${compositeDiffHtml}</td>`;
        html += `</tr>`;

        html += '</tbody></table>';

        $('#comp-h2h-table').html(html);
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        init();
    });

})(jQuery);

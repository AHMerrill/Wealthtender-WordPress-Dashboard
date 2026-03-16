/**
 * Wealthtender Analytics: Benchmarks Module
 * Displays benchmark comparisons, pool statistics, and score distributions
 */

(function($) {
    'use strict';

    // State management
    const state = {
        method: 'mean',
        entityType: null,
        minPeerReviews: 0,
        entityId: null,
        poolStats: null,
        distributions: null,
        entities: null,
    };

    /**
     * Initialize the benchmarks module
     */
    function init() {
        bindEvents();
        loadEntities();
        loadPoolStats();
        loadDistributions();
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        $('#bench-method-select').on('change', function() {
            state.method = $(this).val();
            loadDistributions();
            if (state.entityId) {
                loadEntityScores();
            }
        });

        $('#bench-entity-type').on('change', function() {
            state.entityType = $(this).val() || null;
            $('#bench-entity-select').val('');
            state.entityId = null;
            $('#bench-comparison-section').hide();
            loadDistributions();
            updateEntityDropdown();
        });

        $('#bench-pool-select').on('change', function() {
            state.minPeerReviews = parseInt($(this).val());
            loadDistributions();
            if (state.entityId) {
                loadEntityScores();
            }
        });

        $('#bench-entity-select').on('change', function() {
            state.entityId = $(this).val();
            if (state.entityId) {
                loadEntityScores();
            } else {
                $('#bench-comparison-section').hide();
                redrawHistograms();
            }
        });
    }

    /**
     * Load entities from API
     */
    function loadEntities() {
        WT.api('entities', function(data) {
            // Flatten firms and advisors into a single array
            state.entities = (data.firms || []).concat(data.advisors || []);
            updateEntityDropdown();
        }, function(error) {
            console.error('Error loading entities:', error);
        });
    }

    /**
     * Update entity dropdown options based on entity type filter
     */
    function updateEntityDropdown() {
        const $select = $('#bench-entity-select');
        const entityType = state.entityType;

        $select.empty().append('<option value="">Select an entity...</option>');

        if (!state.entities) return;

        const filtered = state.entities.filter(entity => {
            return !entityType || entity.entity_type === entityType;
        });

        filtered.forEach(entity => {
            $select.append(`<option value="${entity.entity_id}">${entity.entity_name}</option>`);
        });
    }

    /**
     * Load pool statistics
     */
    function loadPoolStats() {
        WT.showLoading('#bench-pool-kpis');
        WT.api('benchmarks/pool-stats', { min_peer_reviews: 20 }, function(data) {
            state.poolStats = data || {};
            renderPoolKpis();
        }, function(error) {
            console.error('Error loading pool stats:', error);
            WT.emptyState('#bench-pool-kpis', 'Failed to load pool stats');
        });
    }

    /**
     * Render pool KPIs side by side
     */
    function renderPoolKpis() {
        const $kpiContainer = $('#bench-pool-kpis');
        $kpiContainer.empty();

        if (!state.poolStats || typeof state.poolStats !== 'object') {
            $kpiContainer.html(WT.emptyState('No pool data available'));
            return;
        }

        const pools = [
            { label: 'All Pool', key: 'all' },
            { label: 'Premier Pool', key: 'premier' }
        ];
        let html = '';

        pools.forEach(pool => {
            const poolData = state.poolStats[pool.key] || {};

            html += `
                <div class="wt-pool-block">
                    <div class="wt-pool-block-title">${pool.label}</div>
                    <div class="wt-pool-stats-row">
                        <div class="wt-pool-stat">
                            <div class="wt-pool-stat-value">${WT.fmtInt(poolData.total || 0)}</div>
                            <div class="wt-pool-stat-label">Entities</div>
                        </div>
                        <div class="wt-pool-stat">
                            <div class="wt-pool-stat-value">${WT.fmtInt(poolData.firms || 0)}</div>
                            <div class="wt-pool-stat-label">Firms</div>
                        </div>
                        <div class="wt-pool-stat">
                            <div class="wt-pool-stat-value">${WT.fmtInt(poolData.advisors || 0)}</div>
                            <div class="wt-pool-stat-label">Advisors</div>
                        </div>
                        <div class="wt-pool-stat">
                            <div class="wt-pool-stat-value">${WT.fmtInt(poolData.avg_reviews || 0)}</div>
                            <div class="wt-pool-stat-label">Avg Reviews</div>
                        </div>
                    </div>
                </div>
            `;
        });

        $kpiContainer.html(html);
    }

    /**
     * Load score distributions for histograms
     */
    function loadDistributions() {
        // Show loading on histogram containers
        (wtAnalytics.dimensions || []).forEach(function(dim) {
            WT.showLoading('#bench-hist-' + dim);
        });

        const params = {
            method: state.method,
            entity_type: state.entityType,
            min_peer_reviews: state.minPeerReviews
        };

        WT.api('benchmarks/distributions', params, function(data) {
            state.distributions = data || {};
            redrawHistograms();
        }, function(error) {
            console.error('Error loading distributions:', error);
            (wtAnalytics.dimensions || []).forEach(function(dim) {
                WT.emptyState('#bench-hist-' + dim, 'Failed to load distribution data');
            });
        });
    }

    /**
     * Redraw all histograms
     */
    function redrawHistograms() {
        const dimensions = wtAnalytics.dimensions || [];

        dimensions.forEach(dim => {
            renderHistogram(dim);
        });
    }

    /**
     * Render a single histogram for a dimension
     */
    function renderHistogram(dimension) {
        const dimData = state.distributions[dimension] || [];
        const $container = $(`#bench-hist-${dimension}`);

        if (!$container.length) return;

        if (!dimData || dimData.length === 0) {
            $container.html(WT.emptyState('No data available'));
            return;
        }

        const dimLabel = wtAnalytics.dimLabels[dimension] || dimension;
        const dimColor = wtAnalytics.dimColors[dimension] || '#666';

        const traces = [
            {
                x: dimData,
                type: 'histogram',
                nbinsx: 20,
                marker: {
                    color: dimColor,
                    opacity: 0.7
                },
                name: 'Distribution'
            }
        ];

        const layout = WT.baseLayout({
            title: dimLabel,
            xaxis: { title: 'Score' },
            yaxis: { title: 'Count' },
            hovermode: 'x'
        });

        // If entity is selected, add vertical line and annotation
        if (state.entityId && state.entityScores) {
            const enriched = state.entityScores[dimension];
            const entityScore = enriched && typeof enriched === 'object' ? enriched.raw : enriched;
            if (typeof entityScore === 'number') {
                const shapes = [{
                    type: 'line',
                    x0: entityScore,
                    x1: entityScore,
                    y0: 0,
                    y1: 1,
                    yref: 'paper',
                    line: {
                        color: '#E74C3C',
                        width: 3,
                        dash: 'dash'
                    }
                }];

                const annotations = [{
                    x: entityScore,
                    y: 1,
                    yref: 'paper',
                    text: 'You',
                    showarrow: false,
                    bgcolor: '#E74C3C',
                    font: { color: '#fff', size: 12 },
                    xanchor: 'center',
                    yanchor: 'bottom'
                }];

                layout.shapes = shapes;
                layout.annotations = annotations;
            }
        }

        WT.plot($container[0], traces, layout);
    }

    /**
     * Load entity scores when entity is selected
     */
    function loadEntityScores() {
        $('#bench-comparison-section').show();
        WT.showLoading('#bench-comparison-table');
        const params = {
            entity_id: state.entityId,
            method: state.method
        };

        WT.api('advisor-dna/advisor-scores', params, function(data) {
            state.entityScores = data || {};
            loadMethodBreakpoints();
        }, function(error) {
            console.error('Error loading entity scores:', error);
            WT.emptyState('#bench-comparison-table', 'Failed to load entity scores');
        });
    }

    /**
     * Load method breakpoints for tier calculation
     */
    function loadMethodBreakpoints() {
        const params = { method: state.method };

        WT.api('advisor-dna/method-breakpoints', params, function(data) {
            state.breakpoints = data || {};
            redrawHistograms();
            renderComparisonTable();
            $('#bench-comparison-section').show();
        }, function(error) {
            console.error('Error loading breakpoints:', error);
            WT.emptyState('#bench-comparison-table', 'Failed to load benchmark data');
        });
    }

    /**
     * Render comparison table
     */
    function renderComparisonTable() {
        const $table = $('#bench-comparison-table');
        $table.empty();

        if (!state.entityScores || !state.breakpoints) return;

        const dimensions = wtAnalytics.dimensions || [];
        let html = `
            <table class="bench-comparison-table">
                <thead>
                    <tr>
                        <th>Dimension</th>
                        <th>Your Score</th>
                        <th>Percentile</th>
                        <th>P25</th>
                        <th>P50</th>
                        <th>P75</th>
                    </tr>
                </thead>
                <tbody>
        `;

        dimensions.forEach(dim => {
            const enriched = state.entityScores[dim] || {};
            const score = typeof enriched === 'object' ? (enriched.raw || 0) : enriched;
            const percentile = typeof enriched === 'object' ? (enriched.percentile || 0) : 0;
            const breakpoint = state.breakpoints[dim] || {};
            const p25 = breakpoint.p25 || 0;
            const p50 = breakpoint.p50 || 0;
            const p75 = breakpoint.p75 || 0;

            const tier = WT.getTier(percentile);
            const tierClass = WT.getTierClass(tier);
            const label = wtAnalytics.dimLabels[dim] || dim;

            html += `
                <tr>
                    <td>${label}</td>
                    <td>${typeof score === 'number' ? score.toFixed(4) : score}</td>
                    <td class="percentile-cell tier-${tierClass}">${Math.round(percentile)}</td>
                    <td>${typeof p25 === 'number' ? p25.toFixed(4) : p25}</td>
                    <td>${typeof p50 === 'number' ? p50.toFixed(4) : p50}</td>
                    <td>${typeof p75 === 'number' ? p75.toFixed(4) : p75}</td>
                </tr>
            `;
        });

        // Add composite row
        const compositeEnriched = state.entityScores.composite || {};
        const compositeScore = typeof compositeEnriched === 'object' ? (compositeEnriched.raw || 0) : compositeEnriched;
        const compositePercentile = typeof compositeEnriched === 'object' ? (compositeEnriched.percentile || 0) : 0;
        const compositeBreakpoint = state.breakpoints.composite || {};
        const compositeP25 = compositeBreakpoint.p25 || 0;
        const compositeP50 = compositeBreakpoint.p50 || 0;
        const compositeP75 = compositeBreakpoint.p75 || 0;
        const compositeTier = WT.getTier(compositePercentile);
        const compositeTierClass = WT.getTierClass(compositeTier);

        html += `
            <tr class="composite-row">
                <td><strong>Composite</strong></td>
                <td><strong>${typeof compositeScore === 'number' ? compositeScore.toFixed(4) : compositeScore}</strong></td>
                <td class="percentile-cell tier-${compositeTierClass}"><strong>${Math.round(compositePercentile)}</strong></td>
                <td><strong>${typeof compositeP25 === 'number' ? compositeP25.toFixed(4) : compositeP25}</strong></td>
                <td><strong>${typeof compositeP50 === 'number' ? compositeP50.toFixed(4) : compositeP50}</strong></td>
                <td><strong>${typeof compositeP75 === 'number' ? compositeP75.toFixed(4) : compositeP75}</strong></td>
            </tr>
        `;

        html += `
                </tbody>
            </table>
        `;

        $table.html(html);
    }

    /**
     * Calculate percentile given score and breakpoints
     */
    function calculatePercentile(score, p25, p50, p75) {
        if (score <= p25) {
            return 25;
        } else if (score <= p50) {
            return 50;
        } else if (score <= p75) {
            return 75;
        } else {
            return 100;
        }
    }

    // Initialize on document ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(jQuery);

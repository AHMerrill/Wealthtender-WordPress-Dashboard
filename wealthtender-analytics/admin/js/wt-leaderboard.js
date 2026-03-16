/**
 * Wealthtender Analytics: Leaderboard Module
 * Displays ranked entities with comparison capabilities
 */

(function($) {
    'use strict';

    // State management
    const state = {
        dimension: 'composite',
        method: 'mean',
        entityType: null,
        minPeerReviews: 0,
        topN: 5,
        leaderboardData: null,
        selectedIds: [],    // max 2
        entityMap: {},      // id → enriched data cache
    };

    /**
     * Initialize the leaderboard module
     */
    function init() {
        bindEvents();
        populateDimensionDropdown();
        loadLeaderboard();
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        $('#lb-dim-select').on('change', function() {
            state.dimension = $(this).val();
            clearSelections();
            loadLeaderboard();
        });

        $('#lb-method-select').on('change', function() {
            state.method = $(this).val();
            clearSelections();
            loadLeaderboard();
        });

        $('#lb-entity-type').on('change', function() {
            state.entityType = $(this).val() || null;
            clearSelections();
            loadLeaderboard();
        });

        $('#lb-pool-select').on('change', function() {
            state.minPeerReviews = parseInt($(this).val());
            clearSelections();
            loadLeaderboard();
        });

        $('#lb-top-n').on('change', function() {
            state.topN = parseInt($(this).val());
            clearSelections();
            loadLeaderboard();
        });

        $('#lb-clear-selections').on('click', function() {
            clearSelections();
        });
    }

    /**
     * Populate dimension dropdown
     */
    function populateDimensionDropdown() {
        const $select = $('#lb-dim-select');
        $select.empty();

        $select.append('<option value="composite">Composite</option>');

        const dimensions = wtAnalytics.dimensions || [];
        dimensions.forEach(dim => {
            const label = wtAnalytics.dimLabels[dim] || dim;
            $select.append(`<option value="${dim}">${label}</option>`);
        });
    }

    /**
     * Load leaderboard data from API
     */
    function loadLeaderboard() {
        WT.showLoading('#lb-chart');
        const params = {
            method: state.method,
            entity_type: state.entityType,
            min_peer_reviews: state.minPeerReviews,
            top_n: state.topN,
            dimension: state.dimension
        };

        WT.api('leaderboard', params, function(data) {
            // API returns {dimension: [...items]}; extract the current dimension's data
            var dimKey = state.dimension || 'composite';
            var items = (data && data[dimKey]) ? data[dimKey] : [];
            // Normalize item properties for chart rendering
            state.leaderboardData = items.map(function(item) {
                return {
                    id: item.entity_id,
                    name: item.entity_name,
                    entity_type: item.entity_type,
                    review_count: item.review_count,
                    score: item.score,
                    percentile: item.enriched ? (item.enriched.percentile || 0) : 0,
                    tier: item.enriched ? (item.enriched.tier || '') : '',
                };
            });
            // Auto-select first two entities for comparison
            if (state.leaderboardData.length >= 2 && state.selectedIds.length === 0) {
                var sorted = [...state.leaderboardData].sort(function(a, b) { return b.percentile - a.percentile; });
                state.selectedIds = [sorted[0].id, sorted[1].id];
                renderChart();
                loadEntityEnrichment();
            } else {
                renderChart();
            }
            // loading cleared by chart render
        }, function(error) {
            console.error('Error loading leaderboard:', error);
            WT.emptyState('#lb-chart', 'Failed to load leaderboard data');
        });
    }

    /**
     * Render the leaderboard bar chart
     */
    function renderChart() {
        const $container = $('#lb-chart');

        if (!state.leaderboardData || state.leaderboardData.length === 0) {
            $container.html(WT.emptyState('No leaderboard data available'));
            return;
        }

        // Sort ascending so Plotly renders highest at top (horizontal bars render bottom-to-top)
        const sorted = [...state.leaderboardData].sort((a, b) => {
            return a.percentile - b.percentile;
        });

        const names = sorted.map(item => item.name);
        const percentiles = sorted.map(item => item.percentile);
        const ids = sorted.map(item => item.id);

        // Determine bar colors
        const colors = sorted.map(item => {
            if (state.dimension === 'composite') {
                return '#1A3A52'; // Navy for composite
            }
            return wtAnalytics.dimColors[state.dimension] || '#666';
        });

        // Determine opacity based on selections
        const opacity = sorted.map(item => {
            if (state.selectedIds.length === 0) {
                return 1; // Full opacity when no selections
            }
            return state.selectedIds.includes(item.id) ? 1 : 0.3;
        });

        // Create bar colors with opacity
        const barColors = colors.map((color, idx) => {
            return WT.hexToRgba(color, opacity[idx]);
        });

        const traces = [{
            y: names,
            x: percentiles,
            type: 'bar',
            orientation: 'h',
            marker: {
                color: barColors,
                line: {
                    color: sorted.map((item, idx) => {
                        return state.selectedIds.includes(item.id) ? '#000' : 'transparent';
                    }),
                    width: sorted.map((item, idx) => {
                        return state.selectedIds.includes(item.id) ? 3 : 0;
                    })
                }
            },
            text: sorted.map(item => `${getOrdinal(item.percentile)}`),
            textposition: 'outside',
            hovertemplate: '<b>%{y}</b><br>Percentile: %{x}<extra></extra>',
            customdata: ids
        }];

        const layout = WT.baseLayout({
            title: `Top ${state.topN} - ${state.dimension === 'composite' ? 'Composite' : wtAnalytics.dimLabels[state.dimension] || state.dimension}`,
            xaxis: {
                title: 'Percentile',
                range: [0, 110]
            },
            yaxis: {
                title: '',
                automargin: true
            },
            height: Math.max(400, state.leaderboardData.length * 50 + 120),
            margin: { l: 250, r: 60, t: 50, b: 50 }
        });

        const $chart = $container[0];
        WT.plot($chart, traces, layout);

        // Add click handler
        $chart.on('plotly_click', function(data) {
            if (data.points && data.points.length > 0) {
                const point = data.points[0];
                const id = point.customdata;
                toggleSelection(id);
            }
        });
    }

    /**
     * Toggle entity selection
     */
    function toggleSelection(id) {
        const index = state.selectedIds.indexOf(id);

        if (index > -1) {
            // Remove selection
            state.selectedIds.splice(index, 1);
        } else {
            // Add selection
            if (state.selectedIds.length >= 2) {
                // Remove oldest (first) selection
                state.selectedIds.shift();
            }
            state.selectedIds.push(id);
        }

        // Always re-render the bar chart so opacity/borders update immediately
        renderChart();

        if (state.selectedIds.length > 0) {
            loadEntityEnrichment();
        } else {
            $('#lb-comparison-section').hide();
        }
    }

    /**
     * Clear all selections
     */
    function clearSelections() {
        state.selectedIds = [];
        state.entityMap = {};
        $('#lb-comparison-section').hide();
        renderChart();
    }

    /**
     * Load enriched data for selected entities
     */
    function loadEntityEnrichment() {
        WT.showLoading('#lb-comparison-spider');
        let completed = 0;

        state.selectedIds.forEach(id => {
            // Check cache first
            if (state.entityMap[id]) {
                completed++;
                if (completed === state.selectedIds.length) {
                    renderComparison();
                    // loading cleared by chart render
                }
                return;
            }

            const params = {
                entity_id: id,
                method: state.method
            };

            WT.api('advisor-dna/advisor-scores', params, function(data) {
                state.entityMap[id] = data || {};
                completed++;
                if (completed === state.selectedIds.length) {
                    renderComparison();
                    // loading cleared by chart render
                }
            }, function(error) {
                console.error('Error loading entity scores:', error);
                completed++;
                if (completed === state.selectedIds.length) {
                    WT.emptyState('#lb-comparison-spider', 'Failed to load comparison data');
                }
            });
        });
    }

    /**
     * Render comparison panel
     */
    function renderComparison() {
        renderSpiderChart();
        renderComparisonTable();
        $('#lb-comparison-section').show();
    }

    /**
     * Render spider/radar chart for selected entities
     */
    function renderSpiderChart() {
        const $container = $('#lb-comparison-spider');
        const dimensions = wtAnalytics.dimensions || [];

        const traces = [];
        const palette = wtAnalytics.dataVizPalette || [];

        state.selectedIds.forEach((id, idx) => {
            const entityData = state.entityMap[id];
            if (!entityData) return;

            // Extract percentiles from enriched dimension objects
            const values = dimensions.map(dim => {
                var d = entityData[dim];
                return (d && typeof d === 'object') ? (d.percentile || 0) : (d || 0);
            });

            // Close the polygon
            const closedValues = [...values, values[0]];
            const closedLabels = [...dimensions.map(dim => wtAnalytics.dimShort[dim] || dim), wtAnalytics.dimShort[dimensions[0]] || dimensions[0]];

            var itemName = 'Entity ' + (idx + 1);
            var found = state.leaderboardData.find(item => item.id === id);
            if (found) itemName = found.name;

            traces.push({
                type: 'scatterpolar',
                r: closedValues,
                theta: closedLabels,
                fill: 'toself',
                name: itemName,
                line: { color: palette[idx % palette.length] || '#666' }
            });
        });

        const layout = WT.baseLayout({
            polar: {
                radialaxis: {
                    visible: true,
                    range: [0, 100]
                },
                domain: {
                    x: [0.05, 0.95],
                    y: [0.05, 0.9]
                }
            },
            showlegend: true,
            legend: {
                orientation: 'h',
                y: -0.05,
                x: 0.5,
                xanchor: 'center'
            },
            height: 450,
            margin: { l: 40, r: 40, t: 30, b: 40 }
        });

        WT.plot($container[0], traces, layout);
    }

    /**
     * Render comparison table
     */
    function renderComparisonTable() {
        const $table = $('#lb-comparison-table');
        // Rebuild thead/tbody structure (empty() destroys them)
        $table.html('<thead></thead><tbody></tbody>');

        const dimensions = wtAnalytics.dimensions || [];

        if (state.selectedIds.length === 1) {
            renderSingleComparisonTable(dimensions);
        } else if (state.selectedIds.length === 2) {
            renderDualComparisonTable(dimensions);
        }
    }

    /**
     * Render comparison table for single entity
     */
    function renderSingleComparisonTable(dimensions) {
        const $table = $('#lb-comparison-table');
        const id = state.selectedIds[0];
        const entityData = state.entityMap[id];
        const entityName = state.leaderboardData.find(item => item.id === id)?.name || 'Entity';

        if (!entityData) return;

        let thead = `<tr>
            <th>Dimension</th>
            <th>Score</th>
            <th>Percentile</th>
            <th>Tier</th>
        </tr>`;

        let tbody = '';

        dimensions.forEach(dim => {
            const enriched = entityData[dim] || {};
            const score = typeof enriched === 'object' ? (enriched.raw || 0) : enriched;
            const percentile = typeof enriched === 'object' ? (enriched.percentile || 0) : 0;
            const tier = typeof enriched === 'object' ? (enriched.tier || WT.getTier(percentile)) : WT.getTier(percentile);
            const tierClass = WT.getTierClass(tier);
            const label = wtAnalytics.dimLabels[dim] || dim;

            tbody += `<tr>
                <td>${label}</td>
                <td>${typeof score === 'number' ? score.toFixed(4) : score}</td>
                <td class="percentile-cell tier-${tierClass}">${Math.round(percentile)}</td>
                <td>${tier}</td>
            </tr>`;
        });

        // Add composite row
        const compositeEnriched = entityData.composite || {};
        const compositeScore = typeof compositeEnriched === 'object' ? (compositeEnriched.raw || 0) : compositeEnriched;
        const compositePercentile = typeof compositeEnriched === 'object' ? (compositeEnriched.percentile || 0) : 0;
        const compositeTier = typeof compositeEnriched === 'object' ? (compositeEnriched.tier || WT.getTier(compositePercentile)) : WT.getTier(compositePercentile);
        const compositeTierClass = WT.getTierClass(compositeTier);

        tbody += `<tr class="composite-row">
            <td><strong>Composite</strong></td>
            <td><strong>${typeof compositeScore === 'number' ? compositeScore.toFixed(4) : compositeScore}</strong></td>
            <td class="percentile-cell tier-${compositeTierClass}"><strong>${Math.round(compositePercentile)}</strong></td>
            <td><strong>${compositeTier}</strong></td>
        </tr>`;

        $table.find('thead').html(thead);
        $table.find('tbody').html(tbody);
    }

    /**
     * Render comparison table for two entities
     */
    function renderDualComparisonTable(dimensions) {
        const $table = $('#lb-comparison-table');
        const id1 = state.selectedIds[0];
        const id2 = state.selectedIds[1];
        const data1 = state.entityMap[id1];
        const data2 = state.entityMap[id2];
        const name1 = state.leaderboardData.find(item => item.id === id1)?.name || 'Entity 1';
        const name2 = state.leaderboardData.find(item => item.id === id2)?.name || 'Entity 2';

        if (!data1 || !data2) return;

        let thead = `<tr>
            <th>Dimension</th>
            <th>${WT.escapeHtml(name1)}</th>
            <th>${WT.escapeHtml(name2)}</th>
            <th>Difference</th>
        </tr>`;

        let tbody = '';

        dimensions.forEach(dim => {
            const e1 = data1[dim] || {};
            const e2 = data2[dim] || {};
            const pct1 = typeof e1 === 'object' ? (e1.percentile || 0) : 0;
            const pct2 = typeof e2 === 'object' ? (e2.percentile || 0) : 0;
            const diff = pct2 - pct1;
            const diffClass = diff > 0 ? 'positive' : diff < 0 ? 'negative' : 'neutral';
            const label = wtAnalytics.dimLabels[dim] || dim;

            tbody += `<tr>
                <td>${label}</td>
                <td>${Math.round(pct1)}</td>
                <td>${Math.round(pct2)}</td>
                <td class="diff-cell ${diffClass}">${diff > 0 ? '+' : ''}${Math.round(diff)}</td>
            </tr>`;
        });

        // Add composite row
        const ce1 = data1.composite || {};
        const ce2 = data2.composite || {};
        const compositePct1 = typeof ce1 === 'object' ? (ce1.percentile || 0) : 0;
        const compositePct2 = typeof ce2 === 'object' ? (ce2.percentile || 0) : 0;
        const compositeDiff = compositePct2 - compositePct1;
        const compositeDiffClass = compositeDiff > 0 ? 'positive' : compositeDiff < 0 ? 'negative' : 'neutral';

        tbody += `<tr class="composite-row">
            <td><strong>Composite</strong></td>
            <td><strong>${Math.round(compositePct1)}</strong></td>
            <td><strong>${Math.round(compositePct2)}</strong></td>
            <td class="diff-cell ${compositeDiffClass}"><strong>${compositeDiff > 0 ? '+' : ''}${Math.round(compositeDiff)}</strong></td>
        </tr>`;

        $table.find('thead').html(thead);
        $table.find('tbody').html(tbody);
    }

    /**
     * Convert percentile to ordinal (e.g., "92nd")
     */
    function getOrdinal(num) {
        const n = Math.round(num);
        const suffix = ['th', 'st', 'nd', 'rd'];
        const val = n % 100;
        if (val >= 11 && val <= 13) return n + 'th';
        const index = val % 10;
        return n + (suffix[index] || suffix[0]);
    }

    // Initialize on document ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(jQuery);

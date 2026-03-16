/**
 * Wealthtender Analytics - EDA Page
 * Handles data fetching, filtering, and chart rendering for Exploratory Data Analysis
 */

(function() {
    'use strict';

    // ==================== STATE ====================
    const state = {
        entityType: null,
        entityId: null,
        dateStart: null,
        dateEnd: null,
        rating: null,
        tokenCats: ['all'],
        reviewCats: ['all'],
        ngramN: 1,
        topN: 30,
        excludeStopwords: true,
        customStopwords: '',
        timeFreq: 'month',
        data: null,         // cached EDA payload
        meta: null,         // cached meta for category mapping
    };

    // ==================== INITIALIZATION ====================
    document.addEventListener('DOMContentLoaded', function() {
        initializeEntityList();
        setupEventListeners();
        loadInitialData();
    });

    /**
     * Initialize entity dropdown with list from API
     */
    function initializeEntityList() {
        WT.api('entities', {}, function(response) {
            if (!response) {
                console.error('Failed to load entities');
                return;
            }

            // API returns {firms: [...], advisors: [...]}
            window.wtEdaEntities = {
                firms: response.firms || [],
                advisors: response.advisors || []
            };

            // Set default entity type
            const defaultType = document.querySelector('#eda-entity-type:checked')?.value || 'firm';
            state.entityType = defaultType;

            populateEntitySelect(defaultType);
        });
    }

    /**
     * Populate entity dropdown based on selected entity type
     */
    function populateEntitySelect(entityType) {
        const selectEl = document.getElementById('eda-entity-select');
        const entities = entityType === 'firm'
            ? (window.wtEdaEntities?.firms || [])
            : (window.wtEdaEntities?.advisors || []);

        const options = [
            { value: '', label: 'All ' + (entityType === 'firm' ? 'Firms' : 'Advisors') }
        ];

        entities.forEach(entity => {
            options.push({
                value: entity.entity_id,
                label: entity.entity_name
            });
        });

        WT.populateSelect(selectEl, options);

        // Reset entity selection
        state.entityId = null;
        selectEl.value = '';
    }

    /**
     * Setup all event listeners
     */
    function setupEventListeners() {
        const debounceDelay = 300;
        let debounceTimer;

        // Entity type change
        document.querySelectorAll('#eda-entity-type').forEach(radio => {
            radio.addEventListener('change', function() {
                state.entityType = this.value;
                state.entityId = null;
                populateEntitySelect(this.value);
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
            });
        });

        // Entity select change
        document.getElementById('eda-entity-select').addEventListener('change', function() {
            state.entityId = this.value || null;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        // Date range
        document.getElementById('eda-date-start').addEventListener('change', function() {
            state.dateStart = this.value || null;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        document.getElementById('eda-date-end').addEventListener('change', function() {
            state.dateEnd = this.value || null;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        // Rating filter
        document.getElementById('eda-rating').addEventListener('change', function() {
            state.rating = this.value ? parseInt(this.value) : null;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        // Token categories (multiselect)
        document.getElementById('eda-token-cats').addEventListener('change', function() {
            const selected = Array.from(this.selectedOptions).map(o => o.value);
            state.tokenCats = selected.length > 0 ? selected : ['all'];
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        // Review categories (multiselect)
        document.getElementById('eda-review-cats').addEventListener('change', function() {
            const selected = Array.from(this.selectedOptions).map(o => o.value);
            state.reviewCats = selected.length > 0 ? selected : ['all'];
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        // N-gram size
        document.getElementById('eda-ngram-n').addEventListener('change', function() {
            state.ngramN = parseInt(this.value) || 1;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        // Top N results
        document.getElementById('eda-top-n').addEventListener('change', function() {
            state.topN = parseInt(this.value) || 30;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        // Stopwords
        document.getElementById('eda-exclude-stopwords').addEventListener('change', function() {
            state.excludeStopwords = this.checked;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        document.getElementById('eda-custom-stopwords').addEventListener('change', function() {
            state.customStopwords = this.value;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        // Time frequency
        document.getElementById('eda-time-freq').addEventListener('change', function() {
            state.timeFreq = this.value || 'month';
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadEDAData(), debounceDelay);
        });

        // Reset button
        document.getElementById('eda-reset-btn').addEventListener('click', function() {
            resetFilters();
        });

        // Review detail close button
        const closeBtn = document.querySelector('#eda-review-detail .close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                document.getElementById('eda-review-detail').style.display = 'none';
            });
        }
    }

    /**
     * Load initial EDA data without filters
     */
    function loadInitialData() {
        WT.showLoading('#eda-summary-kpis');
        WT.showLoading('#eda-coverage-kpis');

        const params = buildApiParams();

        WT.api('eda/charts', params, function(response) {
            if (!response) {
                showErrorState();
                return;
            }

            // API returns data directly or wrapped in .data
            const data = response.data || response;
            state.data = data;

            // Cache meta for category mapping
            if (response.meta) {
                state.meta = response.meta;
            }

            // Render all components
            renderAllCharts(data);
            renderSummaryKpis(data);
            renderCoverageKpis(data);
        }, function() {
            showErrorState();
        });
    }

    /**
     * Load EDA data based on current filters
     */
    function loadEDAData() {
        WT.showLoading('#eda-summary-kpis');
        WT.showLoading('#eda-coverage-kpis');
        WT.showLoading('#eda-chart-rating');
        WT.showLoading('#eda-chart-timeline');
        WT.showLoading('#eda-chart-reviews-per-advisor');
        WT.showLoading('#eda-chart-tokens');
        WT.showLoading('#eda-chart-rating-vs-token');
        WT.showLoading('#eda-chart-lexical');

        const params = buildApiParams();

        WT.api('eda/charts', params, function(response) {
            if (!response) {
                showErrorState();
                return;
            }

            // API returns data directly or wrapped in .data
            const data = response.data || response;
            state.data = data;

            // Cache meta for category mapping
            if (response.meta) {
                state.meta = response.meta;
            }

            // Render all components
            renderAllCharts(data);
            renderSummaryKpis(data);
            renderCoverageKpis(data);
        }, function() {
            showErrorState();
        });
    }

    /**
     * Build API parameters from current state
     */
    function buildApiParams() {
        const params = {
            entity_type: state.entityType,
            entity_id: state.entityId,
            date_start: state.dateStart,
            date_end: state.dateEnd,
            rating: state.rating,
            token_cats: state.tokenCats,
            review_cats: state.reviewCats,
            ngram_n: state.ngramN,
            top_n: state.topN,
            exclude_stopwords: state.excludeStopwords,
            custom_stopwords: state.customStopwords,
            time_freq: state.timeFreq,
        };

        // Remove null/undefined values
        Object.keys(params).forEach(key => {
            if (params[key] === null || params[key] === undefined || params[key] === '') {
                delete params[key];
            }
        });

        return params;
    }

    /**
     * Render all charts — clear spinners even when data is missing
     */
    function renderAllCharts(data) {
        if (!data) return;

        if (data.rating_distribution) {
            renderRatingDistribution(data);
        } else {
            WT.emptyState('#eda-chart-rating', 'No rating data available');
        }

        if (data.reviews_over_time) {
            renderTimeline(data);
        } else {
            WT.emptyState('#eda-chart-timeline', 'No timeline data available');
        }

        if (data.reviews_per_advisor) {
            renderReviewsPerAdvisor(data);
        } else {
            WT.emptyState('#eda-chart-reviews-per-advisor', 'No advisor data available');
        }

        if (data.token_counts) {
            renderTokenDistribution(data);
        } else {
            WT.emptyState('#eda-chart-tokens', 'No token data available');
        }

        if (data.rating_vs_token) {
            renderRatingVsToken(data);
        } else {
            WT.emptyState('#eda-chart-rating-vs-token', 'No scatter data available');
        }

        if (data.lexical) {
            renderLexical(data);
        } else {
            WT.emptyState('#eda-chart-lexical', 'No lexical data available');
        }
    }

    // ==================== CHART RENDERING ====================

    /**
     * Render rating distribution bar chart
     */
    function renderRatingDistribution(data) {
        const container = document.getElementById('eda-chart-rating');
        if (!container || !data.rating_distribution) return;

        const ratings = Object.keys(data.rating_distribution)
            .map(r => parseInt(r))
            .sort((a, b) => a - b);

        const counts = ratings.map(r => data.rating_distribution[r]);
        const palette = wtAnalytics.dataVizPalette || [];

        const trace = {
            x: ratings,
            y: counts,
            type: 'bar',
            marker: {
                color: ratings.map((_, i) => palette[i % palette.length])
            },
            hovertemplate: 'Rating: %{x}<br>Count: %{y}<extra></extra>'
        };

        const layout = WT.baseLayout({
            title: 'Rating Distribution',
            xaxis: { title: 'Rating' },
            yaxis: { title: 'Count' },
            showlegend: false
        });

        WT.plot(container, [trace], layout);
    }

    /**
     * Render reviews over time line chart
     */
    function renderTimeline(data) {
        const container = document.getElementById('eda-chart-timeline');
        if (!container || !data.reviews_over_time) return;

        const periods = Object.keys(data.reviews_over_time).sort();
        const counts = periods.map(p => data.reviews_over_time[p]);
        const palette = wtAnalytics.dataVizPalette || [];

        const trace = {
            x: periods,
            y: counts,
            type: 'scatter',
            mode: 'lines+markers',
            line: { color: palette[0] || '#1f77b4' },
            marker: { color: palette[1] || '#ff7f0e', size: 8 },
            hovertemplate: '%{x}<br>Reviews: %{y}<extra></extra>'
        };

        const layout = WT.baseLayout({
            title: 'Reviews Over Time',
            xaxis: { title: 'Period' },
            yaxis: { title: 'Number of Reviews' },
            showlegend: false
        });

        WT.plot(container, [trace], layout);
    }

    /**
     * Render reviews per advisor histogram with median and P90 lines
     */
    function renderReviewsPerAdvisor(data) {
        const container = document.getElementById('eda-chart-reviews-per-advisor');
        if (!container || !data.reviews_per_advisor) return;

        const counts = data.reviews_per_advisor
            .map(item => item.count)
            .sort((a, b) => a - b);

        if (counts.length === 0) {
            WT.emptyState(container, 'No data available');
            return;
        }

        // Compute median
        const median = counts.length % 2 === 0
            ? (counts[counts.length / 2 - 1] + counts[counts.length / 2]) / 2
            : counts[Math.floor(counts.length / 2)];

        // Compute P90
        const p90Index = Math.ceil(counts.length * 0.9) - 1;
        const p90 = counts[p90Index];

        const palette = wtAnalytics.dataVizPalette || [];
        const histColor = palette[4] || palette[2] || '#1f77b4';

        const trace = {
            x: counts,
            type: 'histogram',
            marker: { color: histColor },
            nbinsx: 30,
            hovertemplate: 'Range: %{x}<br>Advisors: %{y}<extra></extra>'
        };

        const shapes = [
            {
                type: 'line',
                x0: median,
                x1: median,
                y0: 0,
                y1: 1,
                yref: 'paper',
                line: { color: 'rgba(255, 0, 0, 0.7)', width: 2, dash: 'dash' }
            },
            {
                type: 'line',
                x0: p90,
                x1: p90,
                y0: 0,
                y1: 1,
                yref: 'paper',
                line: { color: 'rgba(255, 165, 0, 0.7)', width: 2, dash: 'dot' }
            }
        ];

        const layout = WT.baseLayout({
            title: 'Reviews per Advisor',
            xaxis: { title: 'Number of Reviews' },
            yaxis: { title: 'Number of Advisors' },
            showlegend: false,
            shapes: shapes
        });

        // Add annotations for median and P90
        layout.annotations = [
            {
                x: median,
                y: 1,
                yref: 'paper',
                text: 'Median: ' + Math.round(median),
                showarrow: false,
                yanchor: 'bottom',
                bgcolor: 'rgba(255, 0, 0, 0.1)',
                bordercolor: 'red',
                borderwidth: 1,
                borderpad: 4
            },
            {
                x: p90,
                y: 0.95,
                yref: 'paper',
                text: 'P90: ' + Math.round(p90),
                showarrow: false,
                yanchor: 'top',
                bgcolor: 'rgba(255, 165, 0, 0.1)',
                bordercolor: 'orange',
                borderwidth: 1,
                borderpad: 4
            }
        ];

        WT.plot(container, [trace], layout);
    }

    /**
     * Render token (word count) distribution histogram
     */
    function renderTokenDistribution(data) {
        const container = document.getElementById('eda-chart-tokens');
        if (!container || !data.token_counts) return;

        const palette = wtAnalytics.dataVizPalette || [];
        const color = palette[4] || palette[2] || '#1f77b4';

        const trace = {
            x: data.token_counts,
            type: 'histogram',
            marker: { color: color },
            nbinsx: 40,
            hovertemplate: 'Word Count: %{x}<br>Reviews: %{y}<extra></extra>'
        };

        const layout = WT.baseLayout({
            title: 'Review Length Distribution',
            xaxis: { title: 'Word Count' },
            yaxis: { title: 'Number of Reviews' },
            showlegend: false
        });

        WT.plot(container, [trace], layout);
    }

    /**
     * Render rating vs token count scatter plot
     */
    function renderRatingVsToken(data) {
        const container = document.getElementById('eda-chart-rating-vs-token');
        if (!container || !data.rating_vs_token) return;

        const palette = wtAnalytics.dataVizPalette || [];
        const scatterColor = palette[1] || '#ff7f0e';

        const xs = [];
        const ys = [];
        const customdata = [];

        data.rating_vs_token.forEach(item => {
            xs.push(item.token_count);
            ys.push(item.rating);
            customdata.push(item.review_idx);
        });

        const trace = {
            x: xs,
            y: ys,
            mode: 'markers',
            type: 'scattergl',
            marker: {
                color: scatterColor,
                opacity: 0.65,
                size: 7
            },
            customdata: customdata,
            hovertemplate: 'Words: %{x}<br>Rating: %{y}<extra></extra>'
        };

        const layout = WT.baseLayout({
            title: 'Rating vs Review Length',
            xaxis: { title: 'Word Count' },
            yaxis: { title: 'Rating' },
            showlegend: false
        });

        WT.plot(container, [trace], layout);

        // Add click handler
        container.on('plotly_click', function(data) {
            if (!data.points || data.points.length === 0) return;

            const point = data.points[0];
            const reviewIdx = point.customdata;

            if (reviewIdx) {
                fetchAndShowReviewDetail(reviewIdx);
            }
        });
    }

    /**
     * Render lexical (n-gram) bar chart
     */
    function renderLexical(data) {
        const container = document.getElementById('eda-chart-lexical');
        if (!container || !data.lexical) return;

        if (data.lexical.length === 0) {
            WT.emptyState(container, 'No data available');
            return;
        }

        const ngrams = data.lexical.map(item => item.ngram);
        const frequencies = data.lexical.map(item => item.frequency);
        const palette = wtAnalytics.dataVizPalette || [];

        const trace = {
            x: ngrams,
            y: frequencies,
            type: 'bar',
            marker: {
                color: ngrams.map((_, i) => palette[i % palette.length])
            },
            text: frequencies.map(f => f.toString()),
            textposition: 'outside',
            hovertemplate: '%{x}<br>Frequency: %{y}<extra></extra>'
        };

        const layout = WT.baseLayout({
            title: 'Top N-Grams',
            xaxis: {
                title: 'N-Gram',
                tickangle: -45
            },
            yaxis: { title: 'Frequency' },
            showlegend: false,
            margin: { b: 100 }
        });

        WT.plot(container, [trace], layout);
    }

    // ==================== KPI RENDERING ====================

    /**
     * Render summary KPIs
     */
    function renderSummaryKpis(data) {
        const container = document.getElementById('eda-summary-kpis');
        if (!container || !data.summary) return;

        const summary = data.summary;
        const kpis = [
            {
                label: 'Total Reviews',
                value: WT.fmtInt(summary.review_count || 0),
                subtitle: 'All reviews (incl. unscored)'
            },
            {
                label: 'Advisors',
                value: WT.fmtInt(summary.advisor_count || 0)
            },
            {
                label: 'Under 20 Words',
                value: WT.pct(summary.pct_under_20_tokens || 0)
            },
            {
                label: 'Under 50 Words',
                value: WT.pct(summary.pct_under_50_tokens || 0)
            }
        ];

        container.innerHTML = kpis.map(kpi => WT.kpiHtml(kpi.label, kpi.value, kpi.subtitle)).join('');
    }

    /**
     * Render coverage KPIs
     */
    function renderCoverageKpis(data) {
        const container = document.getElementById('eda-coverage-kpis');
        if (!container || !data.coverage) return;

        const coverage = data.coverage;
        const kpis = [
            {
                label: 'Total Advisors',
                value: WT.fmtInt(coverage.total_advisors || 0)
            },
            {
                label: 'Under 3 Reviews',
                value: WT.pct(coverage.pct_under_3 || 0)
            },
            {
                label: 'Under 5 Reviews',
                value: WT.pct(coverage.pct_under_5 || 0)
            },
            {
                label: 'Under 10 Reviews',
                value: WT.pct(coverage.pct_under_10 || 0)
            }
        ];

        container.innerHTML = kpis.map(kpi => WT.kpiHtml(kpi.label, kpi.value)).join('');
    }

    // ==================== REVIEW DETAIL ====================

    /**
     * Fetch and display review detail
     */
    function fetchAndShowReviewDetail(reviewIdx) {
        const panel = document.getElementById('eda-review-detail');
        if (!panel) return;

        WT.showLoading(panel);
        panel.style.display = 'block';

        WT.api('eda/review/' + reviewIdx, {}, function(response) {
            if (!response || (typeof response === 'object' && Object.keys(response).length === 0)) {
                panel.innerHTML = buildReviewDetailClosable('<em>Review not found (index: ' + reviewIdx + ')</em>');
                return;
            }

            const review = response.data || response;
            const html = buildReviewDetailHtml(review);
            panel.innerHTML = html;

            // Re-attach close handler
            const closeBtn = panel.querySelector('.close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    panel.style.display = 'none';
                });
            }
        }, function() {
            panel.innerHTML = '<div class="error">Error loading review details</div>';
        });
    }

    /**
     * Build HTML for review detail panel
     * Handles fields from both reviews_clean.csv and review_dimension_scores.csv:
     *   reviews_clean: review_idx, advisor_id, advisor_name, review_text_raw, rating, token_count, review_date
     *   scored reviews: review_idx, advisor_id, advisor_name, review_text_raw, sim_trust_integrity, etc.
     */
    function buildReviewDetailHtml(review) {
        // Handle field names from either CSV source
        const reviewText = review.review_text_raw || review.text || review.review_text || '';
        const advisorName = review.advisor_name || review.entity_name || 'Unknown';
        const wordCount = reviewText.trim() ? reviewText.trim().split(/\s+/).length : 0;
        const reviewIdx = review.review_idx || '';
        const rating = review.rating ? parseFloat(review.rating) : null;
        const reviewDate = review.review_date || review.date || '';

        // Build dimension scores display (only if scored review)
        const dimScores = [
            { label: 'Trust & Integrity', field: 'sim_trust_integrity' },
            { label: 'Empathy', field: 'sim_listening_personalization' },
            { label: 'Communication', field: 'sim_communication_clarity' },
            { label: 'Responsiveness', field: 'sim_responsiveness_availability' },
            { label: 'Life Events', field: 'sim_life_event_support' },
            { label: 'Investment', field: 'sim_investment_expertise' },
        ];

        let scoresHtml = '';
        dimScores.forEach(dim => {
            const val = parseFloat(review[dim.field]);
            if (!isNaN(val)) {
                scoresHtml += `<span class="wt-dim-score">${dim.label}: ${val.toFixed(3)}</span> `;
            }
        });

        // Build meta items
        let metaItems = [];
        metaItems.push(`<span class="review-word-count">${WT.fmtInt(wordCount)} words</span>`);
        if (rating !== null && !isNaN(rating)) {
            metaItems.push(`<span class="review-rating">Rating: ${rating}</span>`);
        }
        if (reviewDate) {
            metaItems.push(`<span class="review-date">${escapeHtml(reviewDate)}</span>`);
        }

        return `
            <div class="review-detail-content">
                <div class="review-detail-header">
                    <div>
                        <h3>${escapeHtml(advisorName)}</h3>
                        <p class="review-detail-date">Review #${escapeHtml(String(reviewIdx))}</p>
                    </div>
                    <button class="close-btn">&times;</button>
                </div>

                <div class="review-detail-meta">
                    ${metaItems.join(' &middot; ')}
                </div>

                <div class="review-detail-text">
                    ${escapeHtml(reviewText) || '<em>No review text available</em>'}
                </div>

                ${scoresHtml ? '<div class="review-detail-scores">' + scoresHtml + '</div>' : ''}
            </div>
        `;
    }

    /**
     * Build a closable panel with custom content
     */
    function buildReviewDetailClosable(innerHtml) {
        return `
            <div class="review-detail-content">
                <div class="review-detail-header">
                    <div>${innerHtml}</div>
                    <button class="close-btn">&times;</button>
                </div>
            </div>
        `;
    }

    /**
     * Simple HTML escape helper
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // ==================== FILTERS ====================

    /**
     * Reset all filters to defaults
     */
    function resetFilters() {
        state.entityType = 'firm';
        state.entityId = null;
        state.dateStart = null;
        state.dateEnd = null;
        state.rating = null;
        state.tokenCats = ['all'];
        state.reviewCats = ['all'];
        state.ngramN = 1;
        state.topN = 30;
        state.excludeStopwords = true;
        state.customStopwords = '';
        state.timeFreq = 'month';

        // Update form controls
        document.querySelectorAll('#eda-entity-type').forEach(radio => {
            radio.checked = radio.value === 'firm';
        });

        document.getElementById('eda-entity-select').value = '';
        document.getElementById('eda-date-start').value = '';
        document.getElementById('eda-date-end').value = '';
        document.getElementById('eda-rating').value = '';
        document.getElementById('eda-token-cats').value = 'all';
        document.getElementById('eda-review-cats').value = 'all';
        document.getElementById('eda-ngram-n').value = '1';
        document.getElementById('eda-top-n').value = '30';
        document.getElementById('eda-exclude-stopwords').checked = true;
        document.getElementById('eda-custom-stopwords').value = '';
        document.getElementById('eda-time-freq').value = 'month';

        // Repopulate entity dropdown
        populateEntitySelect('firm');

        // Reload data
        loadEDAData();
    }

    // ==================== ERROR HANDLING ====================

    /**
     * Show error state on all chart containers
     */
    function showErrorState() {
        const containers = [
            '#eda-summary-kpis',
            '#eda-coverage-kpis',
            '#eda-chart-rating',
            '#eda-chart-timeline',
            '#eda-chart-reviews-per-advisor',
            '#eda-chart-tokens',
            '#eda-chart-rating-vs-token',
            '#eda-chart-lexical'
        ];

        containers.forEach(selector => {
            const el = document.querySelector(selector);
            if (el) {
                WT.emptyState(el, 'Error loading data');
            }
        });
    }

})();

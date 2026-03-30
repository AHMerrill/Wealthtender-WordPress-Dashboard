/**
 * Wealthtender Analytics — All Reviews Page
 *
 * Three-column layout with paginated review list, spider chart detail,
 * and always-visible reference panel (cosine-similarity legend +
 * canonical dimension query texts).
 *
 * Depends on: wt-common.js (WT namespace), Plotly, wtAnalytics localized data
 */

(function () {
	'use strict';

	/* ──────────────────── Constants ──────────────────── */
	var PAGE_SIZE   = 10;
	var dims        = wtAnalytics.dimensions;
	var dimLabels   = wtAnalytics.dimLabels;
	var dimShort    = wtAnalytics.dimShort;
	var dimColors   = wtAnalytics.dimColors;
	var colors      = wtAnalytics.colors;

	/* ──────────────────── State ──────────────────── */
	var currentPage   = 0;
	var totalReviews  = 0;
	var currentItems  = [];
	var selectedIdx   = null;

	/* ──────────────────── DOM refs ──────────────────── */
	var elHeader, elList, elPrev, elNext, elPageInfo, elDetail;

	/* ──────────────────── Init ──────────────────── */
	jQuery(document).ready(function () {
		elHeader   = document.getElementById('wt-ar-header');
		elList     = document.getElementById('wt-ar-list-container');
		elPrev     = document.getElementById('wt-ar-prev-btn');
		elNext     = document.getElementById('wt-ar-next-btn');
		elPageInfo = document.getElementById('wt-ar-page-info');
		elDetail   = document.getElementById('wt-ar-detail-panel');

		if (!elList) return; // not on this page

		elPrev.addEventListener('click', function () { changePage(-1); });
		elNext.addEventListener('click', function () { changePage(1); });

		fetchPage(0);
	});

	/* ──────────────────── Data fetching ──────────────────── */

	function fetchPage(page) {
		var offset = page * PAGE_SIZE;
		WT.api('reviews/all', { offset: offset, limit: PAGE_SIZE }, function (resp) {
			currentPage  = page;
			totalReviews = resp.total || 0;
			currentItems = resp.items || [];
			renderList();
			updatePagination();
		}, function () {
			elList.innerHTML = '<div class="wt-ar-loading">Failed to load reviews.</div>';
		});
	}

	function changePage(delta) {
		var lastPage = Math.max(0, Math.ceil(totalReviews / PAGE_SIZE) - 1);
		var newPage  = Math.max(0, Math.min(lastPage, currentPage + delta));
		if (newPage === currentPage) return;
		selectedIdx = null;
		fetchPage(newPage);
	}

	/* ──────────────────── Render list ──────────────────── */

	function renderList() {
		elList.innerHTML = '';

		if (!currentItems.length) {
			elList.innerHTML = '<div class="wt-ar-loading">No reviews available.</div>';
			return;
		}

		currentItems.forEach(function (r) {
			var item = buildListItem(r);
			elList.appendChild(item);
		});

		if (elHeader) {
			elHeader.textContent = 'Reviews (' + totalReviews + ')';
		}
	}

	function buildListItem(r) {
		var reviewIdx   = r.review_idx;
		var advisorName = r.advisor_name || 'Unknown Advisor';
		var parts       = [];
		if (r.reviewer_name) parts.push(r.reviewer_name);
		if (r.review_date)   parts.push(r.review_date);
		if (!parts.length)   parts.push('Review #' + reviewIdx);
		var subtitle = parts.join('  \u00b7  ');

		var isSelected = (selectedIdx !== null && reviewIdx === selectedIdx);

		var div = document.createElement('div');
		div.className = 'wt-ar-list-item' + (isSelected ? ' wt-ar-selected' : '');
		div.setAttribute('data-review-idx', reviewIdx);

		var nameEl = document.createElement('div');
		nameEl.className = 'wt-ar-item-name';
		nameEl.textContent = advisorName;

		var subEl = document.createElement('div');
		subEl.className = 'wt-ar-item-sub';
		subEl.textContent = subtitle;

		div.appendChild(nameEl);
		div.appendChild(subEl);

		div.addEventListener('click', function () {
			selectReview(reviewIdx);
		});

		return div;
	}

	function selectReview(reviewIdx) {
		selectedIdx = reviewIdx;

		// Update highlight
		var items = elList.querySelectorAll('.wt-ar-list-item');
		items.forEach(function (el) {
			var idx = parseInt(el.getAttribute('data-review-idx'), 10);
			if (idx === selectedIdx) {
				el.classList.add('wt-ar-selected');
			} else {
				el.classList.remove('wt-ar-selected');
			}
		});

		// Fetch detail
		loadReviewDetail(reviewIdx);
	}

	/* ──────────────────── Pagination ──────────────────── */

	function updatePagination() {
		var lastPage = Math.max(0, Math.ceil(totalReviews / PAGE_SIZE) - 1);
		var onFirst  = currentPage <= 0;
		var onLast   = currentPage >= lastPage;

		elPrev.disabled = onFirst;
		elNext.disabled = onLast;

		var startNum = currentPage * PAGE_SIZE + 1;
		var endNum   = Math.min(startNum + currentItems.length - 1, totalReviews);
		elPageInfo.textContent = startNum + '\u2013' + endNum + ' of ' + totalReviews;
	}

	/* ──────────────────── Detail panel ──────────────────── */

	function loadReviewDetail(reviewIdx) {
		elDetail.innerHTML = '<div class="wt-ar-loading">Loading&hellip;</div>';

		WT.api('advisor-dna/review/' + reviewIdx, {}, function (detail) {
			renderDetail(detail);
		}, function () {
			elDetail.innerHTML = '<div class="wt-ar-loading">Could not load review details.</div>';
		});
	}

	function renderDetail(detail) {
		var scores      = {};
		var advisorName = detail.advisor_name || 'Unknown';
		var reviewer    = detail.reviewer_name || 'Anonymous';
		var date        = detail.review_date || '';
		var text        = detail.review_text_raw || detail.review_text || '';

		dims.forEach(function (d) {
			// CSV columns are prefixed with sim_ (e.g. sim_trust_integrity)
			scores[d] = parseFloat(detail['sim_' + d]) || parseFloat(detail[d]) || 0;
		});

		var html = '';

		// Review text card
		html += '<div class="wt-ar-detail-card">';
		html +=   '<div class="wt-ar-detail-meta">';
		html +=     '<span class="wt-ar-detail-advisor">' + escHtml(advisorName) + '</span>';
		html +=     '<span class="wt-ar-detail-sub"> &middot; ' + escHtml(reviewer) + ' &middot; ' + escHtml(date) + '</span>';
		html +=   '</div>';
		html +=   '<div class="wt-ar-detail-text">' + escHtml(text) + '</div>';
		html += '</div>';

		// Spider chart container
		html += '<div class="wt-ar-detail-card">';
		html +=   '<div class="wt-ar-section-title">Dimension Scores</div>';
		html +=   '<div id="wt-ar-spider-chart" style="height:300px;"></div>';
		html += '</div>';

		// Score table
		html += '<div class="wt-ar-detail-card">';
		html +=   '<div class="wt-ar-section-title">Cosine Similarities</div>';
		html +=   buildScoreTable(scores);
		html += '</div>';

		elDetail.innerHTML = html;

		// Render Plotly spider chart
		renderSpider(scores);
	}

	/* ──────────────────── Spider chart ──────────────────── */

	function renderSpider(scores) {
		var labels = dims.map(function (d) { return dimShort[d] || d; });
		var values = dims.map(function (d) { return scores[d] || 0; });
		var markerColors = dims.map(function (d) { return dimColors[d]; });

		// Close the polygon
		var theta = labels.concat([labels[0]]);
		var r     = values.concat([values[0]]);
		var mc    = markerColors.concat([markerColors[0]]);

		var maxVal = Math.max.apply(null, values.concat([0.5]));

		var trace = {
			type: 'scatterpolar',
			r: r,
			theta: theta,
			fill: 'toself',
			fillcolor: 'rgba(0, 76, 140, 0.12)',
			line: { color: colors.blue || '#004C8C', width: 2 },
			marker: { size: 6, color: mc },
			hovertemplate: '%{theta}: %{r:.4f}<extra></extra>'
		};

		var layout = {
			polar: {
				radialaxis: {
					visible: true,
					range: [0, maxVal * 1.15],
					tickfont: { size: 9 }
				},
				angularaxis: {
					tickfont: { size: 10 }
				}
			},
			showlegend: false,
			margin: { l: 50, r: 50, t: 20, b: 20 },
			height: 300,
			paper_bgcolor: 'white'
		};

		var el = document.getElementById('wt-ar-spider-chart');
		if (el) {
			Plotly.newPlot(el, [trace], layout, { displayModeBar: false });
		}
	}

	/* ──────────────────── Score table ──────────────────── */

	function buildScoreTable(scores) {
		var rows = '';
		dims.forEach(function (d) {
			var val = scores[d];
			var display = (val !== null && val !== undefined) ? val.toFixed(4) : '\u2014';
			rows += '<div class="wt-ar-score-row">';
			rows +=   '<span class="wt-ar-score-label" style="color:' + (dimColors[d] || '#333') + ';">' + escHtml(dimLabels[d] || d) + '</span>';
			rows +=   '<span class="wt-ar-score-value">' + display + '</span>';
			rows += '</div>';
		});
		return rows;
	}

	/* ──────────────────── Helpers ──────────────────── */

	function escHtml(str) {
		if (!str) return '';
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

})();

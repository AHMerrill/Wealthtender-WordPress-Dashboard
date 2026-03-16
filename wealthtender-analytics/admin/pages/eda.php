<?php
/**
 * Exploratory Data Analysis (EDA) Page Template
 * Displays interactive analysis of review text patterns and trends
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wt-analytics-wrap wt-eda-page">

	<!-- Page Header -->
	<h1><?php esc_html_e( 'Exploratory Data Analysis', 'wealthtender-analytics' ); ?></h1>

	<div class="wt-eda-layout">

		<!-- Left Sidebar: Filters -->
		<aside class="wt-eda-filters">

			<h3><?php esc_html_e( 'Filters', 'wealthtender-analytics' ); ?></h3>

			<!-- Entity Type -->
			<div class="wt-filter-group">
				<label><?php esc_html_e( 'Entity Type', 'wealthtender-analytics' ); ?></label>
				<div class="wt-radio-group" id="eda-entity-type">
					<label><input type="radio" name="entity_type" value="firm" checked /> <?php esc_html_e( 'Firm', 'wealthtender-analytics' ); ?></label>
					<label><input type="radio" name="entity_type" value="advisor" /> <?php esc_html_e( 'Advisor', 'wealthtender-analytics' ); ?></label>
				</div>
			</div>

			<!-- Entity Search -->
			<div class="wt-filter-group">
				<label for="eda-entity-select"><?php esc_html_e( 'Entity', 'wealthtender-analytics' ); ?></label>
				<select id="eda-entity-select" class="wt-select">
					<option value=""><?php esc_html_e( 'All Entities', 'wealthtender-analytics' ); ?></option>
				</select>
			</div>

			<!-- Date Range -->
			<div class="wt-filter-group">
				<label><?php esc_html_e( 'Date Range', 'wealthtender-analytics' ); ?></label>
				<input type="date" id="eda-date-start" class="wt-input" placeholder="Start Date" />
				<input type="date" id="eda-date-end" class="wt-input" placeholder="End Date" />
			</div>

			<!-- Rating Filter -->
			<div class="wt-filter-group">
				<label for="eda-rating"><?php esc_html_e( 'Rating', 'wealthtender-analytics' ); ?></label>
				<select id="eda-rating" class="wt-select">
					<option value=""><?php esc_html_e( 'All Ratings', 'wealthtender-analytics' ); ?></option>
					<option value="1">1 Star</option>
					<option value="2">2 Stars</option>
					<option value="3">3 Stars</option>
					<option value="4">4 Stars</option>
					<option value="5">5 Stars</option>
				</select>
			</div>

			<!-- Token Count Categories -->
			<div class="wt-filter-group">
				<label><?php esc_html_e( 'Token Count', 'wealthtender-analytics' ); ?></label>
				<div class="wt-checkbox-group" id="eda-token-cats">
					<label><input type="checkbox" value="low" checked /> <?php esc_html_e( 'Low', 'wealthtender-analytics' ); ?></label>
					<label><input type="checkbox" value="medium" checked /> <?php esc_html_e( 'Medium', 'wealthtender-analytics' ); ?></label>
					<label><input type="checkbox" value="high" checked /> <?php esc_html_e( 'High', 'wealthtender-analytics' ); ?></label>
				</div>
			</div>

			<!-- Reviews per Advisor -->
			<div class="wt-filter-group">
				<label><?php esc_html_e( 'Reviews per Advisor', 'wealthtender-analytics' ); ?></label>
				<div class="wt-checkbox-group" id="eda-review-cats">
					<label><input type="checkbox" value="low" checked /> <?php esc_html_e( 'Low', 'wealthtender-analytics' ); ?></label>
					<label><input type="checkbox" value="medium" checked /> <?php esc_html_e( 'Medium', 'wealthtender-analytics' ); ?></label>
					<label><input type="checkbox" value="high" checked /> <?php esc_html_e( 'High', 'wealthtender-analytics' ); ?></label>
				</div>
			</div>

			<!-- N-gram Size -->
			<div class="wt-filter-group">
				<label for="eda-ngram-n"><?php esc_html_e( 'N-Gram Size', 'wealthtender-analytics' ); ?></label>
				<select id="eda-ngram-n" class="wt-select">
					<option value="1">1</option>
					<option value="2" selected>2</option>
					<option value="3">3</option>
					<option value="4">4</option>
					<option value="5">5</option>
				</select>
			</div>

			<!-- Top N -->
			<div class="wt-filter-group">
				<label for="eda-top-n"><?php esc_html_e( 'Top N', 'wealthtender-analytics' ); ?></label>
				<select id="eda-top-n" class="wt-select">
					<option value="10">10</option>
					<option value="20" selected>20</option>
					<option value="30">30</option>
					<option value="50">50</option>
					<option value="100">100</option>
				</select>
			</div>

			<!-- Stopwords -->
			<div class="wt-filter-group">
				<label><input type="checkbox" id="eda-exclude-stopwords" checked /> <?php esc_html_e( 'Exclude Stopwords', 'wealthtender-analytics' ); ?></label>
			</div>

			<!-- Custom Stopwords -->
			<div class="wt-filter-group">
				<label for="eda-custom-stopwords"><?php esc_html_e( 'Custom Stopwords', 'wealthtender-analytics' ); ?></label>
				<textarea id="eda-custom-stopwords" class="wt-textarea" placeholder="comma,separated,list"></textarea>
			</div>

			<!-- Time Frequency -->
			<div class="wt-filter-group">
				<label for="eda-time-freq"><?php esc_html_e( 'Time Frequency', 'wealthtender-analytics' ); ?></label>
				<select id="eda-time-freq" class="wt-select">
					<option value="month" selected><?php esc_html_e( 'Month', 'wealthtender-analytics' ); ?></option>
					<option value="quarter"><?php esc_html_e( 'Quarter', 'wealthtender-analytics' ); ?></option>
					<option value="year"><?php esc_html_e( 'Year', 'wealthtender-analytics' ); ?></option>
				</select>
			</div>

			<!-- Reset Button -->
			<div class="wt-filter-group">
				<button id="eda-reset-btn" class="wt-btn wt-btn-secondary wt-btn-block">
					<?php esc_html_e( 'Reset Filters', 'wealthtender-analytics' ); ?>
				</button>
			</div>

		</aside>

		<!-- Main Content Area -->
		<main class="wt-eda-content">

			<!-- KPI Row: Summary Stats -->
			<div id="eda-summary-kpis" class="wt-kpi-row">
				<!-- Populated by JavaScript -->
			</div>

			<!-- Coverage KPIs -->
			<div id="eda-coverage-kpis" class="wt-kpi-row">
				<!-- Populated by JavaScript -->
			</div>

			<!-- Chart Grid -->
			<div class="wt-chart-grid wt-chart-grid-2col">

				<!-- Rating Distribution -->
				<div class="wt-chart-container">
					<h3><?php esc_html_e( 'Rating Distribution', 'wealthtender-analytics' ); ?></h3>
					<div id="eda-chart-rating" class="wt-plot" style="min-height: 400px;"></div>
				</div>

				<!-- Reviews Over Time -->
				<div class="wt-chart-container">
					<h3><?php esc_html_e( 'Reviews Over Time', 'wealthtender-analytics' ); ?></h3>
					<div id="eda-chart-timeline" class="wt-plot" style="min-height: 400px;"></div>
				</div>

				<!-- Reviews per Advisor -->
				<div class="wt-chart-container">
					<h3><?php esc_html_e( 'Reviews per Advisor', 'wealthtender-analytics' ); ?></h3>
					<div id="eda-chart-reviews-per-advisor" class="wt-plot" style="min-height: 400px;"></div>
				</div>

				<!-- Review Length Distribution -->
				<div class="wt-chart-container">
					<h3><?php esc_html_e( 'Review Length Distribution', 'wealthtender-analytics' ); ?></h3>
					<div id="eda-chart-tokens" class="wt-plot" style="min-height: 400px;"></div>
				</div>

				<!-- Rating vs Review Length -->
				<div class="wt-chart-container">
					<h3><?php esc_html_e( 'Rating vs Review Length', 'wealthtender-analytics' ); ?></h3>
					<div id="eda-chart-rating-vs-token" class="wt-plot" style="min-height: 400px;"></div>
				</div>

				<!-- Top N-Grams -->
				<div class="wt-chart-container">
					<h3><?php esc_html_e( 'Top N-Grams', 'wealthtender-analytics' ); ?></h3>
					<div id="eda-chart-lexical" class="wt-plot" style="min-height: 400px;"></div>
				</div>

			</div>

		</main>

	</div>

	<!-- Review Detail Panel -->
	<div id="eda-review-detail" class="wt-review-detail-panel" style="display: none;">
		<div class="wt-panel-header">
			<h3><?php esc_html_e( 'Review Detail', 'wealthtender-analytics' ); ?></h3>
			<button class="wt-btn-close" data-close="eda-review-detail">&times;</button>
		</div>
		<div class="wt-panel-content">
			<!-- Populated by JavaScript -->
		</div>
	</div>

</div>

<script type="text/javascript">
	document.addEventListener( 'DOMContentLoaded', function() {
		if ( typeof WT !== 'undefined' && WT.initEDA ) {
			WT.initEDA();
		}
	} );
</script>

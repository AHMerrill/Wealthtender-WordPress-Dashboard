<?php
/**
 * Leaderboard Page Template
 * Displays top-performing advisors across all dimensions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wt-analytics-wrap wt-leaderboard-page">

	<!-- Page Header -->
	<h1><?php esc_html_e( 'Leaderboard', 'wealthtender-analytics' ); ?></h1>

	<!-- Controls Row -->
	<div class="wt-controls-row wt-lb-controls">

		<!-- Dimension Selector -->
		<div class="wt-control-group">
			<label for="lb-dim-select"><?php esc_html_e( 'Dimension', 'wealthtender-analytics' ); ?></label>
			<select id="lb-dim-select" class="wt-select">
				<option value="composite"><?php esc_html_e( 'Composite', 'wealthtender-analytics' ); ?></option>
				<option value="trust_integrity"><?php esc_html_e( 'Trust & Integrity', 'wealthtender-analytics' ); ?></option>
				<option value="empathy"><?php esc_html_e( 'Customer Empathy', 'wealthtender-analytics' ); ?></option>
				<option value="communication"><?php esc_html_e( 'Communication Clarity', 'wealthtender-analytics' ); ?></option>
				<option value="responsiveness"><?php esc_html_e( 'Responsiveness', 'wealthtender-analytics' ); ?></option>
				<option value="life_events"><?php esc_html_e( 'Life Event Support', 'wealthtender-analytics' ); ?></option>
				<option value="investment_expertise"><?php esc_html_e( 'Investment Expertise', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

		<!-- Method Selector -->
		<div class="wt-control-group">
			<label for="lb-method-select"><?php esc_html_e( 'Method', 'wealthtender-analytics' ); ?></label>
			<select id="lb-method-select" class="wt-select">
				<option value="mean"><?php esc_html_e( 'Mean', 'wealthtender-analytics' ); ?></option>
				<option value="penalized"><?php esc_html_e( 'Penalized', 'wealthtender-analytics' ); ?></option>
				<option value="weighted"><?php esc_html_e( 'Weighted', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

		<!-- Entity Type -->
		<div class="wt-control-group">
			<label for="lb-entity-type"><?php esc_html_e( 'Entity Type', 'wealthtender-analytics' ); ?></label>
			<select id="lb-entity-type" class="wt-select">
				<option value=""><?php esc_html_e( 'All', 'wealthtender-analytics' ); ?></option>
				<option value="firm"><?php esc_html_e( 'Firm', 'wealthtender-analytics' ); ?></option>
				<option value="advisor"><?php esc_html_e( 'Advisor', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

		<!-- Pool Selector -->
		<div class="wt-control-group">
			<label for="lb-pool-select"><?php esc_html_e( 'Pool', 'wealthtender-analytics' ); ?></label>
			<select id="lb-pool-select" class="wt-select">
				<option value="0"><?php esc_html_e( 'All', 'wealthtender-analytics' ); ?></option>
				<option value="20"><?php esc_html_e( 'Premier (20+ reviews)', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

		<!-- Top N Selector -->
		<div class="wt-control-group">
			<label for="lb-top-n"><?php esc_html_e( 'Top N', 'wealthtender-analytics' ); ?></label>
			<select id="lb-top-n" class="wt-select">
				<option value="5" selected>5</option>
				<option value="10">10</option>
				<option value="15">15</option>
				<option value="20">20</option>
				<option value="25">25</option>
			</select>
		</div>

	</div>

	<!-- Main Chart -->
	<div class="wt-chart-container wt-chart-container-full">
		<div id="lb-chart" class="wt-plot" style="min-height: 600px;"></div>
	</div>

	<!-- Comparison Panel (Hidden until Entities Selected) -->
	<div id="lb-comparison-section" class="wt-comparison-section" style="display: none;">

		<h2><?php esc_html_e( 'Selected Entities Comparison', 'wealthtender-analytics' ); ?></h2>

		<div class="wt-lb-comparison-row">
			<!-- Spider Chart (left) -->
			<div class="wt-lb-comparison-spider">
				<div id="lb-comparison-spider" class="wt-plot" style="min-height: 450px;"></div>
			</div>

			<!-- Comparison Table (right) -->
			<div class="wt-lb-comparison-table-wrap">
				<table id="lb-comparison-table" class="lb-comparison-table">
					<thead></thead>
					<tbody></tbody>
				</table>
			</div>
		</div>

	</div>

</div>

<script type="text/javascript">
	document.addEventListener( 'DOMContentLoaded', function() {
		if ( typeof WT !== 'undefined' && WT.initLeaderboard ) {
			WT.initLeaderboard();
		}
	} );
</script>

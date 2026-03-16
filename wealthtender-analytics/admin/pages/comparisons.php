<?php
/**
 * Comparisons Page Template
 * Enables head-to-head and team comparisons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wt-analytics-wrap wt-comparisons-page">

	<!-- Page Header -->
	<h1><?php esc_html_e( 'Comparisons', 'wealthtender-analytics' ); ?></h1>

	<!-- ==== SECTION 1: HEAD-TO-HEAD ==== -->
	<div class="wt-comparison-section wt-h2h-section">

		<h2><?php esc_html_e( 'Head-to-Head Comparison', 'wealthtender-analytics' ); ?></h2>

		<!-- Controls Row -->
		<div class="wt-controls-row wt-h2h-controls">

			<!-- Entity Type -->
			<div class="wt-control-group">
				<label for="comp-h2h-entity-type"><?php esc_html_e( 'Entity Type', 'wealthtender-analytics' ); ?></label>
				<select id="comp-h2h-entity-type" class="wt-select">
					<option value=""><?php esc_html_e( 'All', 'wealthtender-analytics' ); ?></option>
					<option value="firm"><?php esc_html_e( 'Firm', 'wealthtender-analytics' ); ?></option>
					<option value="advisor"><?php esc_html_e( 'Advisor', 'wealthtender-analytics' ); ?></option>
				</select>
			</div>

			<!-- Method Selector -->
			<div class="wt-control-group">
				<label for="comp-h2h-method"><?php esc_html_e( 'Method', 'wealthtender-analytics' ); ?></label>
				<select id="comp-h2h-method" class="wt-select">
					<option value="mean"><?php esc_html_e( 'Mean', 'wealthtender-analytics' ); ?></option>
					<option value="penalized"><?php esc_html_e( 'Penalized', 'wealthtender-analytics' ); ?></option>
					<option value="weighted"><?php esc_html_e( 'Weighted', 'wealthtender-analytics' ); ?></option>
				</select>
			</div>

			<!-- Pool Selector -->
			<div class="wt-control-group">
				<label for="comp-h2h-pool"><?php esc_html_e( 'Pool', 'wealthtender-analytics' ); ?></label>
				<select id="comp-h2h-pool" class="wt-select">
					<option value="0"><?php esc_html_e( 'All', 'wealthtender-analytics' ); ?></option>
					<option value="20"><?php esc_html_e( 'Premier (20+ reviews)', 'wealthtender-analytics' ); ?></option>
				</select>
			</div>

		</div>

		<!-- Entity Selection Row -->
		<div class="wt-entity-selection-row">

			<div class="wt-entity-selector">
				<label for="comp-entity-a"><?php esc_html_e( 'Entity A', 'wealthtender-analytics' ); ?></label>
				<select id="comp-entity-a" class="wt-select">
					<option value=""><?php esc_html_e( 'Select entity...', 'wealthtender-analytics' ); ?></option>
				</select>
			</div>

			<div class="wt-vs-divider"><?php esc_html_e( 'vs', 'wealthtender-analytics' ); ?></div>

			<div class="wt-entity-selector">
				<label for="comp-entity-b"><?php esc_html_e( 'Entity B', 'wealthtender-analytics' ); ?></label>
				<select id="comp-entity-b" class="wt-select">
					<option value=""><?php esc_html_e( 'Select entity...', 'wealthtender-analytics' ); ?></option>
				</select>
			</div>

		</div>

		<!-- Comparison Content -->
		<div class="wt-h2h-content">

			<!-- Spider Chart -->
			<div class="wt-chart-container">
				<div id="comp-h2h-spider" class="wt-plot" style="min-height: 500px;"></div>
			</div>

			<!-- Comparison Table -->
			<div class="wt-table-container">
				<table id="comp-h2h-table" class="wt-data-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Dimension', 'wealthtender-analytics' ); ?></th>
							<th><?php esc_html_e( 'Entity A', 'wealthtender-analytics' ); ?></th>
							<th><?php esc_html_e( 'Entity B', 'wealthtender-analytics' ); ?></th>
							<th><?php esc_html_e( 'Difference', 'wealthtender-analytics' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<!-- Populated by JavaScript -->
					</tbody>
				</table>
			</div>

		</div>

	</div>

	<!-- Team comparisons moved to dedicated "Team" page -->

</div>

<script type="text/javascript">
	document.addEventListener( 'DOMContentLoaded', function() {
		if ( typeof WT !== 'undefined' && WT.initComparisons ) {
			WT.initComparisons();
		}
	} );
</script>

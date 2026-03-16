<?php
/**
 * Team Comparisons Page Template
 * Compare members within a partner group
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wt-analytics-wrap wt-team-comparisons-page">

	<!-- Page Header -->
	<h1><?php esc_html_e( 'Team Comparisons', 'wealthtender-analytics' ); ?></h1>

	<!-- Development Banner -->
	<div class="wt-dev-banner">
		<p><?php esc_html_e( 'Partner group associations are currently mocked for development purposes.', 'wealthtender-analytics' ); ?></p>
	</div>

	<!-- Controls Row -->
	<div class="wt-controls-row wt-team-controls">

		<!-- Partner Group Selector -->
		<div class="wt-control-group">
			<label for="team-group-select"><?php esc_html_e( 'Partner Group', 'wealthtender-analytics' ); ?></label>
			<select id="team-group-select" class="wt-select">
				<option value=""><?php esc_html_e( 'Select a group...', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

		<!-- Method Selector -->
		<div class="wt-control-group">
			<label for="team-method-select"><?php esc_html_e( 'Method', 'wealthtender-analytics' ); ?></label>
			<select id="team-method-select" class="wt-select">
				<option value="mean"><?php esc_html_e( 'Mean', 'wealthtender-analytics' ); ?></option>
				<option value="penalized"><?php esc_html_e( 'Penalized', 'wealthtender-analytics' ); ?></option>
				<option value="weighted"><?php esc_html_e( 'Weighted', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

	</div>

	<!-- Comparison Content -->
	<div class="wt-team-content">

		<!-- Spider Chart -->
		<div class="wt-chart-container">
			<div id="team-comp-spider" class="wt-plot" style="min-height: 500px;"></div>
		</div>

		<!-- Bar Chart -->
		<div class="wt-chart-container">
			<div id="team-comp-bars" class="wt-plot" style="min-height: 500px;"></div>
		</div>

	</div>

</div>

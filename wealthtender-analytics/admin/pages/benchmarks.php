<?php
/**
 * Benchmarks Page Template
 * Compares entity scores against peer distributions and percentiles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wt-analytics-wrap wt-benchmarks-page">

	<!-- Page Header -->
	<h1><?php esc_html_e( 'Benchmarks', 'wealthtender-analytics' ); ?></h1>

	<!-- Controls Row -->
	<div class="wt-controls-row wt-bench-controls">

		<!-- Method Selector -->
		<div class="wt-control-group">
			<label for="bench-method-select"><?php esc_html_e( 'Method', 'wealthtender-analytics' ); ?></label>
			<select id="bench-method-select" class="wt-select">
				<option value="mean"><?php esc_html_e( 'Mean', 'wealthtender-analytics' ); ?></option>
				<option value="penalized"><?php esc_html_e( 'Penalized', 'wealthtender-analytics' ); ?></option>
				<option value="weighted"><?php esc_html_e( 'Weighted', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

		<!-- Entity Type -->
		<div class="wt-control-group">
			<label for="bench-entity-type"><?php esc_html_e( 'Entity Type', 'wealthtender-analytics' ); ?></label>
			<select id="bench-entity-type" class="wt-select">
				<option value=""><?php esc_html_e( 'All', 'wealthtender-analytics' ); ?></option>
				<option value="firm"><?php esc_html_e( 'Firm', 'wealthtender-analytics' ); ?></option>
				<option value="advisor"><?php esc_html_e( 'Advisor', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

		<!-- Pool Selector -->
		<div class="wt-control-group">
			<label for="bench-pool-select"><?php esc_html_e( 'Pool', 'wealthtender-analytics' ); ?></label>
			<select id="bench-pool-select" class="wt-select">
				<option value="0"><?php esc_html_e( 'All', 'wealthtender-analytics' ); ?></option>
				<option value="20"><?php esc_html_e( 'Premier (20+ reviews)', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

		<!-- Entity Search -->
		<div class="wt-control-group">
			<label for="bench-entity-select"><?php esc_html_e( 'Entity', 'wealthtender-analytics' ); ?></label>
			<select id="bench-entity-select" class="wt-select">
				<option value=""><?php esc_html_e( 'Select an entity...', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

	</div>

	<!-- Pool Composition KPIs -->
	<div id="bench-pool-kpis" class="wt-pool-kpi-grid">
		<!-- Populated by JavaScript -->
	</div>

	<!-- Dimension Histograms Grid -->
	<div class="wt-chart-grid wt-chart-grid-3col">
		<?php
		// Get dimension keys from constants
		$dimensions = wt_get_dimensions();

		foreach ( $dimensions as $dim_key ) {
			?>
			<div class="wt-chart-container">
				<div id="bench-hist-<?php echo esc_attr( $dim_key ); ?>" class="wt-plot" style="min-height: 400px;"></div>
			</div>
			<?php
		}
		?>
	</div>

	<!-- Comparison Section (Hidden until Entity Selected) -->
	<div id="bench-comparison-section" class="wt-comparison-section" style="display: none;">

		<h2><?php esc_html_e( 'Peer Comparison', 'wealthtender-analytics' ); ?></h2>

		<div class="wt-table-container">
			<table id="bench-comparison-table" class="wt-data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Dimension', 'wealthtender-analytics' ); ?></th>
						<th><?php esc_html_e( 'Your Score', 'wealthtender-analytics' ); ?></th>
						<th><?php esc_html_e( 'Percentile', 'wealthtender-analytics' ); ?></th>
						<th><?php esc_html_e( 'P25', 'wealthtender-analytics' ); ?></th>
						<th><?php esc_html_e( 'P50', 'wealthtender-analytics' ); ?></th>
						<th><?php esc_html_e( 'P75', 'wealthtender-analytics' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<!-- Populated by JavaScript -->
				</tbody>
			</table>
		</div>

	</div>

</div>

<script type="text/javascript">
	document.addEventListener( 'DOMContentLoaded', function() {
		if ( typeof WT !== 'undefined' && WT.initBenchmarks ) {
			WT.initBenchmarks();
		}
	} );
</script>

<?php
/**
 * Advisor DNA Page Template
 * Displays dimension scores and profiles for advisors and entities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wt-analytics-wrap wt-advisor-dna-page">

	<!-- Page Header -->
	<h1><?php esc_html_e( 'Advisor DNA', 'wealthtender-analytics' ); ?></h1>

	<!-- Controls Row -->
	<div class="wt-controls-row wt-dna-controls">

		<!-- View Toggle: Macro | Entity -->
		<div class="wt-control-group wt-view-toggle">
			<label><?php esc_html_e( 'View', 'wealthtender-analytics' ); ?></label>
			<div class="wt-button-group">
				<button class="wt-btn wt-view-btn wt-view-macro active" data-view="macro">
					<?php esc_html_e( 'Macro', 'wealthtender-analytics' ); ?>
				</button>
				<button class="wt-btn wt-view-btn wt-view-entity" data-view="entity">
					<?php esc_html_e( 'Entity', 'wealthtender-analytics' ); ?>
				</button>
			</div>
		</div>

		<!-- Method Selector -->
		<div class="wt-control-group">
			<label for="dna-method-select"><?php esc_html_e( 'Method', 'wealthtender-analytics' ); ?></label>
			<select id="dna-method-select" class="wt-select">
				<option value="mean"><?php esc_html_e( 'Mean', 'wealthtender-analytics' ); ?></option>
				<option value="penalized"><?php esc_html_e( 'Penalized', 'wealthtender-analytics' ); ?></option>
				<option value="weighted"><?php esc_html_e( 'Weighted', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

		<!-- Pool Selector -->
		<div class="wt-control-group">
			<label for="dna-pool-select"><?php esc_html_e( 'Pool', 'wealthtender-analytics' ); ?></label>
			<select id="dna-pool-select" class="wt-select">
				<option value="0"><?php esc_html_e( 'All', 'wealthtender-analytics' ); ?></option>
				<option value="20"><?php esc_html_e( 'Premier (20+ reviews)', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

		<!-- Chart Type Toggle -->
		<div class="wt-control-group wt-chart-type-toggle">
			<label><?php esc_html_e( 'Chart Type', 'wealthtender-analytics' ); ?></label>
			<div class="wt-button-group">
				<button class="wt-btn wt-chart-type-btn wt-chart-spider active" data-chart-type="spider">
					<?php esc_html_e( 'Spider', 'wealthtender-analytics' ); ?>
				</button>
				<button class="wt-btn wt-chart-type-btn wt-chart-bars" data-chart-type="bars">
					<?php esc_html_e( 'Bars', 'wealthtender-analytics' ); ?>
				</button>
			</div>
		</div>

		<!-- Entity Search (Hidden in Macro View) -->
		<div class="wt-control-group wt-entity-search-group" style="display: none;">
			<label for="dna-entity-select"><?php esc_html_e( 'Entity', 'wealthtender-analytics' ); ?></label>
			<select id="dna-entity-select" class="wt-select">
				<option value=""><?php esc_html_e( 'Select an entity...', 'wealthtender-analytics' ); ?></option>
			</select>
		</div>

	</div>

	<!-- MACRO VIEW SECTION -->
	<div id="dna-macro-section" class="wt-view-section wt-view-active">

		<!-- KPI Row -->
		<div id="dna-macro-kpis" class="wt-kpi-row">
			<!-- Populated by JavaScript -->
		</div>

		<!-- Chart Container -->
		<div class="wt-chart-container">
			<div id="dna-macro-chart" class="wt-plot" style="min-height: 500px;"></div>
		</div>

		<!-- Dimension Description Cards -->
		<div class="wt-dimension-cards-grid">
			<?php
			// Canonical text is sourced from wt_get_dim_query_texts() in
			// includes/constants.php so dimension queries have one source
			// of truth across the plugin (keyed by the full dimension ID).
			$dim_query_texts = wt_get_dim_query_texts();

			$dimensions = [
				[
					'key'         => 'trust_integrity',
					'full_key'    => 'trust_integrity',
					'name'        => __( 'Trust & Integrity', 'wealthtender-analytics' ),
					'description' => __( 'Fiduciary duty, honesty, transparency, and reliability', 'wealthtender-analytics' ),
				],
				[
					'key'         => 'empathy',
					'full_key'    => 'listening_personalization',
					'name'        => __( 'Customer Empathy', 'wealthtender-analytics' ),
					'description' => __( 'Listens, understands unique goals, custom tailored advice', 'wealthtender-analytics' ),
				],
				[
					'key'         => 'communication',
					'full_key'    => 'communication_clarity',
					'name'        => __( 'Communication Clarity', 'wealthtender-analytics' ),
					'description' => __( 'Explains concepts simply, plain English, avoids jargon', 'wealthtender-analytics' ),
				],
				[
					'key'         => 'responsiveness',
					'full_key'    => 'responsiveness_availability',
					'name'        => __( 'Responsiveness', 'wealthtender-analytics' ),
					'description' => __( 'Always accessible, quick replies, available during crises', 'wealthtender-analytics' ),
				],
				[
					'key'         => 'life_events',
					'full_key'    => 'life_event_support',
					'name'        => __( 'Life Event Support', 'wealthtender-analytics' ),
					'description' => __( 'Compassionate guidance through major life transitions', 'wealthtender-analytics' ),
				],
				[
					'key'         => 'investment_expertise',
					'full_key'    => 'investment_expertise',
					'name'        => __( 'Investment Expertise', 'wealthtender-analytics' ),
					'description' => __( 'Technical proficiency, asset allocation, tax strategies', 'wealthtender-analytics' ),
				],
				[
					'key'         => 'outcomes_results',
					'full_key'    => 'outcomes_results',
					'name'        => __( 'Outcomes & Results', 'wealthtender-analytics' ),
					'description' => __( 'Tangible results and measurable progress toward real-world financial goals', 'wealthtender-analytics' ),
				],
			];

			foreach ( $dimensions as $dim ) {
				$canonical = isset( $dim_query_texts[ $dim['full_key'] ] )
					? $dim_query_texts[ $dim['full_key'] ]
					: '';
				?>
				<div class="wt-dimension-card" data-dimension="<?php echo esc_attr( $dim['key'] ); ?>">
					<div class="wt-dim-color-indicator"></div>
					<h4 class="wt-dim-name"><?php echo esc_html( $dim['name'] ); ?></h4>
					<p class="wt-dim-desc"><?php echo esc_html( $dim['description'] ); ?></p>
					<?php if ( ! empty( $canonical ) ) : ?>
						<p class="wt-dim-canonical"><strong>Canonical Scoring Reference:</strong> <?php echo esc_html( $canonical ); ?></p>
					<?php endif; ?>
				</div>
				<?php
			}
			?>
		</div>

	</div>

	<!-- ENTITY VIEW SECTION -->
	<div id="dna-entity-section" class="wt-view-section" style="display: none;">

		<!-- Entity Info Header -->
		<div id="dna-entity-info" class="wt-entity-info">
			<!-- Populated by JavaScript -->
		</div>

		<!-- Chart Container -->
		<div class="wt-chart-container">
			<div id="dna-entity-chart" class="wt-plot" style="min-height: 500px;"></div>
		</div>

		<!-- Scores Table -->
		<div class="wt-table-container">
			<table id="dna-entity-scores" class="wt-data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Dimension', 'wealthtender-analytics' ); ?></th>
						<th><?php esc_html_e( 'Score', 'wealthtender-analytics' ); ?></th>
						<th><?php esc_html_e( 'Percentile', 'wealthtender-analytics' ); ?></th>
						<th><?php esc_html_e( 'Tier', 'wealthtender-analytics' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<!-- Populated by JavaScript -->
				</tbody>
			</table>
		</div>

		<!-- Review List Section -->
		<div id="dna-entity-reviews" class="wt-reviews-section">
			<h3><?php esc_html_e( 'Top Reviews by Dimension', 'wealthtender-analytics' ); ?></h3>
			<div class="wt-review-cards">
				<!-- Populated by JavaScript -->
			</div>
		</div>

	</div>

</div>

<script type="text/javascript">
	// Import page-specific JS
	document.addEventListener( 'DOMContentLoaded', function() {
		if ( typeof WT !== 'undefined' && WT.initAdvisorDNA ) {
			WT.initAdvisorDNA();
		}
	} );
</script>

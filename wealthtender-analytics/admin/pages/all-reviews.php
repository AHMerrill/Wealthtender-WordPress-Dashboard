<?php
/**
 * All Reviews Page Template
 *
 * Three-column layout:
 *   Left   – paginated list of reviews (10 per page) with Prev / Next
 *   Middle – selected review text, spider chart, and score table
 *   Right  – cosine-similarity legend + all 7 canonical dimension query texts
 *
 * @package Wealthtender_Analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dim_labels      = wt_get_dim_labels();
$dim_short       = wt_get_dim_short();
$dim_colors      = wt_get_dim_colors();
$dim_descriptions = wt_get_dim_descriptions();
$dim_query_texts = wt_get_dim_query_texts();
$colors          = wt_get_colors();
?>

<div class="wrap wt-analytics-wrap wt-all-reviews-page">

	<h1><?php esc_html_e( 'All Reviews', 'wealthtender-analytics' ); ?></h1>

	<!-- Three-column grid -->
	<div class="wt-all-reviews-grid">

		<!-- ===== LEFT: Review list + pagination ===== -->
		<div class="wt-all-reviews-left">
			<div class="wt-ar-list-header" id="wt-ar-header">Reviews</div>
			<div class="wt-ar-list-container" id="wt-ar-list-container">
				<div class="wt-ar-loading">Loading reviews&hellip;</div>
			</div>
			<div class="wt-ar-pagination" id="wt-ar-pagination">
				<button class="wt-btn wt-ar-prev-btn" id="wt-ar-prev-btn" disabled>Prev</button>
				<span class="wt-ar-page-info" id="wt-ar-page-info"></span>
				<button class="wt-btn wt-ar-next-btn" id="wt-ar-next-btn" disabled>Next</button>
			</div>
		</div>

		<!-- ===== MIDDLE: Detail panel ===== -->
		<div class="wt-all-reviews-middle" id="wt-ar-detail-panel">
			<div class="wt-ar-detail-placeholder">
				Select a review from the list to view its details.
			</div>
		</div>

		<!-- ===== RIGHT: Reference panel (always visible) ===== -->
		<div class="wt-all-reviews-right">

			<!-- Cosine Similarity Legend -->
			<div class="wt-ar-legend-card">
				<div class="wt-ar-legend-title">Cosine Similarity Scale</div>
				<div class="wt-ar-legend-bands">
					<div class="wt-ar-legend-band" style="background: #F0FFF4;">
						<span class="wt-ar-band-range" style="color: #276749;">0.50 +</span>
						<span class="wt-ar-band-meaning" style="color: #276749;">Very strong alignment</span>
					</div>
					<div class="wt-ar-legend-band" style="background: #F0FFF4;">
						<span class="wt-ar-band-range" style="color: #2F855A;">0.35 &ndash; 0.50</span>
						<span class="wt-ar-band-meaning" style="color: #2F855A;">Strong alignment</span>
					</div>
					<div class="wt-ar-legend-band" style="background: #FEFCBF;">
						<span class="wt-ar-band-range" style="color: #B7791F;">0.20 &ndash; 0.35</span>
						<span class="wt-ar-band-meaning" style="color: #B7791F;">Moderate alignment</span>
					</div>
					<div class="wt-ar-legend-band" style="background: #FEFCBF;">
						<span class="wt-ar-band-range" style="color: #C05621;">0.10 &ndash; 0.20</span>
						<span class="wt-ar-band-meaning" style="color: #C05621;">Weak alignment</span>
					</div>
					<div class="wt-ar-legend-band" style="background: #FFF5F5;">
						<span class="wt-ar-band-range" style="color: #9B2C2C;">&lt; 0.10</span>
						<span class="wt-ar-band-meaning" style="color: #9B2C2C;">Little to no alignment</span>
					</div>
				</div>
				<div class="wt-ar-legend-footnote">
					Cosine similarity measures how closely a review&rsquo;s language
					aligns with the ideal description for each dimension.
					Higher values mean the review more strongly reflects
					that quality. Values near zero indicate the review
					doesn&rsquo;t address that theme; negative values (rare)
					would suggest opposing language.
				</div>
			</div>

			<!-- Canonical Dimension Queries -->
			<div class="wt-ar-queries-card">
				<div class="wt-ar-queries-title">Canonical Dimension Queries</div>
				<div class="wt-ar-queries-subtitle">
					Each review is compared against these ideal descriptions using
					sentence embeddings. The cosine similarity score reflects how
					closely a review&rsquo;s language matches each dimension.
				</div>
				<?php foreach ( wt_get_dimensions() as $dim ) : ?>
					<div class="wt-ar-query-block" style="border-left: 4px solid <?php echo esc_attr( $dim_colors[ $dim ] ); ?>;">
						<div class="wt-ar-query-label" style="color: <?php echo esc_attr( $dim_colors[ $dim ] ); ?>;">
							<?php echo esc_html( $dim_labels[ $dim ] ); ?>
						</div>
						<div class="wt-ar-query-text">
							&ldquo;<?php echo esc_html( $dim_query_texts[ $dim ] ); ?>&rdquo;
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

	</div><!-- .wt-all-reviews-grid -->
</div>

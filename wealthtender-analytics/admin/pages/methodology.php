<?php
/**
 * Methodology Page Template
 * Documentation and explanation of the analysis pipeline, architecture, and scoring
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wt-analytics-wrap wt-methodology-page">

	<!-- Page Header -->
	<h1><?php esc_html_e( 'Methodology', 'wealthtender-analytics' ); ?></h1>

	<!-- Accordion Sections -->
	<div class="wt-methodology-accordion">

		<!-- 1. PROJECT OVERVIEW -->
		<div class="wt-methodology-section">
			<h3 class="wt-section-header" data-section="project-overview">
				<span class="wt-section-icon">+</span>
				<?php esc_html_e( 'Project Overview', 'wealthtender-analytics' ); ?>
			</h3>
			<div class="wt-section-content" style="display: none;">
				<p>
					<?php
					esc_html_e(
						'The Wealthtender Advisor Review Analytics platform transforms client feedback into actionable, dimension-level insights about advisory practice quality. This WordPress plugin converts raw client reviews into structured dimension scores (Trust & Integrity, Customer Empathy, Communication Clarity, Responsiveness, Life Event Support, and Investment Expertise) that help advisors understand their strengths and areas for improvement.',
						'wealthtender-analytics'
					);
					?>
				</p>
				<p>
					<?php
					esc_html_e(
						'The platform employs a two-layer architecture: a Python NLP pipeline running offline to process and score reviews, combined with a WordPress plugin serving the analytics dashboard. This design separates heavy computational work from the interactive interface, ensuring fast, responsive user experience while maintaining analytical rigor.',
						'wealthtender-analytics'
					);
					?>
				</p>
			</div>
		</div>

		<!-- 2. SYSTEM ARCHITECTURE -->
		<div class="wt-methodology-section">
			<h3 class="wt-section-header" data-section="system-architecture">
				<span class="wt-section-icon">+</span>
				<?php esc_html_e( 'System Architecture', 'wealthtender-analytics' ); ?>
			</h3>
			<div class="wt-section-content" style="display: none;">
				<p>
					<?php
					esc_html_e(
						'The Wealthtender system employs a modular two-layer design:',
						'wealthtender-analytics'
					);
					?>
				</p>
				<ul>
					<li>
						<strong><?php esc_html_e( 'Python NLP Pipeline:', 'wealthtender-analytics' ); ?></strong>
						<?php
						esc_html_e(
							'Runs offline (batch processing) to normalize text, generate embeddings, compute dimension scores, and aggregate results by advisor/entity. Produces CSV artifacts and metadata.',
							'wealthtender-analytics'
						);
						?>
					</li>
					<li>
						<strong><?php esc_html_e( 'WordPress Plugin:', 'wealthtender-analytics' ); ?></strong>
						<?php
						esc_html_e(
							'PHP + Plotly.js frontend reads scored artifacts, serves data via REST API (/wt/v1/), and renders the interactive admin dashboard for users.',
							'wealthtender-analytics'
						);
						?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Data Flow:', 'wealthtender-analytics' ); ?></strong>
						<?php
						esc_html_e(
							'Raw reviews → Python pipeline (clean, embed, score) → CSV artifacts → WP REST API → Plotly.js charts → Admin dashboard.',
							'wealthtender-analytics'
						);
						?>
					</li>
				</ul>
			</div>
		</div>

		<!-- 3. ANALYSIS PIPELINE -->
		<div class="wt-methodology-section">
			<h3 class="wt-section-header" data-section="analysis-pipeline">
				<span class="wt-section-icon">+</span>
				<?php esc_html_e( 'Analysis Pipeline', 'wealthtender-analytics' ); ?>
			</h3>
			<div class="wt-section-content" style="display: none;">
				<p><?php esc_html_e( 'The analysis pipeline consists of three sequential stages:', 'wealthtender-analytics' ); ?></p>
				<h4><?php esc_html_e( 'Stage 1: Clean', 'wealthtender-analytics' ); ?></h4>
				<ul>
					<li><?php esc_html_e( 'Text normalization (lowercasing, punctuation handling)', 'wealthtender-analytics' ); ?></li>
					<li><?php esc_html_e( 'Boilerplate removal (template text, disclaimers)', 'wealthtender-analytics' ); ?></li>
					<li><?php esc_html_e( 'Test account and invalid review filtering', 'wealthtender-analytics' ); ?></li>
				</ul>
				<h4><?php esc_html_e( 'Stage 2: Embed', 'wealthtender-analytics' ); ?></h4>
				<ul>
					<li><?php esc_html_e( 'Sentence-transformer embeddings using all-MiniLM-L6-v2 model (384-dimensional)', 'wealthtender-analytics' ); ?></li>
					<li><?php esc_html_e( 'Incremental processing with SHA256 deduplication', 'wealthtender-analytics' ); ?></li>
					<li><?php esc_html_e( 'Time-weighted aggregation to prioritize recent reviews', 'wealthtender-analytics' ); ?></li>
				</ul>
				<h4><?php esc_html_e( 'Stage 3: Score', 'wealthtender-analytics' ); ?></h4>
				<ul>
					<li><?php esc_html_e( 'Cosine similarity computation against 6 dimension query vectors', 'wealthtender-analytics' ); ?></li>
					<li><?php esc_html_e( 'Advisor-level aggregation using mean, penalized, or weighted methods', 'wealthtender-analytics' ); ?></li>
					<li><?php esc_html_e( 'Percentile ranking and tier assignment against peer groups', 'wealthtender-analytics' ); ?></li>
				</ul>
			</div>
		</div>

		<!-- 4. THE SIX DIMENSIONS -->
		<div class="wt-methodology-section">
			<h3 class="wt-section-header" data-section="six-dimensions">
				<span class="wt-section-icon">+</span>
				<?php esc_html_e( 'The Six Dimensions', 'wealthtender-analytics' ); ?>
			</h3>
			<div class="wt-section-content" style="display: none;">
				<table class="wt-dimensions-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Dimension', 'wealthtender-analytics' ); ?></th>
							<th><?php esc_html_e( 'Description', 'wealthtender-analytics' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Trust & Integrity', 'wealthtender-analytics' ); ?></strong></td>
							<td><?php esc_html_e( 'Fiduciary duty, honesty, transparency, and reliability in all client interactions.', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Customer Empathy & Personalization', 'wealthtender-analytics' ); ?></strong></td>
							<td><?php esc_html_e( 'Listens actively, understands unique goals and circumstances, provides custom-tailored advice.', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Communication Clarity', 'wealthtender-analytics' ); ?></strong></td>
							<td><?php esc_html_e( 'Explains concepts simply in plain English, avoids jargon, ensures client understanding.', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Responsiveness', 'wealthtender-analytics' ); ?></strong></td>
							<td><?php esc_html_e( 'Always accessible, responds quickly, available during market crises or emergencies.', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Life Event Support', 'wealthtender-analytics' ); ?></strong></td>
							<td><?php esc_html_e( 'Provides compassionate guidance through major life transitions (retirement, inheritance, loss, etc.).', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Investment Expertise', 'wealthtender-analytics' ); ?></strong></td>
							<td><?php esc_html_e( 'Demonstrates technical proficiency, strong asset allocation skills, and tax strategy knowledge.', 'wealthtender-analytics' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- 5. SCORING METHODOLOGY -->
		<div class="wt-methodology-section">
			<h3 class="wt-section-header" data-section="scoring-methodology">
				<span class="wt-section-icon">+</span>
				<?php esc_html_e( 'Scoring Methodology', 'wealthtender-analytics' ); ?>
			</h3>
			<div class="wt-section-content" style="display: none;">
				<h4><?php esc_html_e( 'Cosine Similarity', 'wealthtender-analytics' ); ?></h4>
				<p>
					<?php
					esc_html_e(
						'Each review is embedded as a 384-dimensional vector. Dimension query vectors (crafted to capture the essence of each dimension) are compared to review embeddings using cosine similarity. A score of 1.0 indicates perfect alignment; 0.0 indicates no alignment.',
						'wealthtender-analytics'
					);
					?>
				</p>
				<h4><?php esc_html_e( 'Aggregation Methods', 'wealthtender-analytics' ); ?></h4>
				<ul>
					<li><strong><?php esc_html_e( 'Mean:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Simple average of all review scores for the dimension.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Penalized:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Reduces score if review count is low (penalizes low-review advisors).', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Weighted:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Weights reviews by recency and length, emphasizing substantive recent feedback.', 'wealthtender-analytics' ); ?></li>
				</ul>
				<h4><?php esc_html_e( 'Percentile Ranking', 'wealthtender-analytics' ); ?></h4>
				<p>
					<?php
					esc_html_e(
						'Scores are ranked against peer groups (All, Premier 20+) to calculate percentiles. Percentiles show how an advisor ranks relative to peers (e.g., 75th percentile = top 25%).',
						'wealthtender-analytics'
					);
					?>
				</p>
				<h4><?php esc_html_e( 'Performance Tiers', 'wealthtender-analytics' ); ?></h4>
				<table class="wt-tiers-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tier', 'wealthtender-analytics' ); ?></th>
							<th><?php esc_html_e( 'Percentile Range', 'wealthtender-analytics' ); ?></th>
							<th><?php esc_html_e( 'Interpretation', 'wealthtender-analytics' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Very Strong', 'wealthtender-analytics' ); ?></td>
							<td>≥75th</td>
							<td><?php esc_html_e( 'Top quartile performance', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Strong', 'wealthtender-analytics' ); ?></td>
							<td>50th–74th</td>
							<td><?php esc_html_e( 'Above median', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Moderate', 'wealthtender-analytics' ); ?></td>
							<td>25th–49th</td>
							<td><?php esc_html_e( 'Below median', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Foundational', 'wealthtender-analytics' ); ?></td>
							<td>&lt;25th</td>
							<td><?php esc_html_e( 'Bottom quartile', 'wealthtender-analytics' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- 6. DATA FLOW: FOLLOWING A REVIEW -->
		<div class="wt-methodology-section">
			<h3 class="wt-section-header" data-section="data-flow">
				<span class="wt-section-icon">+</span>
				<?php esc_html_e( 'Data Flow: Following a Review', 'wealthtender-analytics' ); ?>
			</h3>
			<div class="wt-section-content" style="display: none;">
				<p><?php esc_html_e( 'Trace a sample review through the pipeline:', 'wealthtender-analytics' ); ?></p>
				<ol>
					<li><strong><?php esc_html_e( 'Raw Text:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Client submits a written review through the review platform.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Cleaned:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Text is normalized, boilerplate removed, invalid reviews filtered out.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Embedded:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Cleaned text is converted to 384-dimensional embedding using all-MiniLM-L6-v2.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Scored per Dimension:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Cosine similarity computed against each of the 6 dimension query vectors.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Aggregated:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Review scores combined with advisor\'s other reviews using mean/penalized/weighted method.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Enriched:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Advisor scores enriched with percentiles and tier labels based on peer comparisons.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Visualized:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Results rendered in the WordPress dashboard as spider charts, bar charts, and tables.', 'wealthtender-analytics' ); ?></li>
				</ol>
			</div>
		</div>

		<!-- 7. WORDPRESS PLUGIN ARCHITECTURE -->
		<div class="wt-methodology-section">
			<h3 class="wt-section-header" data-section="wp-architecture">
				<span class="wt-section-icon">+</span>
				<?php esc_html_e( 'WordPress Plugin Architecture', 'wealthtender-analytics' ); ?>
			</h3>
			<div class="wt-section-content" style="display: none;">
				<p>
					<?php
					esc_html_e(
						'The WordPress plugin reimplements the Python analytics pipeline for the web. Rather than replicating all computations, it reads pre-computed CSV artifacts generated by the Python pipeline and serves the data via REST API endpoints.',
						'wealthtender-analytics'
					);
					?>
				</p>
				<table class="wt-architecture-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Component', 'wealthtender-analytics' ); ?></th>
							<th><?php esc_html_e( 'Original (Python)', 'wealthtender-analytics' ); ?></th>
							<th><?php esc_html_e( 'WordPress Plugin', 'wealthtender-analytics' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Embedding', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'sentence-transformers', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'Pre-computed (read from CSV)', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Scoring', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'Cosine similarity (NumPy)', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'Pre-computed (read from CSV)', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Aggregation', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'Pandas, NumPy', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'PHP (in-memory calculation)', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'API', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'FastAPI', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'WordPress REST API (/wt/v1/)', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Visualization', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'Plotly (Python)', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'Plotly.js (JavaScript)', 'wealthtender-analytics' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Authentication', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'Prototype auth system', 'wealthtender-analytics' ); ?></td>
							<td><?php esc_html_e( 'WordPress roles + capabilities', 'wealthtender-analytics' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- 8. KNOWN LIMITATIONS -->
		<div class="wt-methodology-section">
			<h3 class="wt-section-header" data-section="limitations">
				<span class="wt-section-icon">+</span>
				<?php esc_html_e( 'Known Limitations', 'wealthtender-analytics' ); ?>
			</h3>
			<div class="wt-section-content" style="display: none;">
				<p><?php esc_html_e( 'The cosine similarity approach has inherent limitations:', 'wealthtender-analytics' ); ?></p>
				<ul>
					<li>
						<strong><?php esc_html_e( 'Short reviews score low:', 'wealthtender-analytics' ); ?></strong>
						<?php esc_html_e( 'A one-sentence compliment may rate poorly even if the sentiment is positive. Embedding models benefit from longer context.', 'wealthtender-analytics' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Implied meaning invisible:', 'wealthtender-analytics' ); ?></strong>
						<?php esc_html_e( 'Subtle or sarcastic feedback may not be captured. Models rely on explicit language alignment.', 'wealthtender-analytics' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Verbose bias:', 'wealthtender-analytics' ); ?></strong>
						<?php esc_html_e( 'The system systematically favors long, detailed reviews over concise ones, regardless of sentiment or insight.', 'wealthtender-analytics' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Tier labels misleading:', 'wealthtender-analytics' ); ?></strong>
						<?php esc_html_e( 'A "Foundational" tier score may not reflect true weakness if the advisor has few reviews. Always check review count and confidence metrics.', 'wealthtender-analytics' ); ?>
					</li>
				</ul>
			</div>
		</div>

		<!-- 9. FUTURE IMPROVEMENTS -->
		<div class="wt-methodology-section">
			<h3 class="wt-section-header" data-section="future-improvements">
				<span class="wt-section-icon">+</span>
				<?php esc_html_e( 'Future Improvements', 'wealthtender-analytics' ); ?>
			</h3>
			<div class="wt-section-content" style="display: none;">
				<ul>
					<li><strong><?php esc_html_e( 'LLM-Augmented Scoring:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Use large language models to capture nuance and implied meaning beyond embedding similarity.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'External Review Integration:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Ingest reviews from third-party platforms (Zillow, BrightScope, etc.) to expand data sources.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Keyword Highlighting:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Automatically highlight the words/phrases in reviews that most influence dimension scores.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Dimension Trends:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Track how dimension scores evolve over time (monthly, quarterly) to identify improvement or decline.', 'wealthtender-analytics' ); ?></li>
					<li><strong><?php esc_html_e( 'Feedback Loop:', 'wealthtender-analytics' ); ?></strong> <?php esc_html_e( 'Allow users to rate the accuracy of dimension assignments to refine query vectors.', 'wealthtender-analytics' ); ?></li>
				</ul>
			</div>
		</div>

	</div>

</div>

<script type="text/javascript">
	document.addEventListener( 'DOMContentLoaded', function() {
		// Accordion toggle functionality
		var headers = document.querySelectorAll( '.wt-section-header' );
		headers.forEach( function( header ) {
			header.addEventListener( 'click', function() {
				var content = this.nextElementSibling;
				var icon = this.querySelector( '.wt-section-icon' );
				var isOpen = content.style.display !== 'none';

				// Close other sections
				headers.forEach( function( h ) {
					if ( h !== header ) {
						h.nextElementSibling.style.display = 'none';
						h.querySelector( '.wt-section-icon' ).textContent = '+';
					}
				} );

				// Toggle current section
				if ( isOpen ) {
					content.style.display = 'none';
					icon.textContent = '+';
				} else {
					content.style.display = 'block';
					icon.textContent = '−';
				}
			} );
		} );

		// Initialize JS-related functionality if available
		if ( typeof WT !== 'undefined' && WT.initMethodology ) {
			WT.initMethodology();
		}
	} );
</script>

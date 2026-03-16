<?php
/**
 * Splash/Home Page Template
 * Displays Wealthtender branding, welcome message, navigation cards, and team info
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get user role and allowed pages
$user_role = wt_get_user_role();
$role_config = wt_get_role_config( $user_role );
$allowed_pages = isset( $role_config['pages'] ) ? $role_config['pages'] : [];

$brand_url = WEALTHTENDER_ANALYTICS_URL . 'data/brand/';
?>

<div class="wrap wt-analytics-wrap wt-splash-page">

	<!-- Splash Hero Section -->
	<div class="wt-splash-hero">
		<!-- Logo -->
		<div class="wt-splash-logo">
			<img src="<?php echo esc_url( $brand_url . 'logo-blue.svg' ); ?>" alt="Wealthtender" class="wt-splash-logo-img" />
		</div>

		<!-- Title and Tagline -->
		<h1 class="wt-splash-title"><?php esc_html_e( 'Advisor Review Analytics', 'wealthtender-analytics' ); ?></h1>
		<p class="wt-splash-tagline"><?php esc_html_e( 'UT Austin MSBA Capstone 2026', 'wealthtender-analytics' ); ?></p>

		<!-- Description -->
		<p class="wt-splash-description">
			<?php
			esc_html_e(
				'Transform client feedback into actionable intelligence. Understand your advisory practice through the lens of six core dimensions that drive client satisfaction and loyalty.',
				'wealthtender-analytics'
			);
			?>
		</p>
	</div>

	<!-- Navigation Cards -->
	<div class="wt-splash-cards">
		<?php
		$sections = [
			[
				'id'          => 'eda',
				'title'       => __( 'Exploratory Data Analysis', 'wealthtender-analytics' ),
				'description' => __( 'Explore review text patterns, sentiment, and temporal trends.', 'wealthtender-analytics' ),
				'icon'        => 'chart',
			],
			[
				'id'          => 'advisor-dna',
				'title'       => __( 'Advisor DNA', 'wealthtender-analytics' ),
				'description' => __( 'Analyze dimension scores and profiles for advisors and entities.', 'wealthtender-analytics' ),
				'icon'        => 'dna',
			],
			[
				'id'          => 'benchmarks',
				'title'       => __( 'Benchmarks', 'wealthtender-analytics' ),
				'description' => __( 'Compare your scores against peer distributions and percentiles.', 'wealthtender-analytics' ),
				'icon'        => 'benchmark',
			],
			[
				'id'          => 'leaderboard',
				'title'       => __( 'Leaderboard', 'wealthtender-analytics' ),
				'description' => __( 'See top-performing advisors across all dimensions.', 'wealthtender-analytics' ),
				'icon'        => 'ranking',
			],
			[
				'id'          => 'comparisons',
				'title'       => __( 'Comparisons', 'wealthtender-analytics' ),
				'description' => __( 'Compare two entities or team members head-to-head.', 'wealthtender-analytics' ),
				'icon'        => 'compare',
			],
			[
				'id'          => 'methodology',
				'title'       => __( 'Methodology', 'wealthtender-analytics' ),
				'description' => __( 'Learn how the scoring and analysis pipeline works.', 'wealthtender-analytics' ),
				'icon'        => 'methodology',
			],
		];

		foreach ( $sections as $section ) {
			if ( 'methodology' !== $section['id'] && ! in_array( $section['id'], $allowed_pages, true ) ) {
				continue;
			}

			$page_slug = 'wt-analytics-' . $section['id'];
			$page_url  = menu_page_url( $page_slug, false );
			?>
			<a href="<?php echo esc_url( $page_url ); ?>" class="wt-splash-card">
				<div class="wt-card-icon wt-icon-<?php echo esc_attr( $section['icon'] ); ?>"></div>
				<h3 class="wt-card-title"><?php echo esc_html( $section['title'] ); ?></h3>
				<p class="wt-card-description"><?php echo esc_html( $section['description'] ); ?></p>
				<div class="wt-card-arrow">&rarr;</div>
			</a>
			<?php
		}
		?>
	</div>

	<!-- User Status -->
	<div class="wt-splash-status">
		<p>
			<?php
			printf(
				esc_html__( 'Signed in as %s.', 'wealthtender-analytics' ),
				'<strong>' . esc_html( wp_get_current_user()->display_name ) . '</strong>'
			);
			?>
			<?php esc_html_e( 'Full access to all firms, advisors, EDA, and methodology.', 'wealthtender-analytics' ); ?>
		</p>
	</div>

	<!-- Team Credits -->
	<div class="wt-team-credits">
		<h3><?php esc_html_e( 'MSBA Team', 'wealthtender-analytics' ); ?></h3>
		<p class="wt-team-names"><?php esc_html_e( 'Joseph Bailey • Chris Breton • Manny Escalante • Zan Merrill • Carolina Rios • Alisha Surabhi', 'wealthtender-analytics' ); ?></p>

		<h3><?php esc_html_e( 'Undergraduate Contributors', 'wealthtender-analytics' ); ?></h3>
		<p class="wt-team-names"><?php esc_html_e( 'Saif Ansari • Isabelle Demengeon • Katelyn Semien • Julianna Tijerina • Abhinav Yarlagadda', 'wealthtender-analytics' ); ?></p>
	</div>

	<!-- Footer -->
	<div class="wt-splash-footer">
		<img src="<?php echo esc_url( $brand_url . 'logo-wordmark.svg' ); ?>" alt="Wealthtender" class="wt-footer-logo" />
		<p><?php esc_html_e( 'Advisor Review Analytics', 'wealthtender-analytics' ); ?></p>
		<p class="wt-footer-sub"><?php esc_html_e( 'UT Austin MSBA Capstone 2026', 'wealthtender-analytics' ); ?></p>
	</div>

</div>

<?php
/**
 * Plugin Name: Wealthtender Analytics
 * Plugin URI: https://wealthtender.io
 * Description: Financial advisor analytics dashboard powered by review insights
 * Version: 1.0.0
 * Author: Wealthtender
 * Author URI: https://wealthtender.io
 * License: Apache-2.0
 * Text Domain: wealthtender-analytics
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants
 */
define( 'WEALTHTENDER_ANALYTICS_VERSION', '3.7.0' );
define( 'WEALTHTENDER_ANALYTICS_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEALTHTENDER_ANALYTICS_URL', plugin_dir_url( __FILE__ ) );
define( 'WEALTHTENDER_ANALYTICS_BASENAME', plugin_basename( __FILE__ ) );
define( 'WEALTHTENDER_ANALYTICS_INCLUDES', WEALTHTENDER_ANALYTICS_DIR . 'includes/' );
define( 'WEALTHTENDER_ANALYTICS_ADMIN', WEALTHTENDER_ANALYTICS_DIR . 'admin/' );

/**
 * Plugin activation hook
 */
register_activation_hook( __FILE__, 'wt_activate_plugin' );
function wt_activate_plugin() {
	// Require roles file for role setup
	require_once WEALTHTENDER_ANALYTICS_INCLUDES . 'roles.php';
	wt_setup_roles_and_capabilities();
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook( __FILE__, 'wt_deactivate_plugin' );
function wt_deactivate_plugin() {
	// Require roles file for cleanup
	require_once WEALTHTENDER_ANALYTICS_INCLUDES . 'roles.php';
	wt_cleanup_roles_and_capabilities();
}

/**
 * Load includes early — needed by admin_menu, rest_api_init, and admin_init hooks
 */
require_once WEALTHTENDER_ANALYTICS_INCLUDES . 'constants.php';
require_once WEALTHTENDER_ANALYTICS_INCLUDES . 'artifacts.php';
require_once WEALTHTENDER_ANALYTICS_INCLUDES . 'roles.php';
require_once WEALTHTENDER_ANALYTICS_INCLUDES . 'rest-api.php';

/**
 * Register admin menus
 */
add_action( 'admin_menu', 'wt_register_admin_menus' );
function wt_register_admin_menus() {
	$user_role = wt_get_user_role();

	// Only show menu to users with a WT role
	if ( null === $user_role ) {
		return;
	}

	$role_config = wt_get_role_config( $user_role );

	// Add main menu
	add_menu_page(
		'WT Analytics',
		'WT Analytics',
		'read',
		'wt-analytics-home',
		'wt_render_admin_page',
		'dashicons-chart-area',
		25
	);

	// Define menu structure
	$pages = [
		'splash' => 'Home',
		'eda' => 'EDA',
		'advisor-dna' => 'Advisor DNA',
		'benchmarks' => 'Benchmarks',
		'leaderboard' => 'Leaderboard',
		'comparisons' => 'Comparisons',
		'team-comparisons' => 'Team Comparisons',
		'methodology' => 'Methodology',
	];

	// Add submenus based on user role
	foreach ( $pages as $slug => $title ) {
		// Skip splash for submenus (it's the main page)
		if ( 'splash' === $slug ) {
			continue;
		}

		// Check if user can access this page
		if ( ! in_array( $slug, $role_config['pages'], true ) ) {
			continue;
		}

		add_submenu_page(
			'wt-analytics-home',
			'WT Analytics - ' . $title,
			$title,
			'read',
			'wt-analytics-' . $slug,
			'wt_render_admin_page'
		);
	}
}

/**
 * Inject full-screen overrides for html element on WT pages
 */
add_action( 'admin_head', 'wt_fullscreen_head_styles' );
function wt_fullscreen_head_styles() {
	if ( isset( $_GET['page'] ) && strpos( sanitize_key( $_GET['page'] ), 'wt-analytics' ) !== false ) {
		echo '<style>html.wp-toolbar { padding-top: 0 !important; }</style>';
	}
}

/**
 * Add body class to WT Analytics pages for full-screen mode
 */
add_filter( 'admin_body_class', 'wt_admin_body_class' );
function wt_admin_body_class( $classes ) {
	if ( isset( $_GET['page'] ) && strpos( sanitize_key( $_GET['page'] ), 'wt-analytics' ) !== false ) {
		$classes .= ' wt-fullscreen-app';
	}
	return $classes;
}

/**
 * Get the current WT page slug
 */
function wt_get_current_page_slug() {
	if ( isset( $_GET['page'] ) ) {
		$slug = sanitize_key( $_GET['page'] );
		$slug = str_replace( 'wt-analytics-', '', $slug );
		if ( 'home' === $slug ) {
			$slug = 'splash';
		}
		return $slug;
	}
	return 'splash';
}

/**
 * Render the branded top navigation bar
 */
function wt_render_navbar( $current_slug ) {
	$user_role   = wt_get_user_role();
	$role_config = wt_get_role_config( $user_role );
	$allowed     = isset( $role_config['pages'] ) ? $role_config['pages'] : [];

	$nav_items = [
		'home'              => [ 'label' => 'Home',        'slug' => 'splash' ],
		'eda'               => [ 'label' => 'EDA',          'slug' => 'eda' ],
		'advisor-dna'       => [ 'label' => 'Advisor DNA',  'slug' => 'advisor-dna' ],
		'benchmarks'        => [ 'label' => 'Benchmarks',   'slug' => 'benchmarks' ],
		'leaderboard'       => [ 'label' => 'Leaderboard',  'slug' => 'leaderboard' ],
		'comparisons'       => [ 'label' => 'Comparisons',  'slug' => 'comparisons' ],
		'team-comparisons'  => [ 'label' => 'Team Comparisons', 'slug' => 'team-comparisons' ],
		'methodology'       => [ 'label' => 'Methodology',  'slug' => 'methodology' ],
	];

	$brand_url = WEALTHTENDER_ANALYTICS_URL . 'data/brand/';
	?>
	<nav class="wt-app-navbar">
		<div class="wt-navbar-inner">
			<a href="<?php echo esc_url( menu_page_url( 'wt-analytics-home', false ) ); ?>" class="wt-navbar-brand">
				<img src="<?php echo esc_url( $brand_url . 'logo-mark.svg' ); ?>" alt="Wealthtender" class="wt-navbar-logo-mark" />
				<img src="<?php echo esc_url( $brand_url . 'logo-wordmark.svg' ); ?>" alt="Wealthtender" class="wt-navbar-logo-text" />
			</a>
			<div class="wt-navbar-links">
				<?php foreach ( $nav_items as $key => $item ) :
					// Check access (methodology always visible, splash always visible)
					if ( 'home' !== $key && 'methodology' !== $key && ! in_array( $item['slug'], $allowed, true ) ) {
						continue;
					}
					$page_key = ( 'home' === $key ) ? 'wt-analytics-home' : 'wt-analytics-' . $item['slug'];
					$is_active = ( $current_slug === $item['slug'] );
					?>
					<a href="<?php echo esc_url( menu_page_url( $page_key, false ) ); ?>"
					   class="wt-navbar-link <?php echo $is_active ? 'active' : ''; ?>">
						<?php echo esc_html( $item['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
			<div class="wt-navbar-user">
				<span class="wt-navbar-user-name"><?php echo esc_html( wp_get_current_user()->display_name ); ?></span>
				<a href="<?php echo esc_url( wp_logout_url() ); ?>" class="wt-navbar-signout">Sign Out</a>
			</div>
		</div>
	</nav>
	<?php
}

/**
 * Render admin pages
 */
function wt_render_admin_page() {
	$page_slug = wt_get_current_page_slug();

	// Verify user can access this page
	if ( ! wt_user_can_access_page( $page_slug ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'wealthtender-analytics' ) );
	}

	// Render the branded app shell
	echo '<div class="wt-app-shell">';
	wt_render_navbar( $page_slug );
	echo '<div class="wt-app-body">';

	// Route to appropriate template
	$template_path = WEALTHTENDER_ANALYTICS_ADMIN . 'pages/' . $page_slug . '.php';

	if ( file_exists( $template_path ) ) {
		include $template_path;
	} else {
		wp_die( esc_html__( 'Page template not found.', 'wealthtender-analytics' ) );
	}

	echo '</div>'; // .wt-app-body
	echo '</div>'; // .wt-app-shell
}

/**
 * Enqueue admin scripts and styles
 */
add_action( 'admin_enqueue_scripts', 'wt_enqueue_admin_assets' );
function wt_enqueue_admin_assets( $hook ) {
	// Only on plugin pages
	if ( strpos( $hook, 'wt-analytics' ) === false ) {
		return;
	}

	// Get current page slug
	if ( isset( $_GET['page'] ) ) {
		$page_slug = sanitize_key( $_GET['page'] );
		$page_slug = str_replace( 'wt-analytics-', '', $page_slug );
		if ( 'home' === $page_slug ) {
			$page_slug = 'splash';
		}
	} else {
		$page_slug = 'splash';
	}

	// Get dimensions and colors from constants
	$dimensions = wt_get_dimensions();
	$dim_labels = wt_get_dim_labels();
	$dim_short = wt_get_dim_short();
	$dim_colors = wt_get_dim_colors();
	$colors = wt_get_colors();
	$data_viz_palette = wt_get_data_viz_palette();

	// Build artifacts path
	$artifacts_path = WEALTHTENDER_ANALYTICS_DIR . 'data/artifacts/';

	// Enqueue styles
	wp_enqueue_style(
		'wt-theme',
		WEALTHTENDER_ANALYTICS_URL . 'admin/css/wt-theme.css',
		[],
		WEALTHTENDER_ANALYTICS_VERSION
	);

	// Enqueue Plotly.js
	wp_enqueue_script(
		'plotly',
		'https://cdn.plot.ly/plotly-2.35.0.min.js',
		[],
		'2.35.0',
		true
	);

	// Enqueue common utilities
	wp_enqueue_script(
		'wt-common',
		WEALTHTENDER_ANALYTICS_URL . 'admin/js/wt-common.js',
		[ 'jquery', 'plotly' ],
		WEALTHTENDER_ANALYTICS_VERSION,
		true
	);

	// Localize script with analytics data
	wp_localize_script(
		'wt-common',
		'wtAnalytics',
		[
			'restUrl'         => rest_url(),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'artifactsPath'   => $artifacts_path,
			'dimensions'      => $dimensions,
			'dimLabels'       => $dim_labels,
			'dimShort'        => $dim_short,
			'dimColors'       => $dim_colors,
			'dataVizPalette'  => $data_viz_palette,
			'colors'          => $colors,
		]
	);

	// Enqueue page-specific script
	$page_script_map = [
		'splash'            => 'wt-splash.js',
		'advisor-dna'       => 'wt-advisor-dna.js',
		'eda'               => 'wt-eda.js',
		'benchmarks'        => 'wt-benchmarks.js',
		'leaderboard'       => 'wt-leaderboard.js',
		'comparisons'       => 'wt-comparisons.js',
		'team-comparisons'  => 'wt-team-comparisons.js',
		'methodology'       => 'wt-methodology.js',
	];

	if ( isset( $page_script_map[ $page_slug ] ) ) {
		$script_file = $page_script_map[ $page_slug ];
		wp_enqueue_script(
			'wt-' . $page_slug,
			WEALTHTENDER_ANALYTICS_URL . 'admin/js/' . $script_file,
			[ 'wt-common' ],
			WEALTHTENDER_ANALYTICS_VERSION,
			true
		);
	}
}

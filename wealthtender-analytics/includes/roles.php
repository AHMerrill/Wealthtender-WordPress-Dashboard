<?php
/**
 * Wealthtender Analytics Roles and Capabilities
 *
 * Manages role definitions, capability assignments, and access control
 * for admin and firm portal users.
 *
 * @package Wealthtender_Analytics
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get role configuration
 *
 * @param string $role Role identifier: 'admin' or 'firm'
 * @return array|null Role configuration or null if role doesn't exist
 */
function wt_get_role_config( $role ) {
	$roles = [
		'admin' => [
			'label'            => 'Wealthtender Admin',
			'pages'            => [ 'splash', 'eda', 'advisor-dna', 'benchmarks', 'leaderboard', 'comparisons', 'team-comparisons', 'all-reviews', 'methodology' ],
			'show_firm_picker' => true,
			'firm_locked'      => false,
		],
		'firm'  => [
			'label'            => 'Firm Portal',
			'pages'            => [ 'advisor-dna', 'benchmarks', 'leaderboard', 'comparisons', 'team-comparisons' ],
			'show_firm_picker' => false,
			'firm_locked'      => true,
		],
	];

	return isset( $roles[ $role ] ) ? $roles[ $role ] : null;
}

/**
 * Get current user's role
 *
 * Checks user capabilities and returns role identifier.
 *
 * @return string|null 'admin', 'firm', or null if user has no WT role
 */
function wt_get_user_role() {
	$user = wp_get_current_user();

	if ( ! $user->exists() ) {
		return null;
	}

	// WordPress administrators always get admin access
	if ( user_can( $user, 'manage_options' ) || user_can( $user, 'wt_admin_access' ) ) {
		return 'admin';
	}

	if ( user_can( $user, 'wt_firm_access' ) ) {
		return 'firm';
	}

	return null;
}

/**
 * Check if user can access a page
 *
 * Verifies that the current user's role allows access to the requested page.
 *
 * @param string $page_slug Page identifier (e.g., 'advisor-dna', 'benchmarks')
 * @return bool True if user can access, false otherwise
 */
function wt_user_can_access_page( $page_slug ) {
	$user_role = wt_get_user_role();

	if ( null === $user_role ) {
		return false;
	}

	$role_config = wt_get_role_config( $user_role );

	if ( null === $role_config ) {
		return false;
	}

	return in_array( $page_slug, $role_config['pages'], true );
}

/**
 * Setup roles and capabilities on plugin activation
 *
 * Creates the custom wt_firm role and assigns capabilities to admin and firm users.
 */
function wt_setup_roles_and_capabilities() {
	global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles(); // phpcs:ignore WordPress.Variables.GlobalVariables.Prohibited
	}

	// Add custom wt_firm role
	add_role(
		'wt_firm',
		__( 'Firm Portal User', 'wealthtender-analytics' ),
		[
			'read' => true,
		]
	);

	// Add capabilities to wt_firm role
	$wp_roles->add_cap( 'wt_firm', 'wt_firm_access' );

	// Add capabilities to administrator role
	$wp_roles->add_cap( 'administrator', 'wt_admin_access' );
	$wp_roles->add_cap( 'administrator', 'wt_firm_access' );
}

/**
 * Remove roles and capabilities on plugin deactivation
 *
 * Cleans up the custom wt_firm role and removes all WT capabilities.
 */
function wt_cleanup_roles_and_capabilities() {
	global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles(); // phpcs:ignore WordPress.Variables.GlobalVariables.Prohibited
	}

	// Remove capabilities from administrator
	$wp_roles->remove_cap( 'administrator', 'wt_admin_access' );
	$wp_roles->remove_cap( 'administrator', 'wt_firm_access' );

	// Remove capability from wt_firm role
	$wp_roles->remove_cap( 'wt_firm', 'wt_firm_access' );

	// Remove the custom role
	remove_role( 'wt_firm' );
}

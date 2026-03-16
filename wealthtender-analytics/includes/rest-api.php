<?php
/**
 * Wealthtender Analytics REST API
 *
 * Registers REST API endpoints for data retrieval and analysis.
 *
 * @package Wealthtender_Analytics
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST API routes
 */
add_action( 'rest_api_init', 'wt_register_rest_routes' );
function wt_register_rest_routes() {
	// ===== Health & Metadata =====

	// GET /wt/v1/health
	register_rest_route(
		'wt/v1',
		'/health',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_health',
			'permission_callback' => '__return_true',
		)
	);

	// GET /wt/v1/metadata
	register_rest_route(
		'wt/v1',
		'/metadata',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_metadata',
			'permission_callback' => 'wt_rest_check_permission',
		)
	);

	// ===== Entities =====

	// GET /wt/v1/entities
	register_rest_route(
		'wt/v1',
		'/entities',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_entities',
			'permission_callback' => 'wt_rest_check_permission',
		)
	);

	// GET /wt/v1/stopwords
	register_rest_route(
		'wt/v1',
		'/stopwords',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_stopwords',
			'permission_callback' => 'wt_rest_check_permission',
		)
	);

	// ===== Advisor DNA =====

	// GET /wt/v1/advisor-dna/macro-totals
	register_rest_route(
		'wt/v1',
		'/advisor-dna/macro-totals',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_dna_macro_totals',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'min_peer_reviews' => array(
					'type'    => 'integer',
					'default' => 0,
				),
			),
		)
	);

	// GET /wt/v1/advisor-dna/macro-sample
	register_rest_route(
		'wt/v1',
		'/advisor-dna/macro-sample',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_dna_macro_sample',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'n' => array(
					'type'    => 'integer',
					'default' => 100,
				),
			),
		)
	);

	// GET /wt/v1/advisor-dna/entity-reviews
	register_rest_route(
		'wt/v1',
		'/advisor-dna/entity-reviews',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_dna_entity_reviews',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'entity_id' => array(
					'type'     => 'string',
					'required' => true,
				),
			),
		)
	);

	// GET /wt/v1/advisor-dna/advisor-scores
	register_rest_route(
		'wt/v1',
		'/advisor-dna/advisor-scores',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_dna_advisor_scores',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'entity_id' => array(
					'type'     => 'string',
					'required' => true,
				),
				'method'    => array(
					'type'    => 'string',
					'default' => 'mean',
				),
			),
		)
	);

	// GET /wt/v1/advisor-dna/percentile-scores
	register_rest_route(
		'wt/v1',
		'/advisor-dna/percentile-scores',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_dna_percentile_scores',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'entity_id'         => array(
					'type'     => 'string',
					'required' => true,
				),
				'method'            => array(
					'type'    => 'string',
					'default' => 'mean',
				),
				'min_peer_reviews'  => array(
					'type'    => 'integer',
					'default' => 0,
				),
			),
		)
	);

	// GET /wt/v1/advisor-dna/method-breakpoints
	register_rest_route(
		'wt/v1',
		'/advisor-dna/method-breakpoints',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_dna_method_breakpoints',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'method'      => array(
					'type'    => 'string',
					'default' => 'mean',
				),
				'entity_type' => array(
					'type' => 'string',
				),
			),
		)
	);

	// GET /wt/v1/advisor-dna/review/(?P<review_idx>\d+)
	register_rest_route(
		'wt/v1',
		'/advisor-dna/review/(?P<review_idx>\d+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_dna_review_detail',
			'permission_callback' => 'wt_rest_check_permission',
		)
	);

	// GET /wt/v1/eda/review/(?P<review_idx>\d+)
	register_rest_route(
		'wt/v1',
		'/eda/review/(?P<review_idx>\d+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_eda_review_detail',
			'permission_callback' => 'wt_rest_check_permission',
		)
	);

	// ===== EDA =====

	// GET /wt/v1/eda/charts
	register_rest_route(
		'wt/v1',
		'/eda/charts',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_eda_charts',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'scope'                     => array(
					'type' => 'string',
				),
				'firm_id'                   => array(
					'type' => 'string',
				),
				'advisor_id'                => array(
					'type' => 'string',
				),
				'date_start'                => array(
					'type' => 'string',
				),
				'date_end'                  => array(
					'type' => 'string',
				),
				'rating'                    => array(
					'type' => 'string',
				),
				'min_tokens'                => array(
					'type' => 'integer',
				),
				'max_tokens'                => array(
					'type' => 'integer',
				),
				'min_reviews_per_advisor'   => array(
					'type' => 'integer',
				),
				'max_reviews_per_advisor'   => array(
					'type' => 'integer',
				),
				'lexical_n'                 => array(
					'type'    => 'integer',
					'default' => 1,
				),
				'lexical_top_n'             => array(
					'type'    => 'integer',
					'default' => 30,
				),
				'exclude_stopwords'         => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'custom_stopwords'          => array(
					'type' => 'string',
				),
				'time_freq'                 => array(
					'type'    => 'string',
					'default' => 'month',
				),
			),
		)
	);

	// ===== Benchmarks =====

	// GET /wt/v1/benchmarks/pool-stats
	register_rest_route(
		'wt/v1',
		'/benchmarks/pool-stats',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_benchmarks_pool_stats',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'min_peer_reviews' => array(
					'type'    => 'integer',
					'default' => 20,
				),
			),
		)
	);

	// GET /wt/v1/benchmarks/distributions
	register_rest_route(
		'wt/v1',
		'/benchmarks/distributions',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_benchmarks_distributions',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'method'            => array(
					'type'    => 'string',
					'default' => 'mean',
				),
				'entity_type'       => array(
					'type' => 'string',
				),
				'min_peer_reviews'  => array(
					'type'    => 'integer',
					'default' => 0,
				),
			),
		)
	);

	// ===== Leaderboard =====

	// GET /wt/v1/leaderboard
	register_rest_route(
		'wt/v1',
		'/leaderboard',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_leaderboard',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'method'            => array(
					'type'    => 'string',
					'default' => 'mean',
				),
				'entity_type'       => array(
					'type' => 'string',
				),
				'min_peer_reviews'  => array(
					'type'    => 'integer',
					'default' => 0,
				),
				'top_n'             => array(
					'type'    => 'integer',
					'default' => 10,
				),
				'dimension'         => array(
					'type' => 'string',
				),
			),
		)
	);

	// ===== Comparisons =====

	// GET /wt/v1/comparisons/partner-groups
	register_rest_route(
		'wt/v1',
		'/comparisons/partner-groups',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_comparisons_partner_groups',
			'permission_callback' => 'wt_rest_check_permission',
		)
	);

	// GET /wt/v1/comparisons/partner-group/(?P<group_code>[a-zA-Z0-9_-]+)
	register_rest_route(
		'wt/v1',
		'/comparisons/partner-group/(?P<group_code>[a-zA-Z0-9_-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_comparisons_partner_group',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'method' => array(
					'type'    => 'string',
					'default' => 'mean',
				),
			),
		)
	);

	// GET /wt/v1/comparisons/entities
	register_rest_route(
		'wt/v1',
		'/comparisons/entities',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_comparisons_entities',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'entity_ids' => array(
					'type'     => 'string',
					'required' => true,
				),
				'method'     => array(
					'type'    => 'string',
					'default' => 'mean',
				),
			),
		)
	);

	// GET /wt/v1/comparisons/head-to-head
	register_rest_route(
		'wt/v1',
		'/comparisons/head-to-head',
		array(
			'methods'             => 'GET',
			'callback'            => 'wt_rest_comparisons_head_to_head',
			'permission_callback' => 'wt_rest_check_permission',
			'args'                => array(
				'entity_a' => array(
					'type'     => 'string',
					'required' => true,
				),
				'entity_b' => array(
					'type'     => 'string',
					'required' => true,
				),
				'method'   => array(
					'type'    => 'string',
					'default' => 'mean',
				),
			),
		)
	);
}

/**
 * Permission callback - checks if user is logged in
 */
function wt_rest_check_permission() {
	return is_user_logged_in();
}

/**
 * Health endpoint callback
 */
function wt_rest_health() {
	return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
}

/**
 * Metadata endpoint callback
 */
function wt_rest_metadata( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		// Load metadata from artifacts
		$metadata = $store->get_metadata();
		return new WP_REST_Response( $metadata, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'metadata_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Entities endpoint callback
 */
function wt_rest_entities( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$entities = $store->get_entity_list();
		return new WP_REST_Response( $entities, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'entities_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Stopwords endpoint callback
 */
function wt_rest_stopwords( WP_REST_Request $request ) {
	try {
		$stopwords = wt_get_stopwords();
		return new WP_REST_Response( $stopwords, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'stopwords_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * DNA Macro Totals endpoint callback
 */
function wt_rest_dna_macro_totals( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$min_peer_reviews = $request->get_param( 'min_peer_reviews' );
		$result           = $store->dna_macro_totals( $min_peer_reviews );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'dna_macro_totals_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * DNA Macro Sample endpoint callback
 */
function wt_rest_dna_macro_sample( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$n      = $request->get_param( 'n' );
		$result = $store->dna_macro_sample( $n );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'dna_macro_sample_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * DNA Entity Reviews endpoint callback
 */
function wt_rest_dna_entity_reviews( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$entity_id = $request->get_param( 'entity_id' );
		$result    = $store->dna_entity_reviews( $entity_id );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'dna_entity_reviews_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * DNA Advisor Scores endpoint callback
 */
function wt_rest_dna_advisor_scores( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$entity_id = $request->get_param( 'entity_id' );
		$method    = $request->get_param( 'method' );
		$result    = $store->enrich_scores( $entity_id, $method );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'dna_advisor_scores_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * DNA Percentile Scores endpoint callback
 */
function wt_rest_dna_percentile_scores( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$entity_id        = $request->get_param( 'entity_id' );
		$method           = $request->get_param( 'method' );
		$min_peer_reviews = $request->get_param( 'min_peer_reviews' );
		$result           = $store->dna_percentile_scores( $entity_id, $method, $min_peer_reviews );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'dna_percentile_scores_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * DNA Method Breakpoints endpoint callback
 */
function wt_rest_dna_method_breakpoints( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$method      = $request->get_param( 'method' );
		$entity_type = $request->get_param( 'entity_type' );
		$result      = $store->dna_method_breakpoints( $method, $entity_type );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'dna_method_breakpoints_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * EDA Review Detail endpoint callback
 * Looks up from reviews_clean (EDA dataset), falling back to scored reviews
 */
function wt_rest_eda_review_detail( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$review_idx = $request->get_url_params()['review_idx'];
		$result     = $store->eda_review_detail( $review_idx );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'eda_review_detail_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * DNA Review Detail endpoint callback
 */
function wt_rest_dna_review_detail( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$review_idx = $request->get_url_params()['review_idx'];
		$result     = $store->dna_review_detail( $review_idx );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'dna_review_detail_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * EDA Charts endpoint callback
 */
function wt_rest_eda_charts( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		// Collect all parameters into an associative array
		$params = array(
			'scope'                     => $request->get_param( 'scope' ),
			'firm_id'                   => $request->get_param( 'firm_id' ),
			'advisor_id'                => $request->get_param( 'advisor_id' ),
			'date_start'                => $request->get_param( 'date_start' ),
			'date_end'                  => $request->get_param( 'date_end' ),
			'rating'                    => $request->get_param( 'rating' ),
			'min_tokens'                => $request->get_param( 'min_tokens' ),
			'max_tokens'                => $request->get_param( 'max_tokens' ),
			'min_reviews_per_advisor'   => $request->get_param( 'min_reviews_per_advisor' ),
			'max_reviews_per_advisor'   => $request->get_param( 'max_reviews_per_advisor' ),
			'lexical_n'                 => $request->get_param( 'lexical_n' ),
			'lexical_top_n'             => $request->get_param( 'lexical_top_n' ),
			'exclude_stopwords'         => $request->get_param( 'exclude_stopwords' ),
			'custom_stopwords'          => $request->get_param( 'custom_stopwords' ),
			'time_freq'                 => $request->get_param( 'time_freq' ),
		);

		// Remove null values
		$params = array_filter( $params, function ( $v ) {
			return ! is_null( $v );
		} );

		// Convert custom_stopwords from comma-separated string to array
		if ( isset( $params['custom_stopwords'] ) && is_string( $params['custom_stopwords'] ) ) {
			$params['custom_stopwords'] = array_map( 'trim', explode( ',', $params['custom_stopwords'] ) );
			$params['custom_stopwords'] = array_filter( $params['custom_stopwords'] );
		}

		$result = $store->eda_payload( $params );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'eda_charts_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Benchmarks Pool Stats endpoint callback
 */
function wt_rest_benchmarks_pool_stats( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$min_peer_reviews = $request->get_param( 'min_peer_reviews' );
		$result           = $store->benchmark_pool_stats( $min_peer_reviews );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'benchmark_pool_stats_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Benchmarks Distributions endpoint callback
 */
function wt_rest_benchmarks_distributions( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$method           = $request->get_param( 'method' );
		$entity_type      = $request->get_param( 'entity_type' );
		$min_peer_reviews = $request->get_param( 'min_peer_reviews' );
		$result           = $store->benchmark_distributions( $method, $entity_type, $min_peer_reviews );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'benchmark_distributions_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Leaderboard endpoint callback
 */
function wt_rest_leaderboard( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$method           = $request->get_param( 'method' );
		$entity_type      = $request->get_param( 'entity_type' );
		$min_peer_reviews = $request->get_param( 'min_peer_reviews' );
		$top_n            = $request->get_param( 'top_n' );
		$dimension        = $request->get_param( 'dimension' );
		$result           = $store->leaderboard( $method, $entity_type, $min_peer_reviews, $top_n, $dimension );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'leaderboard_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Comparisons Partner Groups endpoint callback
 */
function wt_rest_comparisons_partner_groups( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$result = $store->get_partner_groups();
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'partner_groups_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Comparisons Partner Group endpoint callback
 */
function wt_rest_comparisons_partner_group( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$group_code = $request->get_url_params()['group_code'];
		$method     = $request->get_param( 'method' );
		$result     = $store->get_partner_group_members( $group_code, $method );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'partner_group_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Comparisons Entities endpoint callback
 */
function wt_rest_comparisons_entities( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$entity_ids_str = $request->get_param( 'entity_ids' );
		$entity_ids     = array_map( 'trim', explode( ',', $entity_ids_str ) );
		$method         = $request->get_param( 'method' );
		$result         = $store->entity_comparison( $entity_ids, $method );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'entity_comparison_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Comparisons Head-to-Head endpoint callback
 */
function wt_rest_comparisons_head_to_head( WP_REST_Request $request ) {
	try {
		$store = wt_get_artifact_store();
		if ( ! $store ) {
			return new WP_Error( 'artifact_store_error', 'Artifact store not available', array( 'status' => 500 ) );
		}

		$entity_a = $request->get_param( 'entity_a' );
		$entity_b = $request->get_param( 'entity_b' );
		$method   = $request->get_param( 'method' );
		$result   = $store->head_to_head( $entity_a, $entity_b, $method );
		return new WP_REST_Response( $result, 200 );
	} catch ( Exception $e ) {
		return new WP_Error( 'head_to_head_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

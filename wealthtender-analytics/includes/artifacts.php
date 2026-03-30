<?php
/**
 * Wealthtender Analytics Artifact Store
 *
 * Core data layer that reads and processes CSV and JSON artifacts.
 * Provides all data computation methods for the analytics dashboard.
 *
 * @package Wealthtender_Analytics
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WTArtifactStore class
 *
 * Manages lazy-loading of artifact datasets and provides data computation
 * methods for advisor DNA, benchmarks, leaderboards, and EDA.
 */
class WTArtifactStore {

	/**
	 * Base path to artifacts directory
	 *
	 * @var string
	 */
	private $base_path;

	/**
	 * Cached datasets
	 *
	 * @var array
	 */
	private $cache = [];

	/**
	 * Lazy-loading flags
	 *
	 * @var array
	 */
	private $loaded = [
		'advisor_dim_scores'  => false,
		'review_dim_scores'   => false,
		'reviews_clean'       => false,
		'partner_groups'      => false,
		'eda_summary'         => false,
		'coverage'            => false,
		'quality_summary'     => false,
		'raw_file_meta'       => false,
		'top_tokens'          => false,
		'top_bigrams'         => false,
		'metadata'            => false,
	];

	/**
	 * Constructor
	 *
	 * @param string $base_path Path to artifacts directory
	 */
	public function __construct( $base_path ) {
		// Ensure path ends with trailing slash
		$this->base_path = trailingslashit( $base_path );
	}

	// ====================================================================
	// CSV LOADING
	// ====================================================================

	/**
	 * Load CSV file and return as array of associative arrays
	 *
	 * @param string $file_path Path to CSV file (relative to base_path)
	 * @return array Array of associative arrays keyed by header row
	 */
	private function load_csv( $file_path ) {
		$full_path = $this->base_path . $file_path;

		if ( ! file_exists( $full_path ) ) {
			return [];
		}

		$data = [];
		$header = null;

		if ( ( $handle = fopen( $full_path, 'r' ) ) !== false ) {
			while ( ( $row = fgetcsv( $handle ) ) !== false ) {
				if ( null === $header ) {
					// First row is header
					$header = $row;
				} else {
					// Build associative array using header keys
					$assoc_row = [];
					foreach ( $header as $index => $key ) {
						$assoc_row[ $key ] = isset( $row[ $index ] ) ? $row[ $index ] : null;
					}
					$data[] = $assoc_row;
				}
			}
			fclose( $handle );
		}

		return $data;
	}

	/**
	 * Load and cache advisor dimension scores
	 *
	 * @return array Array of advisor score records
	 */
	public function load_advisor_dim_scores() {
		if ( $this->loaded['advisor_dim_scores'] ) {
			return $this->cache['advisor_dim_scores'];
		}

		$this->cache['advisor_dim_scores'] = $this->load_csv( 'scoring/advisor_dimension_scores.csv' );
		$this->loaded['advisor_dim_scores'] = true;

		return $this->cache['advisor_dim_scores'];
	}

	/**
	 * Load and cache review dimension scores
	 *
	 * @return array Array of review score records
	 */
	public function load_review_dim_scores() {
		if ( $this->loaded['review_dim_scores'] ) {
			return $this->cache['review_dim_scores'];
		}

		$this->cache['review_dim_scores'] = $this->load_csv( 'scoring/review_dimension_scores.csv' );
		$this->loaded['review_dim_scores'] = true;

		return $this->cache['review_dim_scores'];
	}

	/**
	 * Load and cache reviews_clean dataset
	 *
	 * @return array Array of review records
	 */
	public function load_reviews_clean() {
		if ( $this->loaded['reviews_clean'] ) {
			return $this->cache['reviews_clean'];
		}

		$this->cache['reviews_clean'] = $this->load_csv( 'macro_insights/reviews_clean.csv' );
		$this->loaded['reviews_clean'] = true;

		return $this->cache['reviews_clean'];
	}

	/**
	 * Load and cache partner groups
	 *
	 * @return array Array of partner group records
	 */
	public function load_partner_groups() {
		if ( $this->loaded['partner_groups'] ) {
			return $this->cache['partner_groups'];
		}

		$this->cache['partner_groups'] = $this->load_csv( 'scoring/partner_groups_mock.csv' );
		$this->loaded['partner_groups'] = true;

		return $this->cache['partner_groups'];
	}

	/**
	 * Load JSON file
	 *
	 * @param string $file_path Path to JSON file (relative to base_path)
	 * @return array Decoded JSON as array
	 */
	private function load_json( $file_path ) {
		$full_path = $this->base_path . $file_path;

		if ( ! file_exists( $full_path ) ) {
			return [];
		}

		$content = file_get_contents( $full_path );
		return json_decode( $content, true ) ?: [];
	}

	// ====================================================================
	// METADATA
	// ====================================================================

	/**
	 * Get pipeline metadata
	 *
	 * @return array Metadata from metadata.json
	 */
	public function get_metadata() {
		if ( $this->loaded['metadata'] ) {
			return $this->cache['metadata'];
		}

		$this->cache['metadata'] = $this->load_json( 'metadata.json' );
		$this->loaded['metadata'] = true;

		return $this->cache['metadata'];
	}

	// ====================================================================
	// CORE HELPER METHODS
	// ====================================================================

	/**
	 * Get dimensions array
	 *
	 * @return array Dimension keys
	 */
	private function get_dimensions() {
		return wt_get_dimensions();
	}

	/**
	 * Calculate percentile for a value within a peer group
	 *
	 * @param float $value The value to rank
	 * @param array $peer_values All peer values
	 * @return float Percentile (0-100)
	 */
	private function calculate_percentile( $value, $peer_values ) {
		if ( empty( $peer_values ) ) {
			return 0;
		}

		$count_below = 0;
		foreach ( $peer_values as $peer_value ) {
			if ( (float) $peer_value < (float) $value ) {
				$count_below++;
			}
		}

		return ( $count_below / count( $peer_values ) ) * 100;
	}

	/**
	 * Calculate percentile value (P25, P50, P75)
	 *
	 * @param array $values Sorted array of numeric values
	 * @param float $percentile Percentile to calculate (0-100)
	 * @return float|null Percentile value or null if empty
	 */
	private function calculate_percentile_value( $values, $percentile ) {
		if ( empty( $values ) ) {
			return null;
		}

		$sorted = array_map( 'floatval', $values );
		sort( $sorted );

		$count = count( $sorted );
		$position = ( $percentile / 100 ) * ( $count - 1 );
		$lower_index = (int) floor( $position );
		$upper_index = (int) ceil( $position );

		if ( $lower_index === $upper_index ) {
			return $sorted[ $lower_index ];
		}

		$lower_value = $sorted[ $lower_index ];
		$upper_value = $sorted[ $upper_index ];
		$weight = $position - $lower_index;

		return $lower_value + ( $upper_value - $lower_value ) * $weight;
	}

	/**
	 * Min-max normalize a value within a range
	 *
	 * @param float $value Value to normalize
	 * @param float $min Minimum value in range
	 * @param float $max Maximum value in range
	 * @return float Normalized value (0-100)
	 */
	private function normalize_minmax( $value, $min, $max ) {
		if ( $max - $min == 0 ) {
			return 50; // Return middle value if no range
		}
		return ( ( (float) $value - $min ) / ( $max - $min ) ) * 100;
	}

	/**
	 * Get performance tier based on percentile
	 *
	 * @param float $percentile Percentile rank (0-100)
	 * @return string Performance tier
	 */
	private function get_tier( $percentile ) {
		if ( $percentile >= 75 ) {
			return 'Very Strong';
		}
		if ( $percentile >= 50 ) {
			return 'Strong';
		}
		if ( $percentile >= 25 ) {
			return 'Moderate';
		}
		return 'Foundational';
	}

	// ====================================================================
	// ENTITY LISTING
	// ====================================================================

	/**
	 * Get list of all entities (firms and advisors)
	 *
	 * @return array Array with 'firms' and 'advisors' keys
	 */
	public function get_entity_list() {
		$advisor_scores = $this->load_advisor_dim_scores();

		$firms = [];
		$advisors = [];
		$seen_firms = [];
		$seen_advisors = [];

		foreach ( $advisor_scores as $row ) {
			$entity_id = $row['advisor_id'];
			$entity_type = $row['entity_type'];
			$entity_name = $row['advisor_name'];
			$review_count = $row['review_count'];

			if ( 'firm' === $entity_type && ! isset( $seen_firms[ $entity_id ] ) ) {
				$firms[] = [
					'entity_id'    => $entity_id,
					'entity_name'  => $entity_name,
					'entity_type'  => $entity_type,
					'review_count' => (int) $review_count,
				];
				$seen_firms[ $entity_id ] = true;
			} elseif ( 'advisor' === $entity_type && ! isset( $seen_advisors[ $entity_id ] ) ) {
				$advisors[] = [
					'entity_id'    => $entity_id,
					'entity_name'  => $entity_name,
					'entity_type'  => $entity_type,
					'review_count' => (int) $review_count,
				];
				$seen_advisors[ $entity_id ] = true;
			}
		}

		return [
			'firms'    => $firms,
			'advisors' => $advisors,
		];
	}

	// ====================================================================
	// ADVISOR DNA
	// ====================================================================

	/**
	 * Get random sample of review-level scores
	 *
	 * @param int $n Number of reviews to sample
	 * @return array Array of review scores
	 */
	public function dna_macro_sample( $n = 100 ) {
		$review_scores = $this->load_review_dim_scores();

		if ( count( $review_scores ) <= $n ) {
			return $review_scores;
		}

		$indices = array_rand( $review_scores, $n );
		$sample = [];

		foreach ( (array) $indices as $index ) {
			$sample[] = $review_scores[ $index ];
		}

		return $sample;
	}

	/**
	 * Get aggregate review-level totals by dimension
	 *
	 * @param int $min_peer_reviews Minimum reviews to include entity
	 * @return array Aggregated dimension totals and statistics
	 */
	public function dna_macro_totals( $min_peer_reviews = 0 ) {
		$review_scores = $this->load_review_dim_scores();
		$advisor_scores = $this->load_advisor_dim_scores();

		// Build set of qualifying advisor_ids
		$qualifying_ids = [];
		foreach ( $advisor_scores as $row ) {
			if ( (int) $row['review_count'] >= $min_peer_reviews ) {
				$qualifying_ids[ $row['advisor_id'] ] = true;
			}
		}

		$dimensions = $this->get_dimensions();
		$dimension_totals = [];

		foreach ( $dimensions as $dim ) {
			$dimension_totals[ $dim ] = [
				'total' => 0,
				'count' => 0,
			];
		}

		$total_review_count = 0;

		foreach ( $review_scores as $row ) {
			// Check if entity qualifies (review CSV uses advisor_id)
			if ( ! isset( $qualifying_ids[ $row['advisor_id'] ] ) ) {
				continue;
			}

			$total_review_count++;

			foreach ( $dimensions as $dim ) {
				$col = 'sim_' . $dim;
				$score = isset( $row[ $col ] ) ? (float) $row[ $col ] : 0;
				$dimension_totals[ $dim ]['total'] += $score;
				$dimension_totals[ $dim ]['count']++;
			}
		}

		// Compute means
		foreach ( $dimensions as $dim ) {
			if ( $dimension_totals[ $dim ]['count'] > 0 ) {
				$dimension_totals[ $dim ]['mean'] = $dimension_totals[ $dim ]['total'] / $dimension_totals[ $dim ]['count'];
			} else {
				$dimension_totals[ $dim ]['mean'] = 0;
			}
		}

		return [
			'dimensions'   => $dimension_totals,
			'review_count' => $total_review_count,
		];
	}

	/**
	 * Get raw scores for an entity by method
	 *
	 * @param string $entity_id Entity identifier
	 * @param string $method Scoring method: 'mean', 'penalized', 'weighted'
	 * @return array Dimension scores keyed by dimension
	 */
	public function dna_advisor_scores( $entity_id, $method = 'mean' ) {
		$advisor_scores = $this->load_advisor_dim_scores();
		$dimensions = $this->get_dimensions();

		// Find entity record
		$entity_row = null;
		foreach ( $advisor_scores as $row ) {
			if ( $row['advisor_id'] === $entity_id ) {
				$entity_row = $row;
				break;
			}
		}

		if ( null === $entity_row ) {
			return [];
		}

		$scores = [];
		foreach ( $dimensions as $dim ) {
			$col = 'sim_' . $method . '_' . $dim;
			$scores[ $dim ] = isset( $entity_row[ $col ] ) ? (float) $entity_row[ $col ] : 0;
		}

		return $scores;
	}

	/**
	 * Get percentile ranks for an entity within peer group
	 *
	 * @param string $entity_id Entity identifier
	 * @param string $method Scoring method: 'mean', 'penalized', 'weighted'
	 * @param int $min_peer_reviews Minimum reviews for peer qualification
	 * @return array Dimension percentiles keyed by dimension
	 */
	public function dna_percentile_scores( $entity_id, $method = 'mean', $min_peer_reviews = 0 ) {
		$advisor_scores = $this->load_advisor_dim_scores();
		$dimensions = $this->get_dimensions();

		// Find entity and its type
		$entity_row = null;
		foreach ( $advisor_scores as $row ) {
			if ( $row['advisor_id'] === $entity_id ) {
				$entity_row = $row;
				break;
			}
		}

		if ( null === $entity_row ) {
			return [];
		}

		$entity_type = $entity_row['entity_type'];

		// Build peer group (same type, qualifying review count)
		$peer_values = [];
		foreach ( $dimensions as $dim ) {
			$peer_values[ $dim ] = [];
		}

		foreach ( $advisor_scores as $row ) {
			if ( $row['entity_type'] !== $entity_type ) {
				continue;
			}
			if ( (int) $row['review_count'] < $min_peer_reviews ) {
				continue;
			}

			foreach ( $dimensions as $dim ) {
				$col = 'sim_' . $method . '_' . $dim;
				if ( isset( $row[ $col ] ) ) {
					$peer_values[ $dim ][] = (float) $row[ $col ];
				}
			}
		}

		// Calculate percentiles
		$percentiles = [];
		foreach ( $dimensions as $dim ) {
			$col = 'sim_' . $method . '_' . $dim;
			$entity_score = isset( $entity_row[ $col ] ) ? (float) $entity_row[ $col ] : 0;
			$percentiles[ $dim ] = $this->calculate_percentile( $entity_score, $peer_values[ $dim ] );
		}

		return $percentiles;
	}

	/**
	 * Get breakpoints (P25, P50, P75) for a peer group by dimension
	 *
	 * @param string $method Scoring method: 'mean', 'penalized', 'weighted'
	 * @param string|null $entity_type Filter by type: 'advisor', 'firm', or null for all
	 * @return array Breakpoints keyed by dimension
	 */
	public function dna_method_breakpoints( $method = 'mean', $entity_type = null ) {
		$advisor_scores = $this->load_advisor_dim_scores();
		$dimensions = $this->get_dimensions();

		$values = [];
		foreach ( $dimensions as $dim ) {
			$values[ $dim ] = [];
		}

		foreach ( $advisor_scores as $row ) {
			if ( null !== $entity_type && $row['entity_type'] !== $entity_type ) {
				continue;
			}

			foreach ( $dimensions as $dim ) {
				$col = 'sim_' . $method . '_' . $dim;
				if ( isset( $row[ $col ] ) ) {
					$values[ $dim ][] = (float) $row[ $col ];
				}
			}
		}

		$breakpoints = [];
		foreach ( $dimensions as $dim ) {
			sort( $values[ $dim ] );
			$breakpoints[ $dim ] = [
				'p25' => $this->calculate_percentile_value( $values[ $dim ], 25 ),
				'p50' => $this->calculate_percentile_value( $values[ $dim ], 50 ),
				'p75' => $this->calculate_percentile_value( $values[ $dim ], 75 ),
			];
		}

		return $breakpoints;
	}

	/**
	 * Get all reviews for an entity
	 *
	 * @param string $entity_id Entity identifier
	 * @return array Array of review records
	 */
	public function dna_entity_reviews( $entity_id ) {
		$review_scores = $this->load_review_dim_scores();
		$advisor_scores = $this->load_advisor_dim_scores();

		// Determine entity type
		$entity_type = null;
		foreach ( $advisor_scores as $row ) {
			if ( $row['advisor_id'] === $entity_id ) {
				$entity_type = $row['entity_type'];
				break;
			}
		}

		if ( null === $entity_type ) {
			return [];
		}

		$reviews = [];

		// For both advisor and firm entities, match on advisor_id in the review CSV
		// Firm-level entities aggregate reviews from all advisors at that firm URL
		foreach ( $review_scores as $row ) {
			if ( $row['advisor_id'] === $entity_id ) {
				$reviews[] = $row;
			}
		}

		return $reviews;
	}

	/**
	 * Get single review detail
	 *
	 * @param int $review_idx Review index
	 * @return array Review record or empty array
	 */
	public function dna_review_detail( $review_idx ) {
		$review_scores = $this->load_review_dim_scores();

		foreach ( $review_scores as $row ) {
			if ( (int) $row['review_idx'] === (int) $review_idx ) {
				return $row;
			}
		}

		return [];
	}

	/**
	 * Get review detail from reviews_clean (EDA dataset)
	 * Falls back to review_dimension_scores if not found
	 *
	 * @param int $review_idx Review index
	 * @return array Review record or empty array
	 */
	public function eda_review_detail( $review_idx ) {
		// First try reviews_clean (EDA source) — search by ID column
		// reviews_clean.csv uses 'ID' as its primary key, not 'review_idx'
		$reviews = $this->load_reviews_clean();

		foreach ( $reviews as $row ) {
			if ( isset( $row['ID'] ) && (string) $row['ID'] === (string) $review_idx ) {
				return $row;
			}
		}

		// Then try review_idx column (in case CSV format changes)
		foreach ( $reviews as $row ) {
			if ( isset( $row['review_idx'] ) && (int) $row['review_idx'] === (int) $review_idx ) {
				return $row;
			}
		}

		// Fallback to scored reviews (review_dimension_scores.csv)
		return $this->dna_review_detail( $review_idx );
	}

	/**
	 * Return a paginated list of reviews for the All Reviews browse panel.
	 *
	 * @param int $offset Starting index (0-based).
	 * @param int $limit  Number of reviews to return.
	 * @return array {items: array, total: int, offset: int, limit: int}
	 */
	public function all_reviews_list( $offset = 0, $limit = 10 ) {
		$all_rows = $this->load_review_dim_scores();
		$total    = count( $all_rows );

		// Build a lookup from reviews_clean for reviewer_name and review_date.
		// The two CSVs use different ID systems (review_idx vs WordPress post ID),
		// so we match on advisor_id + first 80 chars of review_text_raw.
		$clean_rows = $this->load_reviews_clean();
		$clean_lookup = [];
		foreach ( $clean_rows as $cr ) {
			$aid  = isset( $cr['advisor_id'] ) ? trim( $cr['advisor_id'] ) : '';
			$text = isset( $cr['review_text_raw'] ) ? substr( trim( $cr['review_text_raw'] ), 0, 80 ) : '';
			if ( $aid && $text ) {
				$clean_lookup[ $aid . '|' . $text ] = $cr;
			}
		}

		// Sort by review_idx ascending (review_idx 0 = newest in the pipeline output)
		usort( $all_rows, function ( $a, $b ) {
			return (int) $a['review_idx'] - (int) $b['review_idx'];
		} );

		// Lightweight projection — only send what the list panel needs
		$slim = [];
		foreach ( array_slice( $all_rows, $offset, $limit ) as $row ) {
			$idx  = isset( $row['review_idx'] ) ? (int) $row['review_idx'] : null;
			$aid  = isset( $row['advisor_id'] ) ? trim( $row['advisor_id'] ) : '';
			$text = isset( $row['review_text_raw'] ) ? substr( trim( $row['review_text_raw'] ), 0, 80 ) : '';
			$key  = $aid . '|' . $text;
			$clean = isset( $clean_lookup[ $key ] ) ? $clean_lookup[ $key ] : [];

			$slim[] = [
				'review_idx'    => $idx,
				'advisor_name'  => $row['advisor_name'] ?? '',
				'reviewer_name' => ! empty( $clean['reviewer_name'] ) ? $clean['reviewer_name'] : null,
				'review_date'   => ! empty( $clean['review_date'] ) ? $clean['review_date'] : null,
			];
		}

		return [
			'items'  => $slim,
			'total'  => $total,
			'offset' => $offset,
			'limit'  => $limit,
		];
	}

	/**
	 * Enrich scores with percentiles, normalization, and tiers
	 *
	 * @param string $entity_id Entity identifier
	 * @param string $method Scoring method: 'mean', 'penalized', 'weighted'
	 * @param int $min_peer_reviews Minimum reviews for peer qualification
	 * @return array Enriched scores for each dimension plus composite
	 */
	public function enrich_scores( $entity_id, $method = 'mean', $min_peer_reviews = 0 ) {
		$dimensions = $this->get_dimensions();

		// Get raw scores
		$raw_scores = $this->dna_advisor_scores( $entity_id, $method );
		if ( empty( $raw_scores ) ) {
			return [];
		}

		// Get percentiles
		$percentiles = $this->dna_percentile_scores( $entity_id, $method, $min_peer_reviews );

		// Get breakpoints for min/max normalization
		$advisor_scores = $this->load_advisor_dim_scores();
		$entity_type = null;
		foreach ( $advisor_scores as $row ) {
			if ( $row['advisor_id'] === $entity_id ) {
				$entity_type = $row['entity_type'];
				break;
			}
		}

		$breakpoints = $this->dna_method_breakpoints( $method, $entity_type );

		$enriched = [];

		// Enrich each dimension
		foreach ( $dimensions as $dim ) {
			$raw = $raw_scores[ $dim ];
			$percentile = $percentiles[ $dim ];

			// Get min/max from all values for normalization
			$all_values = [];
			foreach ( $advisor_scores as $row ) {
				if ( $row['entity_type'] === $entity_type ) {
					$col = 'sim_' . $method . '_' . $dim;
					if ( isset( $row[ $col ] ) ) {
						$all_values[] = (float) $row[ $col ];
					}
				}
			}

			$min = $all_values ? min( $all_values ) : 0;
			$max = $all_values ? max( $all_values ) : 100;

			$normalized = $this->normalize_minmax( $raw, $min, $max );
			$tier = $this->get_tier( $percentile );

			$enriched[ $dim ] = [
				'raw'        => round( $raw, 4 ),
				'percentile' => round( $percentile, 2 ),
				'normalized' => round( $normalized, 2 ),
				'tier'       => $tier,
				'min'        => round( $min, 4 ),
				'max'        => round( $max, 4 ),
			];
		}

		// Compute composite
		$composite_raw = array_sum( array_map( function( $d ) use ( $raw_scores ) {
			return $raw_scores[ $d ];
		}, $dimensions ) ) / count( $dimensions );

		// Compute composite percentile
		$composite_values = [];
		foreach ( $advisor_scores as $row ) {
			if ( $row['entity_type'] === $entity_type ) {
				$composite = 0;
				$count = 0;
				foreach ( $dimensions as $dim ) {
					$col = 'sim_' . $method . '_' . $dim;
					if ( isset( $row[ $col ] ) ) {
						$composite += (float) $row[ $col ];
						$count++;
					}
				}
				if ( $count > 0 ) {
					$composite_values[] = $composite / $count;
				}
			}
		}

		$composite_percentile = $this->calculate_percentile( $composite_raw, $composite_values );
		$composite_min = min( $composite_values ) ?: 0;
		$composite_max = max( $composite_values ) ?: 100;
		$composite_normalized = $this->normalize_minmax( $composite_raw, $composite_min, $composite_max );

		$enriched['composite'] = [
			'raw'        => round( $composite_raw, 4 ),
			'percentile' => round( $composite_percentile, 2 ),
			'normalized' => round( $composite_normalized, 2 ),
			'tier'       => $this->get_tier( $composite_percentile ),
			'min'        => round( $composite_min, 4 ),
			'max'        => round( $composite_max, 4 ),
		];

		return $enriched;
	}

	// ====================================================================
	// BENCHMARKS
	// ====================================================================

	/**
	 * Get benchmark pool statistics
	 *
	 * @param int $min_peer_reviews Minimum reviews to qualify as premier
	 * @return array Statistics for 'all' and 'premier' pools
	 */
	public function benchmark_pool_stats( $min_peer_reviews = 20 ) {
		$advisor_scores = $this->load_advisor_dim_scores();

		$all_count = 0;
		$all_firms = 0;
		$all_advisors = 0;
		$all_reviews_sum = 0;

		$premier_count = 0;
		$premier_firms = 0;
		$premier_advisors = 0;
		$premier_reviews_sum = 0;

		foreach ( $advisor_scores as $row ) {
			$review_count = (int) $row['review_count'];
			$entity_type = $row['entity_type'];

			$all_count++;
			$all_reviews_sum += $review_count;

			if ( 'firm' === $entity_type ) {
				$all_firms++;
			} else {
				$all_advisors++;
			}

			if ( $review_count >= $min_peer_reviews ) {
				$premier_count++;
				$premier_reviews_sum += $review_count;

				if ( 'firm' === $entity_type ) {
					$premier_firms++;
				} else {
					$premier_advisors++;
				}
			}
		}

		return [
			'all'     => [
				'total'        => $all_count,
				'firms'        => $all_firms,
				'advisors'     => $all_advisors,
				'avg_reviews'  => $all_count > 0 ? round( $all_reviews_sum / $all_count, 2 ) : 0,
			],
			'premier' => [
				'total'        => $premier_count,
				'firms'        => $premier_firms,
				'advisors'     => $premier_advisors,
				'avg_reviews'  => $premier_count > 0 ? round( $premier_reviews_sum / $premier_count, 2 ) : 0,
			],
		];
	}

	/**
	 * Get score distributions for benchmarking
	 *
	 * @param string $method Scoring method: 'mean', 'penalized', 'weighted'
	 * @param string|null $entity_type Filter by type: 'advisor', 'firm', or null for all
	 * @param int $min_peer_reviews Minimum reviews to include entity
	 * @return array Distributions keyed by dimension (array of raw scores)
	 */
	public function benchmark_distributions( $method = 'mean', $entity_type = null, $min_peer_reviews = 0 ) {
		$advisor_scores = $this->load_advisor_dim_scores();
		$dimensions = $this->get_dimensions();

		$distributions = [];
		foreach ( $dimensions as $dim ) {
			$distributions[ $dim ] = [];
		}

		foreach ( $advisor_scores as $row ) {
			if ( null !== $entity_type && $row['entity_type'] !== $entity_type ) {
				continue;
			}
			if ( (int) $row['review_count'] < $min_peer_reviews ) {
				continue;
			}

			foreach ( $dimensions as $dim ) {
				$col = 'sim_' . $method . '_' . $dim;
				if ( isset( $row[ $col ] ) ) {
					$distributions[ $dim ][] = (float) $row[ $col ];
				}
			}
		}

		return $distributions;
	}

	// ====================================================================
	// LEADERBOARD
	// ====================================================================

	/**
	 * Get top entities for leaderboard
	 *
	 * @param string $method Scoring method: 'mean', 'penalized', 'weighted'
	 * @param string|null $entity_type Filter by type: 'advisor', 'firm', or null for all
	 * @param int $min_peer_reviews Minimum reviews to include entity
	 * @param int $top_n Number of top entities to return
	 * @param string|null $dimension Specific dimension or null for all
	 * @return array Leaderboard data keyed by dimension
	 */
	public function leaderboard( $method = 'mean', $entity_type = null, $min_peer_reviews = 0, $top_n = 10, $dimension = null ) {
		$advisor_scores = $this->load_advisor_dim_scores();
		$dimensions = $this->get_dimensions();

		// Treat 'composite' as null — composite is computed, not a CSV column
		if ( 'composite' === $dimension ) {
			$dimension = null;
		}

		if ( null !== $dimension ) {
			$dimensions = [ $dimension ];
		}

		$leaderboard = [];

		foreach ( $dimensions as $dim ) {
			$candidates = [];

			foreach ( $advisor_scores as $row ) {
				if ( null !== $entity_type && $row['entity_type'] !== $entity_type ) {
					continue;
				}
				if ( (int) $row['review_count'] < $min_peer_reviews ) {
					continue;
				}

				$col = 'sim_' . $method . '_' . $dim;
				if ( ! isset( $row[ $col ] ) ) {
					continue;
				}

				$score = (float) $row[ $col ];
				$entity_id = $row['advisor_id'];

				// Get enriched scores
				$enriched = $this->enrich_scores( $entity_id, $method, $min_peer_reviews );

				$candidates[] = [
					'entity_id'    => $entity_id,
					'entity_name'  => $row['advisor_name'],
					'entity_type'  => $row['entity_type'],
					'review_count' => (int) $row['review_count'],
					'score'        => round( $score, 4 ),
					'enriched'     => $enriched[ $dim ] ?? [],
				];
			}

			// Sort by score descending
			usort( $candidates, function( $a, $b ) {
				return $b['score'] <=> $a['score'];
			} );

			// Trim to top_n
			$leaderboard[ $dim ] = array_slice( $candidates, 0, $top_n );
		}

		// Add composite if not filtered by dimension
		if ( null === $dimension ) {
			$composite_candidates = [];

			foreach ( $advisor_scores as $row ) {
				if ( null !== $entity_type && $row['entity_type'] !== $entity_type ) {
					continue;
				}
				if ( (int) $row['review_count'] < $min_peer_reviews ) {
					continue;
				}

				$entity_id = $row['advisor_id'];
				$enriched = $this->enrich_scores( $entity_id, $method, $min_peer_reviews );
				$composite_score = $enriched['composite']['raw'] ?? 0;

				$composite_candidates[] = [
					'entity_id'    => $entity_id,
					'entity_name'  => $row['advisor_name'],
					'entity_type'  => $row['entity_type'],
					'review_count' => (int) $row['review_count'],
					'score'        => round( $composite_score, 4 ),
					'enriched'     => $enriched['composite'] ?? [],
				];
			}

			usort( $composite_candidates, function( $a, $b ) {
				return $b['score'] <=> $a['score'];
			} );

			$leaderboard['composite'] = array_slice( $composite_candidates, 0, $top_n );
		}

		return $leaderboard;
	}

	// ====================================================================
	// COMPARISONS
	// ====================================================================

	/**
	 * Get list of partner groups
	 *
	 * @return array List of groups with code, name, and member count
	 */
	public function get_partner_groups() {
		$partner_groups = $this->load_partner_groups();

		// Get unique groups and count members
		$groups = [];
		$group_members = [];

		foreach ( $partner_groups as $pg ) {
			$group_code = $pg['partner_group_code'];

			if ( ! isset( $group_members[ $group_code ] ) ) {
				$group_members[ $group_code ] = [];
			}
			$group_members[ $group_code ][] = $pg['advisor_id'];
		}

		// Build group list with names and counts
		foreach ( $partner_groups as $pg ) {
			$group_code = $pg['partner_group_code'];

			if ( ! isset( $groups[ $group_code ] ) ) {
				$groups[ $group_code ] = [
					'group_code'   => $group_code,
					'group_name'   => $pg['partner_group_name'] ?? $group_code,
					'member_count' => count( $group_members[ $group_code ] ?? [] ),
				];
			}
		}

		return array_values( $groups );
	}

	/**
	 * Get members of a partner group with enriched scores
	 *
	 * @param string $group_code Group identifier
	 * @param string $method Scoring method: 'mean', 'penalized', 'weighted'
	 * @return array Group name and array of member scores
	 */
	public function get_partner_group_members( $group_code, $method = 'mean' ) {
		$partner_groups = $this->load_partner_groups();
		$advisor_scores = $this->load_advisor_dim_scores();

		$group_name = '';
		$member_ids = [];

		// Find all members of group
		foreach ( $partner_groups as $pg ) {
			if ( $pg['partner_group_code'] === $group_code ) {
				$group_name = $pg['partner_group_name'] ?? $group_code;
				$member_ids[] = $pg['advisor_id'];
			}
		}

		$members = [];

		foreach ( $advisor_scores as $row ) {
			if ( ! in_array( $row['advisor_id'], $member_ids, true ) ) {
				continue;
			}

			$entity_id = $row['advisor_id'];
			$enriched = $this->enrich_scores( $entity_id, $method );

			$members[] = [
				'entity_id'    => $entity_id,
				'entity_name'  => $row['advisor_name'],
				'entity_type'  => $row['entity_type'],
				'review_count' => (int) $row['review_count'],
				'enriched'     => $enriched,
			];
		}

		return [
			'group_name' => $group_name,
			'members'    => $members,
		];
	}

	/**
	 * Compare multiple entities
	 *
	 * @param array $entity_ids Array of entity identifiers
	 * @param string $method Scoring method: 'mean', 'penalized', 'weighted'
	 * @return array Array of enriched scores for each entity
	 */
	public function entity_comparison( $entity_ids, $method = 'mean' ) {
		$advisor_scores = $this->load_advisor_dim_scores();

		$comparison = [];

		foreach ( $entity_ids as $entity_id ) {
			// Find entity
			$entity_row = null;
			foreach ( $advisor_scores as $row ) {
				if ( $row['advisor_id'] === $entity_id ) {
					$entity_row = $row;
					break;
				}
			}

			if ( null === $entity_row ) {
				continue;
			}

			$enriched = $this->enrich_scores( $entity_id, $method );

			$comparison[] = [
				'entity_id'    => $entity_id,
				'entity_name'  => $entity_row['advisor_name'],
				'entity_type'  => $entity_row['entity_type'],
				'review_count' => (int) $entity_row['review_count'],
				'enriched'     => $enriched,
			];
		}

		return $comparison;
	}

	/**
	 * Head-to-head comparison of two entities
	 *
	 * @param string $entity_a First entity identifier
	 * @param string $entity_b Second entity identifier
	 * @param string $method Scoring method: 'mean', 'penalized', 'weighted'
	 * @return array Comparison data for both entities
	 */
	public function head_to_head( $entity_a, $entity_b, $method = 'mean' ) {
		return $this->entity_comparison( [ $entity_a, $entity_b ], $method );
	}

	// ====================================================================
	// EDA
	// ====================================================================

	/**
	 * Generate comprehensive EDA payload
	 *
	 * Filters reviews based on parameters and generates summary statistics,
	 * distributions, and lexical analysis.
	 *
	 * @param array $params Filter and analysis parameters
	 * @return array Comprehensive EDA data
	 */
	public function eda_payload( $params = [] ) {
		$reviews_clean = $this->load_reviews_clean();

		// Parse parameters
		$scope = $params['scope'] ?? null;
		$firm_id = $params['firm_id'] ?? null;
		$advisor_id = $params['advisor_id'] ?? null;
		$date_start = $params['date_start'] ?? null;
		$date_end = $params['date_end'] ?? null;
		$rating = $params['rating'] ?? null;
		$min_tokens = $params['min_tokens'] ?? 0;
		$max_tokens = $params['max_tokens'] ?? PHP_INT_MAX;
		$min_reviews_per_advisor = $params['min_reviews_per_advisor'] ?? 0;
		$max_reviews_per_advisor = $params['max_reviews_per_advisor'] ?? PHP_INT_MAX;
		$lexical_n = $params['lexical_n'] ?? 1;
		$lexical_top_n = $params['lexical_top_n'] ?? 30;
		$exclude_stopwords = $params['exclude_stopwords'] ?? true;
		$custom_stopwords = $params['custom_stopwords'] ?? [];
		$time_freq = $params['time_freq'] ?? 'month'; // month, quarter, year

		// Build stopwords set
		$stopwords_set = [];
		if ( $exclude_stopwords ) {
			$stopwords = wt_get_stopwords();
			$stopwords_set = array_flip( $stopwords );
		}
		foreach ( $custom_stopwords as $word ) {
			$stopwords_set[ strtolower( $word ) ] = true;
		}

		// Filter reviews
		$filtered_reviews = [];
		$advisor_review_counts = [];

		foreach ( $reviews_clean as $review ) {
			// Entity filters (reviews_clean has advisor_id but no firm_id)
			if ( null !== $advisor_id && $review['advisor_id'] !== $advisor_id ) {
				continue;
			}

			// Date range filter (column is review_date)
			$review_date = $review['review_date'] ?? $review['Date'] ?? '';
			if ( null !== $date_start && $review_date < $date_start ) {
				continue;
			}
			if ( null !== $date_end && $review_date > $date_end ) {
				continue;
			}

			// Rating filter
			if ( null !== $rating && (int) $review['rating'] !== (int) $rating ) {
				continue;
			}

			// Token count filter
			$token_count = (int) $review['clean_token_count'];
			if ( $token_count < $min_tokens || $token_count > $max_tokens ) {
				continue;
			}

			$filtered_reviews[] = $review;

			// Count reviews per advisor
			$advisor = $review['advisor_id'];
			if ( ! isset( $advisor_review_counts[ $advisor ] ) ) {
				$advisor_review_counts[ $advisor ] = 0;
			}
			$advisor_review_counts[ $advisor ]++;
		}

		// Apply reviews-per-advisor filter
		$final_reviews = [];
		foreach ( $filtered_reviews as $review ) {
			$advisor = $review['advisor_id'];
			$count = $advisor_review_counts[ $advisor ];

			if ( $count >= $min_reviews_per_advisor && $count <= $max_reviews_per_advisor ) {
				$final_reviews[] = $review;
			}
		}

		// Build EDA payload
		$payload = [];

		// 1. Summary
		$payload['summary'] = [
			'review_count'          => count( $final_reviews ),
			'advisor_count'         => count( array_unique( array_map( function( $r ) { return $r['advisor_id']; }, $final_reviews ) ) ),
			'pct_under_20_tokens'   => count( array_filter( $final_reviews, function( $r ) { return (int) $r['clean_token_count'] < 20; } ) ) / max( 1, count( $final_reviews ) ) * 100,
			'pct_under_50_tokens'   => count( array_filter( $final_reviews, function( $r ) { return (int) $r['clean_token_count'] < 50; } ) ) / max( 1, count( $final_reviews ) ) * 100,
		];

		// 2. Coverage
		$total_advisors = count( $advisor_review_counts );
		$under_3 = count( array_filter( $advisor_review_counts, function( $c ) { return $c < 3; } ) );
		$under_5 = count( array_filter( $advisor_review_counts, function( $c ) { return $c < 5; } ) );
		$under_10 = count( array_filter( $advisor_review_counts, function( $c ) { return $c < 10; } ) );

		$payload['coverage'] = [
			'total_advisors'    => $total_advisors,
			'pct_under_3'       => $total_advisors > 0 ? ( $under_3 / $total_advisors * 100 ) : 0,
			'pct_under_5'       => $total_advisors > 0 ? ( $under_5 / $total_advisors * 100 ) : 0,
			'pct_under_10'      => $total_advisors > 0 ? ( $under_10 / $total_advisors * 100 ) : 0,
		];

		// 3. Quality (from quality_summary.json)
		$payload['quality'] = $this->load_json( 'macro_insights/quality/quality_summary.json' );

		// 4. Meta
		$token_counts = array_map( function( $r ) { return (int) $r['clean_token_count']; }, $final_reviews );
		sort( $token_counts );

		$dates = array_map( function( $r ) { return $r['review_date'] ?? $r['Date'] ?? ''; }, $final_reviews );
		$dates = array_filter( $dates );
		sort( $dates );

		$payload['meta'] = [
			'date_min'            => $dates ? $dates[0] : null,
			'date_max'            => $dates ? $dates[ count( $dates ) - 1 ] : null,
			'token_min'           => $token_counts ? min( $token_counts ) : 0,
			'token_max'           => $token_counts ? max( $token_counts ) : 0,
			'token_q1'            => $this->calculate_percentile_value( $token_counts, 25 ),
			'token_q3'            => $this->calculate_percentile_value( $token_counts, 75 ),
			'reviews_per_advisor_min' => $advisor_review_counts ? min( $advisor_review_counts ) : 0,
			'reviews_per_advisor_max' => $advisor_review_counts ? max( $advisor_review_counts ) : 0,
			'reviews_per_advisor_q1' => $this->calculate_percentile_value( array_values( $advisor_review_counts ), 25 ),
			'reviews_per_advisor_q3' => $this->calculate_percentile_value( array_values( $advisor_review_counts ), 75 ),
		];

		// 5. Rating distribution
		$rating_dist = [];
		foreach ( $final_reviews as $review ) {
			$r = (int) $review['rating'];
			if ( ! isset( $rating_dist[ $r ] ) ) {
				$rating_dist[ $r ] = 0;
			}
			$rating_dist[ $r ]++;
		}
		ksort( $rating_dist );
		$payload['rating_distribution'] = $rating_dist;

		// 6. Reviews over time
		$time_buckets = [];
		foreach ( $final_reviews as $review ) {
			$date = $review['review_date'] ?? $review['Date'] ?? '';
			if ( empty( $date ) ) continue;
			$bucket = $this->get_time_bucket( $date, $time_freq );

			if ( ! isset( $time_buckets[ $bucket ] ) ) {
				$time_buckets[ $bucket ] = 0;
			}
			$time_buckets[ $bucket ]++;
		}
		ksort( $time_buckets );
		$payload['reviews_over_time'] = $time_buckets;

		// 7. Reviews per advisor
		$reviews_per_advisor = [];
		foreach ( $advisor_review_counts as $advisor => $count ) {
			$reviews_per_advisor[] = [ 'advisor_id' => $advisor, 'count' => $count ];
		}
		$payload['reviews_per_advisor'] = $reviews_per_advisor;

		// 8. Token counts (array)
		$payload['token_counts'] = array_values( array_map( function( $r ) { return (int) $r['clean_token_count']; }, $final_reviews ) );

		// 9. Rating vs token (scatter)
		$rating_vs_token = [];
		foreach ( $final_reviews as $review ) {
			$rating_vs_token[] = [
				'review_idx'      => $review['ID'] ?? $review['review_idx'] ?? 0,
				'rating'          => (float) $review['rating'],
				'token_count'     => (int) $review['clean_token_count'],
			];
		}
		$payload['rating_vs_token'] = $rating_vs_token;

		// 10. Lexical analysis
		$ngrams = $this->extract_ngrams( $final_reviews, $lexical_n, $stopwords_set );
		$top_ngrams = $this->get_top_ngrams( $ngrams, $lexical_top_n );
		$payload['lexical'] = $top_ngrams;

		return $payload;
	}

	/**
	 * Get time bucket for a date
	 *
	 * @param string $date ISO date string
	 * @param string $freq Frequency: 'month', 'quarter', 'year'
	 * @return string Bucket identifier
	 */
	private function get_time_bucket( $date, $freq ) {
		$year = substr( $date, 0, 4 );
		$month = substr( $date, 5, 2 );

		if ( 'year' === $freq ) {
			return $year;
		}
		if ( 'quarter' === $freq ) {
			$quarter = ceil( $month / 3 );
			return $year . '-Q' . $quarter;
		}
		// month
		return $year . '-' . $month;
	}

	/**
	 * Extract n-grams from reviews
	 *
	 * @param array $reviews Array of review records
	 * @param int $n N-gram size
	 * @param array $stopwords_set Stopwords keyed by word
	 * @return array Frequency map of n-grams
	 */
	private function extract_ngrams( $reviews, $n, $stopwords_set ) {
		$ngrams = [];

		foreach ( $reviews as $review ) {
			$text = $review['review_text_clean'] ?? '';

			// Tokenize: split on non-alphanumeric, lowercase, filter stopwords
			$tokens = [];
			$words = preg_split( '/[^a-z0-9]+/i', $text );

			foreach ( $words as $word ) {
				$word = strtolower( trim( $word ) );
				if ( ! empty( $word ) && ! isset( $stopwords_set[ $word ] ) ) {
					$tokens[] = $word;
				}
			}

			// Build n-grams
			for ( $i = 0; $i <= count( $tokens ) - $n; $i++ ) {
				$ngram = implode( ' ', array_slice( $tokens, $i, $n ) );
				if ( ! isset( $ngrams[ $ngram ] ) ) {
					$ngrams[ $ngram ] = 0;
				}
				$ngrams[ $ngram ]++;
			}
		}

		return $ngrams;
	}

	/**
	 * Get top N n-grams by frequency
	 *
	 * @param array $ngrams Frequency map of n-grams
	 * @param int $top_n Number of top n-grams to return
	 * @return array Array of top n-grams with frequencies
	 */
	private function get_top_ngrams( $ngrams, $top_n ) {
		arsort( $ngrams );
		$top = array_slice( $ngrams, 0, $top_n, true );

		$result = [];
		foreach ( $top as $ngram => $freq ) {
			$result[] = [
				'ngram'     => $ngram,
				'frequency' => $freq,
			];
		}

		return $result;
	}
}

/**
 * Get global artifact store instance
 *
 * @return WTArtifactStore
 */
function wt_get_artifact_store() {
	static $store = null;

	if ( null === $store ) {
		$base_path = WEALTHTENDER_ANALYTICS_DIR . 'data/artifacts/';
		$store = new WTArtifactStore( $base_path );
	}

	return $store;
}

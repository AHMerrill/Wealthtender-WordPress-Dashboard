<?php
/**
 * Wealthtender Analytics Constants
 *
 * Defines all dimensional axes, color schemes, and lexical stopwords
 * used throughout the analytics dashboard.
 *
 * @package Wealthtender_Analytics
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core dimensions for advisor and firm scoring
 */
if ( ! function_exists( 'wt_get_dimensions' ) ) {
	function wt_get_dimensions() {
		return [
			'trust_integrity',
			'listening_personalization',
			'communication_clarity',
			'responsiveness_availability',
			'life_event_support',
			'investment_expertise',
		];
	}
}

/**
 * Human-readable dimension labels
 */
if ( ! function_exists( 'wt_get_dim_labels' ) ) {
	function wt_get_dim_labels() {
		return [
			'trust_integrity'              => 'Trust & Integrity',
			'listening_personalization'    => 'Customer Empathy & Personalization',
			'communication_clarity'        => 'Communication Clarity',
			'responsiveness_availability'  => 'Responsiveness',
			'life_event_support'           => 'Life Event Support',
			'investment_expertise'         => 'Investment Expertise',
		];
	}
}

/**
 * Short labels for compact display
 */
if ( ! function_exists( 'wt_get_dim_short' ) ) {
	function wt_get_dim_short() {
		return [
			'trust_integrity'              => 'Trust',
			'listening_personalization'    => 'Empathy',
			'communication_clarity'        => 'Clarity',
			'responsiveness_availability'  => 'Responsive',
			'life_event_support'           => 'Life Events',
			'investment_expertise'         => 'Expertise',
		];
	}
}

/**
 * Brand color palette
 */
if ( ! function_exists( 'wt_get_colors' ) ) {
	function wt_get_colors() {
		return [
			'blue'           => '#004C8C',
			'blue_light'     => '#529BD9',
			'navy'           => '#043466',
			'ink'            => '#111827',
			'gray'           => '#6b7280',
			'soft_blue'      => '#e3f5fe',
			'soft_lavender'  => '#ebebff',
			'red'            => '#790000',
			'bg'             => '#f8fbff',
			'border'         => '#e5e7eb',
			'card_bg'        => '#fafafa',
		];
	}
}

/**
 * Data visualization color palette
 */
if ( ! function_exists( 'wt_get_data_viz_palette' ) ) {
	function wt_get_data_viz_palette() {
		return [
			'#004C8C',
			'#D4376E',
			'#529BD9',
			'#7C3AED',
			'#043466',
			'#B8860B',
			'#6D2348',
			'#9F7AEA',
			'#3A7BBF',
			'#C4975C',
		];
	}
}

/**
 * Dimension-specific colors from palette
 */
if ( ! function_exists( 'wt_get_dim_colors' ) ) {
	function wt_get_dim_colors() {
		$palette = wt_get_data_viz_palette();
		return [
			'trust_integrity'              => $palette[0],
			'listening_personalization'    => $palette[1],
			'communication_clarity'        => $palette[2],
			'responsiveness_availability'  => $palette[3],
			'life_event_support'           => $palette[5],
			'investment_expertise'         => $palette[6],
		];
	}
}

/**
 * NLTK English stopwords (174 common English words)
 * Used for lexical analysis and text processing
 */
if ( ! function_exists( 'wt_get_stopwords' ) ) {
	function wt_get_stopwords() {
		static $stopwords = null;

		if ( null === $stopwords ) {
			$stopwords = [
				'a',
				'about',
				'above',
				'after',
				'again',
				'against',
				'ain',
				'all',
				'am',
				'an',
				'and',
				'any',
				'are',
				'aren',
				'as',
				'at',
				'be',
				'because',
				'been',
				'before',
				'being',
				'below',
				'between',
				'both',
				'but',
				'by',
				'can',
				'couldn',
				'd',
				'did',
				'didn',
				'do',
				'does',
				'doesn',
				'doing',
				'don',
				'down',
				'during',
				'each',
				'few',
				'for',
				'from',
				'further',
				'had',
				'hadn',
				'has',
				'hasn',
				'have',
				'haven',
				'having',
				'he',
				'her',
				'here',
				'hers',
				'herself',
				'him',
				'himself',
				'his',
				'how',
				'i',
				'if',
				'in',
				'into',
				'is',
				'isn',
				'it',
				'its',
				'itself',
				'just',
				'l',
				'll',
				'm',
				'ma',
				'me',
				'might',
				'mightn',
				'more',
				'most',
				'mustn',
				'my',
				'myself',
				'needn',
				'no',
				'nor',
				'not',
				'now',
				'o',
				'of',
				'off',
				'on',
				'only',
				'or',
				'other',
				'our',
				'ours',
				'ourselves',
				'out',
				'over',
				'own',
				're',
				's',
				'same',
				'shan',
				'she',
				'should',
				'shouldn',
				'so',
				'some',
				'such',
				't',
				'than',
				'that',
				'the',
				'their',
				'theirs',
				'them',
				'themselves',
				'then',
				'there',
				'these',
				'they',
				'this',
				'those',
				'through',
				'to',
				'too',
				'under',
				'until',
				'up',
				'very',
				've',
				'was',
				'wasn',
				'we',
				'were',
				'weren',
				'what',
				'when',
				'where',
				'which',
				'while',
				'who',
				'whom',
				'why',
				'will',
				'with',
				'won',
				'would',
				'wouldn',
				'y',
				'you',
				'your',
				'yours',
				'yourself',
				'yourselves',
			];
			sort( $stopwords );
		}

		return $stopwords;
	}
}

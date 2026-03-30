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
			'outcomes_results',
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
			'outcomes_results'            => 'Outcomes & Results',
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
			'outcomes_results'            => 'Outcomes',
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
			'outcomes_results'            => $palette[7],
		];
	}
}

/**
 * Short one-liner descriptions for dimension card grids
 */
if ( ! function_exists( 'wt_get_dim_descriptions' ) ) {
	function wt_get_dim_descriptions() {
		return [
			'trust_integrity'              => 'Clients feel confident their advisor acts honestly and in their best interest.',
			'listening_personalization'    => 'Advisors empathize with client needs and tailor plans to individual goals.',
			'communication_clarity'        => 'Complex financial concepts are explained in plain, understandable language.',
			'responsiveness_availability'  => 'Advisors are accessible and respond promptly to client needs.',
			'life_event_support'           => 'Guidance through major transitions — retirement, inheritance, career changes.',
			'investment_expertise'         => 'Demonstrated knowledge of markets, portfolios, and financial strategy.',
			'outcomes_results'             => 'Tangible results and measurable progress toward real-world financial goals.',
		];
	}
}

/**
 * Full canonical query texts — the "ideal review" each review is compared
 * against via sentence-embedding cosine similarity.
 */
if ( ! function_exists( 'wt_get_dim_query_texts' ) ) {
	function wt_get_dim_query_texts() {
		return [
			'trust_integrity'              => 'I feel a deep sense of security and peace of mind because my advisor acts as a true fiduciary, always putting my best interest before their own commissions or conflicts of interest. They have earned my trust through years of unwavering integrity, honesty, and transparency regarding fees and performance, proving they are an ethical, principled, and reliable professional with a stand-up character who protects my family\'s future and life savings.',
			'listening_personalization'    => 'My advisor genuinely empathizes with my situation, takes the time to understand my unique goals and risk tolerance, and makes me feel truly heard. They have built a highly personalized, custom-tailored financial plan and investment strategy that fits my specific circumstances, aspirations, and values, making me feel like a valued partner rather than just another account number or a sales target.',
			'communication_clarity'        => 'Complex financial concepts are made simple and digestible because my advisor is a master communicator who explains things clearly in plain English without using confusing technical jargon. They provide timely updates, regular check-ins, and transparent breakdowns of my portfolio, ensuring I am well-educated, fully informed, and confident in the logic and rationale behind every recommendation or financial decision.',
			'responsiveness_availability'  => 'The level of service is exceptional; they are always accessible, easy to reach, and promptly return calls or emails within hours, not days. Whether I have a quick question or an urgent concern during market volatility or a personal crisis, they are responsive, attentive, and reliable, providing the immediate support and availability I need to feel taken care of and less anxious about my liquidity and financial health.',
			'life_event_support'           => 'Beyond being a numbers person, they have been a compassionate counselor and supportive partner through major life transitions, including retirement, career changes, marriages, inheritance, or the loss of a loved one. They provide empathy, patience, and guidance during emotional times, offering perspective and hand-holding that goes far beyond a spreadsheet to address the human element and life context of my wealth management.',
			'investment_expertise'         => 'I have total confidence in their technical proficiency, investment pedigree, and deep market knowledge. They are a savvy, highly skilled professional with the credentials and expertise to navigate complex asset allocations, tax strategies, and market cycles. Their competence and strategic insight ensure my portfolio is well-positioned for long-term growth, wealth preservation, and solid returns that meet or exceed my financial expectations.',
			'outcomes_results'             => 'My advisor has delivered tangible results and measurable progress toward my real-world goals, ensuring I have achieved milestones like becoming debt-free, funding a college education, or reaching retirement readiness. They have successfully implemented my tax strategies, finalized estate documents, and consolidated my accounts, demonstrating the follow-through and execution needed to advance my financial plan, avoid costly mistakes, and effectively course-correct when the market or my life changed.',
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

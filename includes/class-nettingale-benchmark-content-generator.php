<?php
/**
 * Content Generator Class
 *
 * Generates deterministic content for posts and pages using seeded randomization
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_Content_Generator
 */
class Nettingale_Benchmark_Content_Generator {

	/**
	 * Generate post title based on ID
	 *
	 * Creates deterministic titles using topics and post ID
	 *
	 * @param int    $post_id Post ID.
	 * @param string $prefix  Optional prefix (e.g., 'Page:', 'Post:').
	 * @return string Generated title.
	 */
	public static function generate_title( $post_id, $prefix = '' ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $post_id );

		// Get topics
		$topics = Nettingale_Benchmark_Config::get_topics();

		// Select topic deterministically
		$topic = $topics[ $post_id % count( $topics ) ];

		// Get some random words for variety
		$words = Nettingale_Benchmark_Config::get_lorem_words();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$word_count = mt_rand( 2, 4 );
		$title_words = array();

		for ( $i = 0; $i < $word_count; $i++ ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
			$title_words[] = ucfirst( $words[ mt_rand( 0, count( $words ) - 1 ) ] );
		}

		// Construct title
		$title = $topic . ': ' . implode( ' ', $title_words );

		// Add prefix if provided
		if ( ! empty( $prefix ) ) {
			$title = $prefix . ' ' . $title;
		}

		return $title;
	}

	/**
	 * Generate post content based on ID
	 *
	 * Creates deterministic content with multiple paragraphs
	 *
	 * @param int $post_id        Post ID.
	 * @param int $paragraph_count Number of paragraphs (default: random 3-8).
	 * @return string Generated content.
	 */
	public static function generate_content( $post_id, $paragraph_count = null ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $post_id );

		// Get words bank
		$words = Nettingale_Benchmark_Config::get_lorem_words();

		// Determine paragraph count
		if ( null === $paragraph_count ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
			$paragraph_count = mt_rand( 3, 8 );
		}

		$paragraphs = array();

		for ( $p = 0; $p < $paragraph_count; $p++ ) {
			// Each paragraph has 5-10 sentences
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
			$sentence_count = mt_rand( 5, 10 );
			$sentences = array();

			for ( $s = 0; $s < $sentence_count; $s++ ) {
				// Each sentence has 8-15 words
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
				$word_count = mt_rand( 8, 15 );
				$sentence_words = array();

				for ( $w = 0; $w < $word_count; $w++ ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
					$word = $words[ mt_rand( 0, count( $words ) - 1 ) ];

					// Capitalize first word
					if ( 0 === $w ) {
						$word = ucfirst( $word );
					}

					$sentence_words[] = $word;
				}

				$sentences[] = implode( ' ', $sentence_words ) . '.';
			}

			$paragraphs[] = '<p>' . implode( ' ', $sentences ) . '</p>';
		}

		return implode( "\n\n", $paragraphs );
	}

	/**
	 * Generate post excerpt based on ID
	 *
	 * Creates deterministic excerpt (1-2 sentences)
	 *
	 * @param int $post_id Post ID.
	 * @return string Generated excerpt.
	 */
	public static function generate_excerpt( $post_id ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $post_id );

		// Get words bank
		$words = Nettingale_Benchmark_Config::get_lorem_words();

		// 1-2 sentences
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$sentence_count = mt_rand( 1, 2 );
		$sentences = array();

		for ( $s = 0; $s < $sentence_count; $s++ ) {
			// Each sentence has 10-20 words
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
			$word_count = mt_rand( 10, 20 );
			$sentence_words = array();

			for ( $w = 0; $w < $word_count; $w++ ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
				$word = $words[ mt_rand( 0, count( $words ) - 1 ) ];

				// Capitalize first word
				if ( 0 === $w ) {
					$word = ucfirst( $word );
				}

				$sentence_words[] = $word;
			}

			$sentences[] = implode( ' ', $sentence_words ) . '.';
		}

		return implode( ' ', $sentences );
	}

	/**
	 * Generate post slug based on ID
	 *
	 * Creates deterministic URL-friendly slug
	 *
	 * @param int    $post_id Post ID.
	 * @param string $prefix  Optional prefix.
	 * @return string Generated slug.
	 */
	public static function generate_slug( $post_id, $prefix = '' ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $post_id );

		// Get words bank
		$words = Nettingale_Benchmark_Config::get_lorem_words();

		// Use 3-5 words for slug
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$word_count = mt_rand( 3, 5 );
		$slug_words = array();

		for ( $i = 0; $i < $word_count; $i++ ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
			$slug_words[] = $words[ mt_rand( 0, count( $words ) - 1 ) ];
		}

		$slug = implode( '-', $slug_words );

		// Add prefix if provided
		if ( ! empty( $prefix ) ) {
			$slug = $prefix . '-' . $slug;
		}

		// Add post ID to ensure uniqueness
		$slug .= '-' . $post_id;

		return sanitize_title( $slug );
	}

	/**
	 * Generate post date based on ID
	 *
	 * Creates deterministic date within the last year
	 *
	 * @param int $post_id Post ID.
	 * @return string MySQL datetime format.
	 */
	public static function generate_date( $post_id ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $post_id );

		// Generate date within last 365 days
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$days_ago = mt_rand( 0, 365 );
		$timestamp = time() - ( $days_ago * DAY_IN_SECONDS );

		// Add some random hours/minutes for variety
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$timestamp -= mt_rand( 0, 86400 ); // Random time within the day

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Assign post author based on ID
	 *
	 * Deterministically assigns author from available users
	 *
	 * @param int   $post_id    Post ID.
	 * @param array $user_ids   Array of available user IDs.
	 * @return int User ID.
	 */
	public static function assign_author( $post_id, $user_ids ) {
		if ( empty( $user_ids ) ) {
			return get_current_user_id();
		}

		// Deterministic assignment
		return $user_ids[ $post_id % count( $user_ids ) ];
	}

	/**
	 * Assign categories to post based on ID
	 *
	 * Deterministically assigns 1-3 categories
	 *
	 * @param int   $post_id      Post ID.
	 * @param array $category_ids Array of available category IDs.
	 * @return array Category IDs to assign.
	 */
	public static function assign_categories( $post_id, $category_ids ) {
		if ( empty( $category_ids ) ) {
			return array();
		}

		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $post_id );

		// Assign 1-3 categories
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$count = min( mt_rand( 1, 3 ), count( $category_ids ) );
		$assigned = array();

                $category_ids = self::deterministic_shuffle( $category_ids );

                for ( $i = 0; $i < $count; $i++ ) {
                        $assigned[] = $category_ids[ $i ];
		}

		return $assigned;
	}

	/**
	 * Assign tags to post based on ID
	 *
	 * Deterministically assigns 3-8 tags
	 *
	 * @param int   $post_id Post ID.
	 * @param array $tag_ids Array of available tag IDs.
	 * @return array Tag IDs to assign.
	 */
	public static function assign_tags( $post_id, $tag_ids ) {
		if ( empty( $tag_ids ) ) {
			return array();
		}

		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $post_id );

		// Assign 3-8 tags
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$count = min( mt_rand( 3, 8 ), count( $tag_ids ) );
		$assigned = array();

                $tag_ids = self::deterministic_shuffle( $tag_ids );

                for ( $i = 0; $i < $count; $i++ ) {
                        $assigned[] = $tag_ids[ $i ];
                }

                return $assigned;
        }

        /**
         * Deterministically shuffle an array using the seeded random generator.
         *
         * Expects the caller to seed the PRNG via Nettingale_Benchmark_Config::seed_random().
         *
         * @param array $items Items to shuffle.
         * @return array Shuffled items.
         */
        private static function deterministic_shuffle( $items ) {
                if ( empty( $items ) ) {
                        return array();
                }

                $weights = array();

                foreach ( $items as $index => $value ) {
                        // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
                        $weights[ $index ] = mt_rand();
                }

                array_multisort( $weights, SORT_ASC, $items );

                return array_values( $items );
        }

	/**
	 * Generate comment count for post based on age
	 *
	 * Newer posts get more comments (simulates real behavior)
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_date Post date.
	 * @param int    $max_comments Maximum comments.
	 * @return int Number of comments.
	 */
	public static function calculate_comment_count( $post_id, $post_date, $max_comments = 50 ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $post_id );

		// Calculate post age in days
		$post_timestamp = strtotime( $post_date );
		$age_days = ( time() - $post_timestamp ) / DAY_IN_SECONDS;

		// Newer posts get more comments (decay formula)
		$base_count = max( 1, $max_comments - ( $age_days / 10 ) );

		// Add some randomness
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$count = (int) ( $base_count * ( mt_rand( 50, 150 ) / 100 ) );

		return max( 0, min( $count, $max_comments ) );
	}
}

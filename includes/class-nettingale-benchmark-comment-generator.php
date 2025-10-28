<?php
/**
 * Comment Generator Class
 *
 * Generates deterministic comments for benchmarking
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_Comment_Generator
 */
class Nettingale_Benchmark_Comment_Generator {

	/**
	 * Generate comment author name based on comment ID
	 *
	 * @param int $comment_id Virtual comment ID.
	 * @return string Author name.
	 */
	public static function generate_author_name( $comment_id ) {
		$names = Nettingale_Benchmark_Config::get_names();
		return $names[ $comment_id % count( $names ) ];
	}

	/**
	 * Generate comment author email based on comment ID
	 *
	 * @param int $comment_id Virtual comment ID.
	 * @return string Author email.
	 */
	public static function generate_author_email( $comment_id ) {
		$name = self::generate_author_name( $comment_id );
		$username = strtolower( str_replace( ' ', '_', $name ) );
		return sanitize_email( $username . '_' . $comment_id . '@commenter.test' );
	}

	/**
	 * Generate comment content based on comment ID
	 *
	 * @param int $comment_id Virtual comment ID.
	 * @return string Comment content.
	 */
	public static function generate_content( $comment_id ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $comment_id );

		// Get words bank
		$words = Nettingale_Benchmark_Config::get_lorem_words();

		// Comments are 2-5 sentences
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$sentence_count = mt_rand( 2, 5 );
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

		return implode( ' ', $sentences );
	}

	/**
	 * Generate comment date based on post date and comment ID
	 *
	 * Comments are spread within 30 days after post date
	 *
	 * @param int    $comment_id Virtual comment ID.
	 * @param string $post_date  Post date in MySQL format.
	 * @return string MySQL datetime format.
	 */
	public static function generate_date( $comment_id, $post_date ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $comment_id );

		// Get post timestamp
		$post_timestamp = strtotime( $post_date );

		// Comments appear within 30 days after post
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$days_after = mt_rand( 0, 30 );
		$timestamp = $post_timestamp + ( $days_after * DAY_IN_SECONDS );

		// Add some random hours/minutes
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$timestamp += mt_rand( 0, 86400 );

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Generate comment status
	 *
	 * Most comments are approved, some are pending
	 *
	 * @param int $comment_id Virtual comment ID.
	 * @return string Comment status (1 for approved, 0 for pending).
	 */
	public static function generate_status( $comment_id ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $comment_id );

		// 95% approved, 5% pending
		// WordPress expects '1' for approved, '0' for pending
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$rand = mt_rand( 1, 100 );
		return $rand <= 95 ? '1' : '0';
	}

	/**
	 * Determine if comment is a reply
	 *
	 * 20% of comments are replies to previous comments
	 *
	 * @param int $comment_id    Virtual comment ID.
	 * @param int $comment_index Comment index in the post (0-based).
	 * @return bool True if this should be a reply.
	 */
	public static function is_reply( $comment_id, $comment_index ) {
		// Can't be a reply if it's the first comment
		if ( 0 === $comment_index ) {
			return false;
		}

		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $comment_id );

		// 20% chance of being a reply
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		return mt_rand( 1, 100 ) <= 20;
	}

	/**
	 * Assign user ID to comment
	 *
	 * Some comments are from registered users, most are from guests
	 *
	 * @param int   $comment_id Virtual comment ID.
	 * @param array $user_ids   Array of available user IDs.
	 * @return int User ID or 0 for guest.
	 */
	public static function assign_user( $comment_id, $user_ids ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $comment_id );

		// 30% of comments are from registered users
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		if ( ! empty( $user_ids ) && mt_rand( 1, 100 ) <= 30 ) {
			return $user_ids[ $comment_id % count( $user_ids ) ];
		}

		return 0; // Guest comment
	}

	/**
	 * Create a comment
	 *
	 * @param int   $comment_id    Virtual comment ID.
	 * @param int   $post_id       WordPress post ID.
	 * @param array $user_ids      Array of available user IDs.
	 * @param int   $parent_id     Parent comment ID (0 for top-level).
	 * @param int   $comment_index Comment index in the post.
	 * @param int   $run_id        Benchmark run ID for tracking.
	 * @return int|WP_Error WordPress comment ID on success, WP_Error on failure.
	 */
	public static function create_comment( $comment_id, $post_id, $user_ids = array(), $parent_id = 0, $comment_index = 0, $run_id = 0 ) {
		// Get post date
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'post_not_found', 'Post not found.' );
		}

		// Assign user
		$user_id = self::assign_user( $comment_id, $user_ids );

		// Prepare comment data
		$comment_data = array(
			'comment_post_ID'      => $post_id,
			'comment_content'      => self::generate_content( $comment_id ),
			'comment_date'         => self::generate_date( $comment_id, $post->post_date ),
			'comment_approved'     => self::generate_status( $comment_id ),
			'comment_parent'       => $parent_id,
			'comment_type'         => 'comment',
		);

		// If registered user
		if ( $user_id > 0 ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$comment_data['user_id'] = $user_id;
				$comment_data['comment_author'] = $user->display_name;
				$comment_data['comment_author_email'] = $user->user_email;
			}
		} else {
			// Guest comment
			$comment_data['comment_author'] = self::generate_author_name( $comment_id );
			$comment_data['comment_author_email'] = self::generate_author_email( $comment_id );
		}

		// Insert comment
		$wp_comment_id = wp_insert_comment( $comment_data );

		if ( ! $wp_comment_id ) {
			return new WP_Error( 'comment_insert_failed', 'Failed to insert comment.' );
		}

		// Add benchmark marker with run_id for per-run tracking
		update_comment_meta( $wp_comment_id, '_nettingale_benchmark_comment', $run_id );
		update_comment_meta( $wp_comment_id, '_nettingale_benchmark_virtual_id', $comment_id );

		// Clear comment caches to ensure counts are updated
		clean_comment_cache( $wp_comment_id );
		wp_update_comment_count( $post_id );

		return $wp_comment_id;
	}

	/**
	 * Create multiple comments for a post
	 *
	 * @param int   $post_id   WordPress post ID.
	 * @param int   $count     Number of comments to create.
	 * @param array $user_ids  Array of available user IDs.
	 * @param int   $start_id  Starting virtual comment ID.
	 * @param int   $run_id    Benchmark run ID for tracking.
	 * @return array Array of created comment IDs.
	 */
	public static function create_comments_for_post( $post_id, $count, $user_ids = array(), $start_id = 1, $run_id = 0 ) {
		$created_comments = array();
		$top_level_comments = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$virtual_id = $start_id + $i;
			$parent_id = 0;

			// Determine if this should be a reply
			if ( self::is_reply( $virtual_id, $i ) && ! empty( $top_level_comments ) ) {
				// Pick a random parent from existing top-level comments
				Nettingale_Benchmark_Config::seed_random( $virtual_id );
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
				$parent_id = $top_level_comments[ mt_rand( 0, count( $top_level_comments ) - 1 ) ];
			}

			// Create comment
			$wp_comment_id = self::create_comment( $virtual_id, $post_id, $user_ids, $parent_id, $i, $run_id );

			if ( ! is_wp_error( $wp_comment_id ) ) {
				$created_comments[] = $wp_comment_id;

				// Track top-level comments for replies
				if ( 0 === $parent_id ) {
					$top_level_comments[] = $wp_comment_id;
				}
			}
		}

		return $created_comments;
	}

	/**
	 * Get all benchmark comment IDs
	 *
	 * @return array Array of WordPress comment IDs.
	 */
	public static function get_benchmark_comments() {
		global $wpdb;

                $comment_ids = $wpdb->get_col(
                        $wpdb->prepare(
                                "SELECT DISTINCT c.comment_ID
                                FROM {$wpdb->comments} c
                                INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
                                WHERE cm.meta_key = %s",
                                '_nettingale_benchmark_comment'
                        )
                );

		return is_array( $comment_ids ) ? array_map( 'intval', $comment_ids ) : array();
	}

	/**
	 * Delete all benchmark comments
	 *
	 * @return int Number of comments deleted.
	 */
	public static function delete_benchmark_comments() {
		$comment_ids = self::get_benchmark_comments();
		$deleted = 0;

		foreach ( $comment_ids as $comment_id ) {
			if ( wp_delete_comment( $comment_id, true ) ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	/**
	 * Get comment statistics
	 *
	 * @return array Statistics about benchmark comments.
	 */
	public static function get_comment_stats() {
		$comment_ids = self::get_benchmark_comments();

		$stats = array(
			'total'    => count( $comment_ids ),
			'approved' => 0,
			'pending'  => 0,
			'replies'  => 0,
			'guests'   => 0,
			'users'    => 0,
		);

		foreach ( $comment_ids as $comment_id ) {
			$comment = get_comment( $comment_id );
			if ( $comment ) {
				// Status
				if ( '1' === $comment->comment_approved ) {
					$stats['approved']++;
				} else {
					$stats['pending']++;
				}

				// Replies
				if ( $comment->comment_parent > 0 ) {
					$stats['replies']++;
				}

				// User vs guest
				if ( $comment->user_id > 0 ) {
					$stats['users']++;
				} else {
					$stats['guests']++;
				}
			}
		}

		return $stats;
	}
}

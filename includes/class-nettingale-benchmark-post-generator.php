<?php
/**
 * Post Generator Class
 *
 * Integrates all generators to create complete posts with content, images, and comments
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_Post_Generator
 */
class Nettingale_Benchmark_Post_Generator {

	/**
	 * Create a complete post with all associated content
	 *
	 * @param int   $post_id       Virtual post ID.
	 * @param array $options       Options array.
	 *                             - run_id: Benchmark run ID (required for tracking)
	 *                             - user_ids: Array of user IDs for author assignment
	 *                             - category_ids: Array of category IDs
	 *                             - tag_ids: Array of tag IDs
	 *                             - with_images: Generate and attach images (default: true)
	 *                             - with_comments: Generate comments (default: false, done separately)
	 *                             - comment_count: Number of comments to generate
	 * @return int|WP_Error WordPress post ID on success, WP_Error on failure.
	 */
	public static function create_post( $post_id, $options = array() ) {
		// Default options
		$defaults = array(
			'run_id'         => 0,
			'user_ids'       => array(),
			'category_ids'   => array(),
			'tag_ids'        => array(),
			'with_images'    => true,
			'with_comments'  => false,
			'comment_count'  => 0,
		);

		$options = wp_parse_args( $options, $defaults );

		// Generate post data
		$post_data = array(
			'post_title'   => Nettingale_Benchmark_Content_Generator::generate_title( $post_id ),
			'post_content' => Nettingale_Benchmark_Content_Generator::generate_content( $post_id ),
			'post_excerpt' => Nettingale_Benchmark_Content_Generator::generate_excerpt( $post_id ),
			'post_name'    => Nettingale_Benchmark_Content_Generator::generate_slug( $post_id ),
			'post_date'    => Nettingale_Benchmark_Content_Generator::generate_date( $post_id ),
			'post_status'  => 'publish',
			'post_type'    => 'post',
		);

		// Assign author
		if ( ! empty( $options['user_ids'] ) ) {
			$post_data['post_author'] = Nettingale_Benchmark_Content_Generator::assign_author( $post_id, $options['user_ids'] );
		}

		// Insert post
		$wp_post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $wp_post_id ) ) {
			return $wp_post_id;
		}

		// Add benchmark marker with run_id for per-run tracking
		update_post_meta( $wp_post_id, '_nettingale_benchmark_item', $options['run_id'] );
		update_post_meta( $wp_post_id, '_nettingale_benchmark_virtual_id', $post_id );

		// Assign categories
		if ( ! empty( $options['category_ids'] ) ) {
			$assigned_cats = Nettingale_Benchmark_Content_Generator::assign_categories( $post_id, $options['category_ids'] );
			wp_set_post_categories( $wp_post_id, $assigned_cats );
		}

		// Assign tags
		if ( ! empty( $options['tag_ids'] ) ) {
			$assigned_tags = Nettingale_Benchmark_Content_Generator::assign_tags( $post_id, $options['tag_ids'] );
			wp_set_post_tags( $wp_post_id, $assigned_tags );
		}

		// Generate and attach images
		if ( $options['with_images'] ) {
			$image_result = Nettingale_Benchmark_Image_Generator::generate_and_attach_images( $wp_post_id, true, $options['run_id'] );
			if ( is_wp_error( $image_result ) ) {
				// Log error but continue
				update_post_meta( $wp_post_id, '_nettingale_benchmark_image_error', $image_result->get_error_message() );
			}
		}

		// Generate comments (if requested)
		if ( $options['with_comments'] && $options['comment_count'] > 0 ) {
			$comment_result = Nettingale_Benchmark_Comment_Generator::create_comments_for_post(
				$wp_post_id,
				$options['comment_count'],
				$options['user_ids'],
				( $post_id * 1000 ), // Use different virtual ID range for comments
				$options['run_id']
			);
		}

		return $wp_post_id;
	}

	/**
	 * Create multiple posts
	 *
	 * @param int   $count    Number of posts to create.
	 * @param int   $start_id Starting virtual post ID.
	 * @param array $options  Options array (same as create_post).
	 * @return array Array of created post IDs.
	 */
	public static function create_posts( $count, $start_id, $options = array() ) {
		$created_posts = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$virtual_id = $start_id + $i;
			$wp_post_id = self::create_post( $virtual_id, $options );

			if ( ! is_wp_error( $wp_post_id ) ) {
				$created_posts[] = $wp_post_id;
			}
		}

		return $created_posts;
	}

	/**
	 * Create a complete page
	 *
	 * @param int   $page_id  Virtual page ID.
	 * @param array $options  Options array.
	 *                        - run_id: Benchmark run ID (required for tracking)
	 *                        - user_ids: Array of user IDs for author assignment
	 *                        - with_images: Generate and attach images (default: true)
	 * @return int|WP_Error WordPress page ID on success, WP_Error on failure.
	 */
	public static function create_page( $page_id, $options = array() ) {
		// Default options
		$defaults = array(
			'run_id'      => 0,
			'user_ids'    => array(),
			'with_images' => true,
		);

		$options = wp_parse_args( $options, $defaults );

		// Generate page data
		$page_data = array(
			'post_title'   => Nettingale_Benchmark_Content_Generator::generate_title( $page_id, 'Page:' ),
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
			'post_content' => Nettingale_Benchmark_Content_Generator::generate_content( $page_id, mt_rand( 5, 12 ) ), // More content for pages
			'post_name'    => Nettingale_Benchmark_Content_Generator::generate_slug( $page_id, 'page' ),
			'post_date'    => Nettingale_Benchmark_Content_Generator::generate_date( $page_id ),
			'post_status'  => 'publish',
			'post_type'    => 'page',
		);

		// Assign author
		if ( ! empty( $options['user_ids'] ) ) {
			$page_data['post_author'] = Nettingale_Benchmark_Content_Generator::assign_author( $page_id, $options['user_ids'] );
		}

		// Insert page
		$wp_page_id = wp_insert_post( $page_data, true );

		if ( is_wp_error( $wp_page_id ) ) {
			return $wp_page_id;
		}

		// Add benchmark marker with run_id for per-run tracking
		update_post_meta( $wp_page_id, '_nettingale_benchmark_item', $options['run_id'] );
		update_post_meta( $wp_page_id, '_nettingale_benchmark_virtual_id', $page_id );

		// Generate and attach images
		if ( $options['with_images'] ) {
			$image_result = Nettingale_Benchmark_Image_Generator::generate_and_attach_images( $wp_page_id, true, $options['run_id'] );
			if ( is_wp_error( $image_result ) ) {
				// Log error but continue
				update_post_meta( $wp_page_id, '_nettingale_benchmark_image_error', $image_result->get_error_message() );
			}
		}

		return $wp_page_id;
	}

	/**
	 * Create multiple pages
	 *
	 * @param int   $count    Number of pages to create.
	 * @param int   $start_id Starting virtual page ID.
	 * @param array $options  Options array (same as create_page).
	 * @return array Array of created page IDs.
	 */
	public static function create_pages( $count, $start_id, $options = array() ) {
		$created_pages = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$virtual_id = $start_id + $i;
			$wp_page_id = self::create_page( $virtual_id, $options );

			if ( ! is_wp_error( $wp_page_id ) ) {
				$created_pages[] = $wp_page_id;
			}
		}

		return $created_pages;
	}

	/**
	 * Add comments to existing posts
	 *
	 * @param array $post_ids  Array of WordPress post IDs.
	 * @param array $user_ids  Array of user IDs.
	 * @param int   $max_comments_per_post Maximum comments per post.
	 * @param int   $run_id    Benchmark run ID for tracking.
	 * @return int Total comments created.
	 */
	public static function add_comments_to_posts( $post_ids, $user_ids, $max_comments_per_post = 50, $run_id = 0 ) {
		$total_comments = 0;
		$comment_virtual_id = 1;
		$total_posts = count( $post_ids );

		// For tier compliance, distribute comments evenly with slight variation
		// to ensure we hit the target comment count
		foreach ( $post_ids as $index => $wp_post_id ) {
			$post = get_post( $wp_post_id );
			if ( ! $post ) {
				continue;
			}

			// Get virtual post ID for seeding
			$virtual_post_id = get_post_meta( $wp_post_id, '_nettingale_benchmark_virtual_id', true );
			if ( ! $virtual_post_id ) {
				$virtual_post_id = $wp_post_id;
			}

			// Use fixed comment count for tier compliance
			// Add slight deterministic variation (±20%) based on post ID
			Nettingale_Benchmark_Config::seed_random( $virtual_post_id );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
			$variation = mt_rand( 80, 120 ) / 100;
			$comment_count = max( 0, (int) round( $max_comments_per_post * $variation ) );

			// Create comments
			if ( $comment_count > 0 ) {
				$created = Nettingale_Benchmark_Comment_Generator::create_comments_for_post(
					$wp_post_id,
					$comment_count,
					$user_ids,
					$comment_virtual_id,
					$run_id
				);

				$total_comments += count( $created );
				$comment_virtual_id += $comment_count;
			}
		}

		return $total_comments;
	}

	/**
	 * Get all benchmark posts
	 *
	 * @param string $post_type Post type (post or page).
	 * @return array Array of WordPress post IDs.
	 */
	public static function get_benchmark_posts( $post_type = 'post' ) {
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'meta_key'       => '_nettingale_benchmark_item',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * Delete all benchmark posts/pages
	 *
	 * @param string $post_type Post type (post or page).
	 * @return int Number of posts deleted.
	 */
	public static function delete_benchmark_posts( $post_type = 'post' ) {
		$post_ids = self::get_benchmark_posts( $post_type );
		$deleted = 0;

		foreach ( $post_ids as $post_id ) {
			// This will also delete associated comments and attachments
			if ( wp_delete_post( $post_id, true ) ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	/**
	 * Get post statistics
	 *
	 * @return array Statistics about benchmark posts.
	 */
	public static function get_post_stats() {
		$posts = self::get_benchmark_posts( 'post' );
		$pages = self::get_benchmark_posts( 'page' );

		$stats = array(
			'posts'      => count( $posts ),
			'pages'      => count( $pages ),
			'total'      => count( $posts ) + count( $pages ),
			'with_images' => 0,
			'with_featured' => 0,
		);

		// Check images
		foreach ( array_merge( $posts, $pages ) as $post_id ) {
			if ( has_post_thumbnail( $post_id ) ) {
				$stats['with_featured']++;
			}

			$attachments = get_attached_media( 'image', $post_id );
			if ( ! empty( $attachments ) ) {
				$stats['with_images']++;
			}
		}

		return $stats;
	}
}

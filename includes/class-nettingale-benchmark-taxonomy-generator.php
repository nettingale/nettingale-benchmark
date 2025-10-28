<?php
/**
 * Taxonomy Generator Class
 *
 * Generates deterministic categories and tags for benchmarking
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_Taxonomy_Generator
 */
class Nettingale_Benchmark_Taxonomy_Generator {

	/**
	 * Generate category name based on term ID
	 *
	 * @param int $term_id Virtual term ID.
	 * @return string Category name.
	 */
	public static function generate_category_name( $term_id ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $term_id );

		// Get topics for categories
		$topics = Nettingale_Benchmark_Config::get_topics();

		// Use topic as base
		$topic = $topics[ $term_id % count( $topics ) ];

		// Add some variety with words
		$words = Nettingale_Benchmark_Config::get_lorem_words();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$word = ucfirst( $words[ mt_rand( 0, count( $words ) - 1 ) ] );

		return $topic . ' ' . $word;
	}

	/**
	 * Generate category slug based on term ID
	 *
	 * @param int $term_id Virtual term ID.
	 * @param int $run_id  Run ID for uniqueness across runs.
	 * @return string Category slug.
	 */
	public static function generate_category_slug( $term_id, $run_id = 0 ) {
		$name = self::generate_category_name( $term_id );
		$slug = $name . '-' . $term_id;

		// Append run_id to ensure uniqueness across runs
		if ( $run_id > 0 ) {
			$slug .= '-r' . $run_id;
		}

		return sanitize_title( $slug );
	}

	/**
	 * Generate category description based on term ID
	 *
	 * @param int $term_id Virtual term ID.
	 * @return string Category description.
	 */
	public static function generate_category_description( $term_id ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $term_id );

		// Get words bank
		$words = Nettingale_Benchmark_Config::get_lorem_words();

		// Generate 1-2 sentences
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$sentence_count = mt_rand( 1, 2 );
		$sentences = array();

		for ( $s = 0; $s < $sentence_count; $s++ ) {
			// Each sentence has 8-12 words
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
			$word_count = mt_rand( 8, 12 );
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
	 * Generate tag name based on term ID
	 *
	 * @param int $term_id Virtual term ID.
	 * @return string Tag name.
	 */
	public static function generate_tag_name( $term_id ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $term_id );

		// Get words bank
		$words = Nettingale_Benchmark_Config::get_lorem_words();

		// Tags are 1-2 words
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$word_count = mt_rand( 1, 2 );
		$tag_words = array();

		for ( $i = 0; $i < $word_count; $i++ ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
			$tag_words[] = ucfirst( $words[ mt_rand( 0, count( $words ) - 1 ) ] );
		}

		return implode( ' ', $tag_words );
	}

	/**
	 * Generate tag slug based on term ID
	 *
	 * @param int $term_id Virtual term ID.
	 * @param int $run_id  Run ID for uniqueness across runs.
	 * @return string Tag slug.
	 */
	public static function generate_tag_slug( $term_id, $run_id = 0 ) {
		$name = self::generate_tag_name( $term_id );
		$slug = $name . '-' . $term_id;

		// Append run_id to ensure uniqueness across runs
		if ( $run_id > 0 ) {
			$slug .= '-r' . $run_id;
		}

		return sanitize_title( $slug );
	}

	/**
	 * Create a category
	 *
	 * @param int $term_id Virtual term ID.
	 * @param int $run_id  Benchmark run ID for tracking.
	 * @return int|WP_Error WordPress term ID on success, WP_Error on failure.
	 */
	public static function create_category( $term_id, $run_id = 0 ) {
		$name = self::generate_category_name( $term_id );
		$slug = self::generate_category_slug( $term_id, $run_id );
		$description = self::generate_category_description( $term_id );

		// Check if term already exists
		$existing = term_exists( $slug, 'category' );
		if ( $existing ) {
			return new WP_Error( 'term_exists', sprintf( 'Category with slug %s already exists.', $slug ) );
		}

		// Create term
		$result = wp_insert_term(
			$name,
			'category',
			array(
				'slug'        => $slug,
				'description' => $description,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$wp_term_id = $result['term_id'];

		// Add benchmark marker
		update_term_meta( $wp_term_id, '_nettingale_benchmark_term', $run_id );
		update_term_meta( $wp_term_id, '_nettingale_benchmark_virtual_id', $term_id );
		update_term_meta( $wp_term_id, '_nettingale_benchmark_taxonomy', 'category' );

		return $wp_term_id;
	}

	/**
	 * Create a tag
	 *
	 * @param int $term_id Virtual term ID.
	 * @param int $run_id  Benchmark run ID for tracking.
	 * @return int|WP_Error WordPress term ID on success, WP_Error on failure.
	 */
	public static function create_tag( $term_id, $run_id = 0 ) {
		$name = self::generate_tag_name( $term_id );
		$slug = self::generate_tag_slug( $term_id, $run_id );

		// Check if term already exists
		$existing = term_exists( $slug, 'post_tag' );
		if ( $existing ) {
			return new WP_Error( 'term_exists', sprintf( 'Tag with slug %s already exists.', $slug ) );
		}

		// Create term
		$result = wp_insert_term(
			$name,
			'post_tag',
			array(
				'slug' => $slug,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$wp_term_id = $result['term_id'];

		// Add benchmark marker
		update_term_meta( $wp_term_id, '_nettingale_benchmark_term', $run_id );
		update_term_meta( $wp_term_id, '_nettingale_benchmark_virtual_id', $term_id );
		update_term_meta( $wp_term_id, '_nettingale_benchmark_taxonomy', 'post_tag' );

		return $wp_term_id;
	}

	/**
	 * Create multiple categories
	 *
	 * @param int $count    Number of categories to create.
	 * @param int $start_id Starting virtual term ID.
	 * @param int $run_id   Benchmark run ID for tracking.
	 * @return array Array of created term IDs.
	 */
	public static function create_categories( $count, $start_id = 1, $run_id = 0 ) {
		$created_terms = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$virtual_id = $start_id + $i;
			$wp_term_id = self::create_category( $virtual_id, $run_id );

			if ( ! is_wp_error( $wp_term_id ) ) {
				$created_terms[] = $wp_term_id;
			}
		}

		return $created_terms;
	}

	/**
	 * Create multiple tags
	 *
	 * @param int $count    Number of tags to create.
	 * @param int $start_id Starting virtual term ID.
	 * @param int $run_id   Benchmark run ID for tracking.
	 * @return array Array of created term IDs.
	 */
	public static function create_tags( $count, $start_id = 1, $run_id = 0 ) {
		$created_terms = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$virtual_id = $start_id + $i;
			$wp_term_id = self::create_tag( $virtual_id, $run_id );

			if ( ! is_wp_error( $wp_term_id ) ) {
				$created_terms[] = $wp_term_id;
			}
		}

		return $created_terms;
	}

	/**
	 * Get all benchmark category IDs
	 *
	 * @return array Array of WordPress term IDs.
	 */
	public static function get_benchmark_categories() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'meta_query' => array(
					array(
						'key'     => '_nettingale_benchmark_term',
						'compare' => 'EXISTS',
					),
				),
				'fields'     => 'ids',
			)
		);

		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * Get all benchmark tag IDs
	 *
	 * @return array Array of WordPress term IDs.
	 */
	public static function get_benchmark_tags() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'post_tag',
				'hide_empty' => false,
				'meta_query' => array(
					array(
						'key'     => '_nettingale_benchmark_term',
						'compare' => 'EXISTS',
					),
				),
				'fields'     => 'ids',
			)
		);

		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * Get all benchmark terms (categories and tags)
	 *
	 * @return array Array with 'categories' and 'tags' keys.
	 */
	public static function get_all_benchmark_terms() {
		return array(
			'categories' => self::get_benchmark_categories(),
			'tags'       => self::get_benchmark_tags(),
		);
	}

	/**
	 * Delete all benchmark categories
	 *
	 * @return int Number of categories deleted.
	 */
	public static function delete_benchmark_categories() {
		$term_ids = self::get_benchmark_categories();
		$deleted = 0;

		foreach ( $term_ids as $term_id ) {
			if ( wp_delete_term( $term_id, 'category' ) ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	/**
	 * Delete all benchmark tags
	 *
	 * @return int Number of tags deleted.
	 */
	public static function delete_benchmark_tags() {
		$term_ids = self::get_benchmark_tags();
		$deleted = 0;

		foreach ( $term_ids as $term_id ) {
			if ( wp_delete_term( $term_id, 'post_tag' ) ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	/**
	 * Delete all benchmark taxonomies
	 *
	 * @return array Number of categories and tags deleted.
	 */
	public static function delete_all_benchmark_terms() {
		return array(
			'categories' => self::delete_benchmark_categories(),
			'tags'       => self::delete_benchmark_tags(),
		);
	}

	/**
	 * Get taxonomy statistics
	 *
	 * @return array Statistics about benchmark taxonomies.
	 */
	public static function get_taxonomy_stats() {
		$categories = self::get_benchmark_categories();
		$tags = self::get_benchmark_tags();

		return array(
			'categories' => count( $categories ),
			'tags'       => count( $tags ),
			'total'      => count( $categories ) + count( $tags ),
		);
	}
}

<?php
/**
 * Benchmark Configuration Class
 *
 * Defines tier configurations, color schemes, and utility methods
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_Config
 */
class Nettingale_Benchmark_Config {

	/**
	 * Fixed seed for reproducible randomization
	 */
	const SEED = 12345;

	/**
	 * Batch size for processing operations
	 *
	 * @var int
	 */
	const BATCH_SIZE = 100;

	/**
	 * Tier configurations
	 *
	 * @var array
	 */
	private static $tiers = array(
		'small'  => array(
			'posts'              => 500,
			'pages'              => 50,
			'comments'           => 1000,
			'users'              => 100,
			'categories'         => 10,
			'tags'               => 50,
			'images_per_post'    => 3,
			'estimated_time'     => '2-5 minutes',
			'estimated_size_mb'  => 50,
		),
		'medium' => array(
			'posts'              => 5000,
			'pages'              => 200,
			'comments'           => 10000,
			'users'              => 1000,
			'categories'         => 25,
			'tags'               => 200,
			'images_per_post'    => 3,
			'estimated_time'     => '20-40 minutes',
			'estimated_size_mb'  => 500,
		),
		'large'  => array(
			'posts'              => 50000,
			'pages'              => 1000,
			'comments'           => 100000,
			'users'              => 10000,
			'categories'         => 50,
			'tags'               => 1000,
			'images_per_post'    => 3,
			'estimated_time'     => '3-6 hours',
			'estimated_size_mb'  => 5000,
		),
	);

	/**
	 * Color schemes for image generation
	 *
	 * @var array
	 */
	private static $color_schemes = array(
		array( 'bg' => '#2C3E50', 'text' => '#ECF0F1' ), // Navy/Cream
		array( 'bg' => '#E74C3C', 'text' => '#FFFFFF' ), // Red/White
		array( 'bg' => '#27AE60', 'text' => '#FFFFFF' ), // Green/White
		array( 'bg' => '#8E44AD', 'text' => '#F4ECF7' ), // Purple/Light Purple
		array( 'bg' => '#F39C12', 'text' => '#FFFFFF' ), // Orange/White
		array( 'bg' => '#16A085', 'text' => '#FFFFFF' ), // Teal/White
		array( 'bg' => '#C0392B', 'text' => '#FFFFFF' ), // Dark Red/White
		array( 'bg' => '#2980B9', 'text' => '#FFFFFF' ), // Blue/White
		array( 'bg' => '#D35400', 'text' => '#FFFFFF' ), // Dark Orange/White
		array( 'bg' => '#7F8C8D', 'text' => '#FFFFFF' ), // Gray/White
	);

	/**
	 * Get tier configuration
	 *
	 * @param string $tier Tier name (small, medium, large).
	 * @return array|null Tier configuration or null if not found.
	 */
	public static function get_tier( $tier ) {
		return isset( self::$tiers[ $tier ] ) ? self::$tiers[ $tier ] : null;
	}

	/**
	 * Get all tiers
	 *
	 * @return array All tier configurations.
	 */
	public static function get_all_tiers() {
		return self::$tiers;
	}

	/**
	 * Check if tier exists
	 *
	 * @param string $tier Tier name.
	 * @return bool True if tier exists.
	 */
	public static function tier_exists( $tier ) {
		return isset( self::$tiers[ $tier ] );
	}

	/**
	 * Get color scheme by index
	 *
	 * @param int $index Index (0-9).
	 * @return array Color scheme with 'bg' and 'text' keys.
	 */
	public static function get_color_scheme( $index ) {
		$index = absint( $index ) % count( self::$color_schemes );
		return self::$color_schemes[ $index ];
	}

	/**
	 * Seed random number generator for reproducibility
	 *
	 * @param int $id ID to add to seed for variation.
	 */
	public static function seed_random( $id ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand -- Required for deterministic seeding with fixed seed to ensure reproducible benchmark results
		mt_srand( self::SEED + absint( $id ) );
	}

	/**
	 * Get seeded random number
	 *
	 * @param int $id ID for seeding.
	 * @param int $min Minimum value.
	 * @param int $max Maximum value.
	 * @return int Random number.
	 */
	public static function get_seeded_random( $id, $min, $max ) {
		self::seed_random( $id );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		return mt_rand( $min, $max );
	}

	/**
	 * Convert hex color to RGB array
	 *
	 * @param string $hex Hex color code (e.g., '#FFFFFF').
	 * @return array RGB values array(r, g, b).
	 */
	public static function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );

		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * Get Lorem Ipsum word bank
	 *
	 * Fixed word bank for reproducible content generation
	 *
	 * @return array Array of Lorem Ipsum words.
	 */
	public static function get_lorem_words() {
		return array(
			'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
			'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
			'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
			'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
			'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate', 'velit',
			'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint', 'occaecat',
			'cupidatat', 'non', 'proident', 'sunt', 'culpa', 'qui', 'officia', 'deserunt',
			'mollit', 'anim', 'id', 'est', 'laborum', 'perspiciatis', 'unde', 'omnis', 'iste',
			'natus', 'error', 'voluptatem', 'accusantium', 'doloremque', 'laudantium', 'totam',
			'rem', 'aperiam', 'eaque', 'ipsa', 'quae', 'ab', 'illo', 'inventore', 'veritatis',
			'quasi', 'architecto', 'beatae', 'vitae', 'dicta', 'explicabo', 'nemo', 'ipsam',
			'quia', 'voluptas', 'aspernatur', 'odit', 'aut', 'fugit', 'consequuntur', 'magni',
			'dolores', 'eos', 'ratione', 'sequi', 'nesciunt', 'neque', 'porro', 'quisquam',
			'dolorem', 'adipisci', 'numquam', 'eius', 'modi', 'tempora', 'incidunt', 'magnam',
			'quam', 'nihil', 'impedit', 'quo', 'minus', 'quod', 'maxime', 'placeat', 'facere',
			'possimus', 'omnis', 'voluptas', 'assumenda', 'est', 'omnis', 'dolor', 'repellendus',
			'temporibus', 'autem', 'quibusdam', 'officiis', 'debitis', 'rerum', 'necessitatibus',
			'saepe', 'eveniet', 'voluptates', 'repudiandae', 'recusandae', 'itaque', 'earum',
			'hic', 'tenetur', 'sapiente', 'delectus', 'reiciendis', 'maiores', 'alias',
			'perferendis', 'doloribus', 'asperiores', 'repellat', 'accusamus', 'iusto', 'odio',
			'dignissimos', 'ducimus', 'blanditiis', 'praesentium', 'voluptatum', 'deleniti',
			'atque', 'corrupti', 'quos', 'quas', 'molestias', 'excepturi', 'obcaecati',
			'similique', 'libero', 'soluta', 'nobis', 'eligendi', 'optio', 'cumque', 'impedit',
			'distinctio', 'aperiam', 'accusantium', 'doloremque', 'laudantium', 'at', 'vero',
			'accusamus', 'iusto', 'dignissimos', 'qui', 'blanditiis', 'praesentium', 'deleniti',
			'atque', 'corrupti', 'quos', 'dolores', 'quas', 'molestias', 'excepturi', 'sint',
			'occaecati', 'cupiditate', 'provident', 'similique', 'mollitia', 'animi', 'facilis',
			'expedita', 'distinctio', 'nam', 'libero', 'tempore', 'soluta', 'nobis', 'eligendi',
			'optio', 'cumque', 'nihil', 'impedit', 'quo', 'porro', 'quisquam', 'est', 'qui',
			'dolorem', 'ipsum', 'quia', 'dolor', 'sit', 'amet', 'consectetur', 'adipisci',
			'velit', 'sed', 'quia', 'non', 'numquam', 'eius', 'modi', 'tempora', 'incidunt',
		);
	}

	/**
	 * Get benchmark topics for title generation
	 *
	 * @return array Array of topics.
	 */
	public static function get_topics() {
		return array(
			'Technology', 'Business', 'Science', 'Health', 'Education',
			'Travel', 'Food', 'Sports', 'Entertainment', 'Finance',
			'Marketing', 'Design', 'Development', 'Innovation', 'Research',
			'Leadership', 'Strategy', 'Analytics', 'Security', 'Quality',
		);
	}

	/**
	 * Get benchmark names for user generation
	 *
	 * @return array Array of names.
	 */
	public static function get_names() {
		return array(
			'John Smith', 'Jane Doe', 'Michael Johnson', 'Emily Brown', 'David Wilson',
			'Sarah Davis', 'Robert Miller', 'Jennifer Garcia', 'William Martinez', 'Linda Anderson',
			'James Taylor', 'Mary Thomas', 'Christopher Moore', 'Patricia Jackson', 'Daniel White',
			'Barbara Harris', 'Matthew Martin', 'Elizabeth Thompson', 'Anthony Clark', 'Nancy Lewis',
		);
	}
}

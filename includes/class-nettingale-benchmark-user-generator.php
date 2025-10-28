<?php
/**
 * User Generator Class
 *
 * Generates deterministic users for benchmarking
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_User_Generator
 */
class Nettingale_Benchmark_User_Generator {

	/**
	 * Generate username based on user ID
	 *
	 * @param int $user_id Virtual user ID.
	 * @param int $run_id  Run ID for uniqueness across runs.
	 * @return string Username.
	 */
	public static function generate_username( $user_id, $run_id = 0 ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $user_id );

		// Get names
		$names = Nettingale_Benchmark_Config::get_names();
		$name = $names[ $user_id % count( $names ) ];

		// Convert to username format
		$username = strtolower( str_replace( ' ', '_', $name ) );
		$username .= '_' . $user_id;

		// Append run_id to ensure uniqueness across runs
		if ( $run_id > 0 ) {
			$username .= '_r' . $run_id;
		}

		return sanitize_user( $username, true );
	}

	/**
	 * Generate email based on user ID
	 *
	 * @param int $user_id Virtual user ID.
	 * @param int $run_id  Run ID for uniqueness across runs.
	 * @return string Email address.
	 */
	public static function generate_email( $user_id, $run_id = 0 ) {
		$username = self::generate_username( $user_id, $run_id );
		return $username . '@benchmark.test';
	}

	/**
	 * Generate display name based on user ID
	 *
	 * @param int $user_id Virtual user ID.
	 * @return string Display name.
	 */
	public static function generate_display_name( $user_id ) {
		$names = Nettingale_Benchmark_Config::get_names();
		return $names[ $user_id % count( $names ) ];
	}

	/**
	 * Generate first name based on user ID
	 *
	 * @param int $user_id Virtual user ID.
	 * @return string First name.
	 */
	public static function generate_first_name( $user_id ) {
		$display_name = self::generate_display_name( $user_id );
		$parts = explode( ' ', $display_name );
		return $parts[0];
	}

	/**
	 * Generate last name based on user ID
	 *
	 * @param int $user_id Virtual user ID.
	 * @return string Last name.
	 */
	public static function generate_last_name( $user_id ) {
		$display_name = self::generate_display_name( $user_id );
		$parts = explode( ' ', $display_name );
		return isset( $parts[1] ) ? $parts[1] : '';
	}

	/**
	 * Generate bio/description based on user ID
	 *
	 * @param int $user_id Virtual user ID.
	 * @return string User bio.
	 */
	public static function generate_bio( $user_id ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $user_id );

		// Get words bank
		$words = Nettingale_Benchmark_Config::get_lorem_words();

		// Generate 2-3 sentences
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$sentence_count = mt_rand( 2, 3 );
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
	 * Generate user role based on user ID
	 *
	 * Most users are subscribers, some are contributors/authors
	 *
	 * @param int $user_id Virtual user ID.
	 * @return string User role.
	 */
	public static function generate_role( $user_id ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $user_id );

		// Role distribution (percentages)
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$rand = mt_rand( 1, 100 );

		if ( $rand <= 85 ) {
			return 'subscriber'; // 85% subscribers
		} elseif ( $rand <= 95 ) {
			return 'contributor'; // 10% contributors
		} else {
			return 'author'; // 5% authors
		}
	}

	/**
	 * Generate user registration date based on user ID
	 *
	 * Users spread across last 2 years
	 *
	 * @param int $user_id Virtual user ID.
	 * @return string MySQL datetime format.
	 */
	public static function generate_registered_date( $user_id ) {
		// Seed the random generator
		Nettingale_Benchmark_Config::seed_random( $user_id );

		// Generate date within last 730 days (2 years)
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$days_ago = mt_rand( 0, 730 );
		$timestamp = time() - ( $days_ago * DAY_IN_SECONDS );

		// Add some random hours/minutes
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand -- Required for deterministic seeding
		$timestamp -= mt_rand( 0, 86400 );

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Create a benchmark user
	 *
	 * @param int $user_id Virtual user ID.
	 * @param int $run_id  Benchmark run ID for tracking.
	 * @return int|WP_Error WordPress user ID on success, WP_Error on failure.
	 */
	public static function create_user( $user_id, $run_id = 0 ) {
		// Generate user data
		$username = self::generate_username( $user_id, $run_id );
		$email = self::generate_email( $user_id, $run_id );
		$display_name = self::generate_display_name( $user_id );
		$role = self::generate_role( $user_id );

		// Check if user already exists
		if ( username_exists( $username ) ) {
			return new WP_Error( 'user_exists', sprintf( 'Username %s already exists.', $username ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'email_exists', sprintf( 'Email %s already exists.', $email ) );
		}

		// Create user
		$wp_user_id = wp_insert_user(
			array(
				'user_login'      => $username,
				'user_email'      => $email,
				'display_name'    => $display_name,
				'first_name'      => self::generate_first_name( $user_id ),
				'last_name'       => self::generate_last_name( $user_id ),
				'description'     => self::generate_bio( $user_id ),
				'user_registered' => self::generate_registered_date( $user_id ),
				'role'            => $role,
				'user_pass'       => wp_generate_password( 20, false ),
			)
		);

		if ( is_wp_error( $wp_user_id ) ) {
			return $wp_user_id;
		}

		// Add benchmark marker with run_id for per-run tracking
		update_user_meta( $wp_user_id, '_nettingale_benchmark_user', $run_id );
		update_user_meta( $wp_user_id, '_nettingale_benchmark_virtual_id', $user_id );

		return $wp_user_id;
	}

	/**
	 * Create multiple users
	 *
	 * @param int $count Number of users to create.
	 * @param int $start_id Starting virtual user ID.
	 * @param int $run_id Benchmark run ID for tracking.
	 * @return array Array of created user IDs.
	 */
	public static function create_users( $count, $start_id = 1, $run_id = 0 ) {
		$created_users = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$virtual_id = $start_id + $i;
			$wp_user_id = self::create_user( $virtual_id, $run_id );

			if ( ! is_wp_error( $wp_user_id ) ) {
				$created_users[] = $wp_user_id;
			}
		}

		return $created_users;
	}

	/**
	 * Get all benchmark user IDs
	 *
	 * @return array Array of WordPress user IDs.
	 */
	public static function get_benchmark_users() {
		$users = get_users(
			array(
				'meta_key'   => '_nettingale_benchmark_user',
				'fields'     => 'ID',
			)
		);

		return $users;
	}

	/**
	 * Delete all benchmark users
	 *
	 * @return int Number of users deleted.
	 */
	public static function delete_benchmark_users() {
		// Include required file for wp_delete_user
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$user_ids = self::get_benchmark_users();
		$deleted = 0;

		foreach ( $user_ids as $user_id ) {
			// Reassign posts to admin user if needed
			if ( wp_delete_user( $user_id, 1 ) ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	/**
	 * Get user statistics
	 *
	 * @return array Statistics about benchmark users.
	 */
	public static function get_user_stats() {
		$user_ids = self::get_benchmark_users();

		$stats = array(
			'total'       => count( $user_ids ),
			'subscribers' => 0,
			'contributors' => 0,
			'authors'     => 0,
		);

		foreach ( $user_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$roles = $user->roles;
				if ( in_array( 'subscriber', $roles, true ) ) {
					$stats['subscribers']++;
				} elseif ( in_array( 'contributor', $roles, true ) ) {
					$stats['contributors']++;
				} elseif ( in_array( 'author', $roles, true ) ) {
					$stats['authors']++;
				}
			}
		}

		return $stats;
	}
}

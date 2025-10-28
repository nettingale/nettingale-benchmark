<?php
/**
 * Metrics Collector Class
 *
 * Collects and reports benchmark metrics
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_Metrics_Collector
 */
class Nettingale_Benchmark_Metrics_Collector {

	/**
	 * Recalculate metrics for existing runs (e.g., after updating size calculation)
	 *
	 * @param int|null $run_id Optional run ID. If null, recalculates all completed runs.
	 * @return int Number of runs recalculated.
	 */
	public static function recalculate_metrics( $run_id = null ) {
		global $wpdb;

		// Security: Validate table name to prevent SQL injection
		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';

		if ( $run_id ) {
			// Recalculate single run - using prepared statement
			$runs = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}nettingale_benchmark_runs WHERE id = %d AND status = 'completed'",
					$run_id
				),
				ARRAY_A
			);
		} else {
			// Recalculate all completed runs - using prepared statement
			$runs = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}nettingale_benchmark_runs WHERE status = %s",
					'completed'
				),
				ARRAY_A
			);
		}

		if ( empty( $runs ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $runs as $run ) {
			$success = self::store_metrics( $run['id'] );
			if ( $success ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Store comprehensive metrics for a benchmark run
	 *
	 * @param int $run_id Run ID.
	 * @return bool Success.
	 */
	public static function store_metrics( $run_id ) {
		global $wpdb;

		// Get run data - using direct prefix reference for security
		$run = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}nettingale_benchmark_runs WHERE id = %d", $run_id ), ARRAY_A );

		if ( ! $run ) {
			return false;
		}

		$start_time = strtotime( $run['started_at'] );
		$end_time = current_time( 'timestamp' );
		$duration = $end_time - $start_time;

		// Collect counts
		$counts = self::count_generated_content( $run_id );

		// Measure filesystem size (benchmark uploads directory)
		$fs_size = self::get_filesystem_size( $run_id );

		// Database size not calculated (would require before/after snapshots)
		$db_size = 0;

		// Calculate rates
		$rates = self::calculate_rates( $counts, $duration, $fs_size );

		// Compile metrics
		$metrics = array(
			'counts'    => $counts,
			'sizes'     => array(
				'filesystem_mb' => round( $fs_size / 1024 / 1024, 2 ),
				'database_mb'   => round( $db_size / 1024 / 1024, 2 ),
				'total_mb'      => round( ( $fs_size + $db_size ) / 1024 / 1024, 2 ),
			),
			'timing'    => array(
				'start_time' => $start_time,
				'end_time'   => $end_time,
				'duration'   => $duration,
				'formatted'  => self::format_duration( $duration ),
			),
			'rates'     => $rates,
			'completed' => gmdate( 'Y-m-d H:i:s' ),
		);

		// Update run record - using direct prefix reference for security
		$wpdb->update(
			$wpdb->prefix . 'nettingale_benchmark_runs',
			array(
				'metrics'       => wp_json_encode( $metrics ),
				'completed_at'  => current_time( 'mysql' ),
				'status'        => 'completed',
				'duration_seconds' => $duration,
			),
			array( 'id' => $run_id ),
			array( '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);

		return true;
	}

	/**
	 * Get summary of metrics for a run
	 *
	 * @param int $run_id Run ID.
	 * @return array Summary metrics.
	 */
	public static function get_summary( $run_id ) {
		global $wpdb;

		// Security: Use direct prefix reference for table name
		$metrics_json = $wpdb->get_var( $wpdb->prepare( "SELECT metrics FROM {$wpdb->prefix}nettingale_benchmark_runs WHERE id = %d", $run_id ) );

		if ( ! $metrics_json ) {
			return array();
		}

		$metrics = json_decode( $metrics_json, true );

		// Return simplified summary
		return array(
			'posts'      => isset( $metrics['counts']['posts'] ) ? $metrics['counts']['posts'] : 0,
			'pages'      => isset( $metrics['counts']['pages'] ) ? $metrics['counts']['pages'] : 0,
			'comments'   => isset( $metrics['counts']['comments'] ) ? $metrics['counts']['comments'] : 0,
			'users'      => isset( $metrics['counts']['users'] ) ? $metrics['counts']['users'] : 0,
			'categories' => isset( $metrics['counts']['categories'] ) ? $metrics['counts']['categories'] : 0,
			'tags'       => isset( $metrics['counts']['tags'] ) ? $metrics['counts']['tags'] : 0,
			'images'     => isset( $metrics['counts']['images'] ) ? $metrics['counts']['images'] : 0,
			'filesystem_mb' => isset( $metrics['sizes']['filesystem_mb'] ) ? $metrics['sizes']['filesystem_mb'] : 0,
			'database_mb' => isset( $metrics['sizes']['database_mb'] ) ? $metrics['sizes']['database_mb'] : 0,
			'total_mb'   => isset( $metrics['sizes']['total_mb'] ) ? $metrics['sizes']['total_mb'] : 0,
			'duration'   => isset( $metrics['timing']['formatted'] ) ? $metrics['timing']['formatted'] : '0s',
		);
	}

	/**
	 * Count all generated benchmark content for a specific run
	 *
	 * @param int $run_id Run ID to filter by.
	 * @return array Content counts.
	 */
	private static function count_generated_content( $run_id ) {
		global $wpdb;

		// Count posts for this run
		$posts_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				WHERE ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_nettingale_benchmark_item' AND meta_value = %d)
				AND post_type = 'post'",
				$run_id
			)
		);

		// Count pages for this run
		$pages_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				WHERE ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_nettingale_benchmark_item' AND meta_value = %d)
				AND post_type = 'page'",
				$run_id
			)
		);

		// Count attachments (images) for this run
		$images_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				WHERE ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_nettingale_benchmark_item' AND meta_value = %d)
				AND post_type = 'attachment'",
				$run_id
			)
		);

		// Count users for this run
		$users_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->users}
				WHERE ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_nettingale_benchmark_user' AND meta_value = %d)",
				$run_id
			)
		);

		// Count comments for this run
		$comments_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->comments}
				WHERE comment_ID IN (SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key = '_nettingale_benchmark_comment' AND meta_value = %d)",
				$run_id
			)
		);

		// Count categories for this run
		$categories_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->terms} t
				INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy = 'category'
				AND t.term_id IN (SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = '_nettingale_benchmark_term' AND meta_value = %d)",
				$run_id
			)
		);

		// Count tags for this run
		$tags_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->terms} t
				INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy = 'post_tag'
				AND t.term_id IN (SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = '_nettingale_benchmark_term' AND meta_value = %d)",
				$run_id
			)
		);

		return array(
			'posts'      => (int) $posts_count,
			'pages'      => (int) $pages_count,
			'images'     => (int) $images_count,
			'users'      => (int) $users_count,
			'comments'   => (int) $comments_count,
			'categories' => (int) $categories_count,
			'tags'       => (int) $tags_count,
			'total'      => (int) ( $posts_count + $pages_count + $images_count + $users_count + $comments_count + $categories_count + $tags_count ),
		);
	}

	/**
	 * Get filesystem size for benchmark uploads in bytes for a specific run
	 *
	 * @param int $run_id Run ID to filter by.
	 * @return int Filesystem size in bytes.
	 */
	private static function get_filesystem_size( $run_id ) {
		global $wpdb;

		// Get all attachment IDs for this run
		$attachment_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_nettingale_benchmark_item'
				AND meta_value = %d
				AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment')",
				$run_id
			)
		);

		if ( empty( $attachment_ids ) ) {
			return 0;
		}

		// Calculate total disk space used (including WordPress-generated sizes and filesystem blocks)
		$total_size = 0;
		foreach ( $attachment_ids as $attachment_id ) {
			// Get original file
			$file_path = get_attached_file( $attachment_id );
			if ( $file_path && file_exists( $file_path ) ) {
				// Use stat to get actual disk blocks allocated
				$stat = stat( $file_path );
				if ( $stat !== false && isset( $stat['blocks'] ) ) {
					// blocks is in 512-byte units
					$total_size += $stat['blocks'] * 512;
				} else {
					// Fallback to filesize if stat fails
					$total_size += filesize( $file_path );
				}
			}

			// Get all WordPress-generated image sizes (thumbnail, medium, large, etc.)
			$metadata = wp_get_attachment_metadata( $attachment_id );
			if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				// Get the directory of the original file
				$file_dir = dirname( $file_path );

				foreach ( $metadata['sizes'] as $size => $size_data ) {
					if ( ! empty( $size_data['file'] ) ) {
						$size_file_path = $file_dir . '/' . $size_data['file'];
						if ( file_exists( $size_file_path ) ) {
							// Use stat to get actual disk blocks allocated
							$stat = stat( $size_file_path );
							if ( $stat !== false && isset( $stat['blocks'] ) ) {
								// blocks is in 512-byte units
								$total_size += $stat['blocks'] * 512;
							} else {
								// Fallback to filesize if stat fails
								$total_size += filesize( $size_file_path );
							}
						}
					}
				}
			}
		}

		return $total_size;
	}

	/**
	 * Calculate performance rates
	 *
	 * @param array $counts Content counts.
	 * @param int   $duration Duration in seconds.
	 * @param int   $fs_size Filesystem size in bytes.
	 * @return array Rates.
	 */
	private static function calculate_rates( $counts, $duration, $fs_size ) {
		if ( $duration <= 0 ) {
			$duration = 1; // Prevent division by zero
		}

		return array(
			'posts_per_second'    => round( $counts['posts'] / $duration, 2 ),
			'pages_per_second'    => round( $counts['pages'] / $duration, 2 ),
			'comments_per_second' => round( $counts['comments'] / $duration, 2 ),
			'items_per_second'    => round( $counts['total'] / $duration, 2 ),
			'mb_per_second'       => round( ( $fs_size / 1024 / 1024 ) / $duration, 2 ),
		);
	}

	/**
	 * Format duration into human-readable string
	 *
	 * @param int $seconds Duration in seconds.
	 * @return string Formatted duration.
	 */
	private static function format_duration( $seconds ) {
		if ( $seconds < 60 ) {
			return sprintf( '%ds', $seconds );
		}

		$minutes = floor( $seconds / 60 );
		$remaining_seconds = $seconds % 60;

		if ( $minutes < 60 ) {
			return sprintf( '%dm %ds', $minutes, $remaining_seconds );
		}

		$hours = floor( $minutes / 60 );
		$remaining_minutes = $minutes % 60;

		return sprintf( '%dh %dm %ds', $hours, $remaining_minutes, $remaining_seconds );
	}

	/**
	 * Get full metrics for a run
	 *
	 * @param int $run_id Run ID.
	 * @return array|null Full metrics or null if not found.
	 */
	public static function get_metrics( $run_id ) {
		global $wpdb;

		// Security: Use direct prefix reference for table name
		$metrics_json = $wpdb->get_var( $wpdb->prepare( "SELECT metrics FROM {$wpdb->prefix}nettingale_benchmark_runs WHERE id = %d", $run_id ) );

		if ( ! $metrics_json ) {
			return null;
		}

		return json_decode( $metrics_json, true );
	}

	/**
	 * Get metrics for all runs
	 *
	 * @return array Array of runs with metrics.
	 */
	public static function get_all_runs() {
		global $wpdb;

		// Security: Use direct prefix reference for table name
		$runs = $wpdb->get_results(
			"SELECT id, tier, status, started_at, completed_at, metrics
			FROM {$wpdb->prefix}nettingale_benchmark_runs
			ORDER BY started_at DESC",
			ARRAY_A
		);

		// Decode metrics JSON for each run
		foreach ( $runs as &$run ) {
			if ( ! empty( $run['metrics'] ) ) {
				$run['metrics'] = json_decode( $run['metrics'], true );
			} else {
				$run['metrics'] = array();
			}
		}

		return $runs;
	}
}

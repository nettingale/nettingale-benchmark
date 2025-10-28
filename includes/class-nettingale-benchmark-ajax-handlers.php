<?php
/**
 * AJAX Handlers for Metrics Export
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_Ajax_Handlers
 */
class Nettingale_Benchmark_Ajax_Handlers {

	/**
	 * Initialize AJAX handlers
	 */
	public static function init() {
		// Get metrics AJAX handler
		add_action( 'wp_ajax_nettingale_benchmark_get_metrics', array( __CLASS__, 'get_metrics' ) );

		// Export JSON AJAX handler
		add_action( 'wp_ajax_nettingale_benchmark_export_json', array( __CLASS__, 'export_json' ) );

		// Export CSV AJAX handler
		add_action( 'wp_ajax_nettingale_benchmark_export_csv', array( __CLASS__, 'export_csv' ) );

		// Cleanup AJAX handlers
		add_action( 'wp_ajax_nettingale_benchmark_get_cleanup_stats', array( __CLASS__, 'get_cleanup_stats' ) );
		add_action( 'wp_ajax_nettingale_benchmark_cleanup_data', array( __CLASS__, 'cleanup_data' ) );

		// Settings AJAX handler
		add_action( 'wp_ajax_nettingale_benchmark_save_settings', array( __CLASS__, 'save_settings' ) );

		// Clear locks AJAX handler (emergency unlock)
		add_action( 'wp_ajax_nettingale_benchmark_clear_locks', array( __CLASS__, 'clear_locks' ) );

		// Check for stuck lock AJAX handler
		add_action( 'wp_ajax_nettingale_benchmark_check_stuck_lock', array( __CLASS__, 'check_stuck_lock' ) );

		// PHP environment status AJAX handler
		add_action( 'wp_ajax_nettingale_benchmark_get_php_status', array( __CLASS__, 'get_php_environment_status' ) );
	}

	/**
	 * Get metrics for a run
	 */
	public static function get_metrics() {
		// Check nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Get run ID
		$run_id = isset( $_POST['run_id'] ) ? intval( $_POST['run_id'] ) : 0;

		if ( ! $run_id ) {
			wp_send_json_error( 'Invalid run ID.' );
		}

		// Get metrics
		$metrics = Nettingale_Benchmark_Metrics_Collector::get_metrics( $run_id );

		if ( ! $metrics ) {
			wp_send_json_error( 'Metrics not found.' );
		}

		// Get run data
		global $wpdb;
		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';
		$run = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $run_id ), ARRAY_A );

		if ( ! $run ) {
			wp_send_json_error( 'Run not found.' );
		}

		wp_send_json_success(
			array(
				'metrics' => $metrics,
				'run'     => array(
					'id'     => $run['id'],
					'tier'   => $run['tier'],
					'status' => $run['status'],
				),
			)
		);
	}

	/**
	 * Export metrics as JSON
	 */
	public static function export_json() {
		// Check nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Get run ID
		$run_id = isset( $_POST['run_id'] ) ? intval( $_POST['run_id'] ) : 0;

		if ( ! $run_id ) {
			wp_send_json_error( 'Invalid run ID.' );
		}

		// Get metrics
		$metrics = Nettingale_Benchmark_Metrics_Collector::get_metrics( $run_id );

		if ( ! $metrics ) {
			wp_send_json_error( 'Metrics not found.' );
		}

		// Get run data
		global $wpdb;
		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';
		$run = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $run_id ), ARRAY_A );

		if ( ! $run ) {
			wp_send_json_error( 'Run not found.' );
		}

		// Compile export data
		$export_data = array(
			'run_id'      => $run['id'],
			'tier'        => $run['tier'],
			'status'      => $run['status'],
			'started_at'  => $run['started_at'],
			'completed_at' => $run['completed_at'],
			'metrics'     => $metrics,
			'exported_at' => current_time( 'mysql' ),
			'plugin_version' => NETTINGALE_BENCHMARK_VERSION,
		);

		wp_send_json_success( $export_data );
	}

	/**
	 * Export metrics as CSV
	 */
	public static function export_csv() {
		// Check nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Get run ID
		$run_id = isset( $_POST['run_id'] ) ? intval( $_POST['run_id'] ) : 0;

		if ( ! $run_id ) {
			wp_send_json_error( 'Invalid run ID.' );
		}

		// Get metrics
		$metrics = Nettingale_Benchmark_Metrics_Collector::get_metrics( $run_id );

		if ( ! $metrics ) {
			wp_send_json_error( 'Metrics not found.' );
		}

		// Get run data
		global $wpdb;
		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';
		$run = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $run_id ), ARRAY_A );

		if ( ! $run ) {
			wp_send_json_error( 'Run not found.' );
		}

		// Build CSV content
		$csv = self::generate_csv( $run, $metrics );

		wp_send_json_success( $csv );
	}

	/**
	 * Generate CSV from metrics data
	 *
	 * @param array $run Run data.
	 * @param array $metrics Metrics data.
	 * @return string CSV content.
	 */
	private static function generate_csv( $run, $metrics ) {
		$csv = array();

		// Header
		$csv[] = 'Nettingale Benchmark - Run #' . $run['id'];
		$csv[] = 'Tier,' . $run['tier'];
		$csv[] = 'Status,' . $run['status'];
		$csv[] = 'Started,' . $run['started_at'];
		$csv[] = 'Completed,' . ( $run['completed_at'] ? $run['completed_at'] : 'N/A' );
		$csv[] = 'Duration,' . ( isset( $metrics['timing']['formatted'] ) ? $metrics['timing']['formatted'] : 'N/A' );
		$csv[] = '';

		// Content counts
		$csv[] = 'Content Generated';
		$csv[] = 'Type,Count';
		$csv[] = 'Posts,' . ( isset( $metrics['counts']['posts'] ) ? $metrics['counts']['posts'] : 0 );
		$csv[] = 'Pages,' . ( isset( $metrics['counts']['pages'] ) ? $metrics['counts']['pages'] : 0 );
		$csv[] = 'Comments,' . ( isset( $metrics['counts']['comments'] ) ? $metrics['counts']['comments'] : 0 );
		$csv[] = 'Users,' . ( isset( $metrics['counts']['users'] ) ? $metrics['counts']['users'] : 0 );
		$csv[] = 'Categories,' . ( isset( $metrics['counts']['categories'] ) ? $metrics['counts']['categories'] : 0 );
		$csv[] = 'Tags,' . ( isset( $metrics['counts']['tags'] ) ? $metrics['counts']['tags'] : 0 );
		$csv[] = 'Images,' . ( isset( $metrics['counts']['images'] ) ? $metrics['counts']['images'] : 0 );
		$csv[] = 'Total Items,' . ( isset( $metrics['counts']['total'] ) ? $metrics['counts']['total'] : 0 );
		$csv[] = '';

		// Storage sizes
		$csv[] = 'Storage Usage';
		$csv[] = 'Type,Size (MB)';
		$csv[] = 'Database,' . ( isset( $metrics['sizes']['database_mb'] ) ? $metrics['sizes']['database_mb'] : 0 );
		$csv[] = 'Filesystem,' . ( isset( $metrics['sizes']['filesystem_mb'] ) ? $metrics['sizes']['filesystem_mb'] : 0 );
		$csv[] = 'Total,' . ( isset( $metrics['sizes']['total_mb'] ) ? $metrics['sizes']['total_mb'] : 0 );
		$csv[] = '';

		// Performance rates
		$csv[] = 'Performance Rates';
		$csv[] = 'Metric,Rate';
		$csv[] = 'Posts per Second,' . ( isset( $metrics['rates']['posts_per_second'] ) ? $metrics['rates']['posts_per_second'] : 0 );
		$csv[] = 'Pages per Second,' . ( isset( $metrics['rates']['pages_per_second'] ) ? $metrics['rates']['pages_per_second'] : 0 );
		$csv[] = 'Comments per Second,' . ( isset( $metrics['rates']['comments_per_second'] ) ? $metrics['rates']['comments_per_second'] : 0 );
		$csv[] = 'Total Items per Second,' . ( isset( $metrics['rates']['items_per_second'] ) ? $metrics['rates']['items_per_second'] : 0 );
		$csv[] = 'MB per Second,' . ( isset( $metrics['rates']['mb_per_second'] ) ? $metrics['rates']['mb_per_second'] : 0 );
		$csv[] = '';

		// Footer
		$csv[] = 'Exported,' . current_time( 'mysql' );
		$csv[] = 'Plugin Version,' . NETTINGALE_BENCHMARK_VERSION;

		return implode( "\n", $csv );
	}

	/**
	 * Get cleanup statistics
	 */
	public static function get_cleanup_stats() {
		// Check nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Get statistics
		$stats = Nettingale_Benchmark_Cleanup_Manager::get_statistics();

		wp_send_json_success( $stats );
	}

	/**
	 * Cleanup benchmark data (delete all)
	 */
	public static function cleanup_data() {
		// Check nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Delete all benchmark data including history
		$results = Nettingale_Benchmark_Cleanup_Manager::delete_all();

		wp_send_json_success(
			array(
				'results' => $results,
			)
		);
	}

	/**
	 * Save plugin settings
	 */
	public static function save_settings() {
		// Check nonce
		check_ajax_referer( 'nettingale_benchmark_settings', 'nonce' );

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Get settings from POST
		$cleanup_on_deactivate = isset( $_POST['cleanup_on_deactivate'] ) && '1' === $_POST['cleanup_on_deactivate'] ? '1' : '0';

		// Save settings
		update_option( 'nettingale_benchmark_cleanup_on_deactivate', $cleanup_on_deactivate );

		wp_send_json_success(
			array(
				'message'               => 'Settings saved successfully.',
				'cleanup_on_deactivate' => $cleanup_on_deactivate,
			)
		);
	}

	/**
	 * Clear all locks (emergency unlock)
	 *
	 * Clears transient locks and stops any running benchmarks
	 */
	public static function clear_locks() {
		// Check nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		global $wpdb;

		// Clear transient lock
		$transient_cleared = delete_transient( 'nettingale_benchmark_lock' );

		// Stop any running benchmarks
		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';
		$stopped_count = $wpdb->query(
			"UPDATE {$table_name}
			SET status = 'stopped', completed_at = NOW()
			WHERE status = 'running'"
		);

		// GD filter will auto-disable on next request when transient is cleared

		wp_send_json_success(
			array(
				'message'          => 'All locks cleared successfully.',
				'transient_cleared' => $transient_cleared,
				'benchmarks_stopped' => $stopped_count,
			)
		);
	}

	/**
	 * Check for stuck lock
	 *
	 * Returns true if a transient lock exists but no benchmarks are running
	 */
	public static function check_stuck_lock() {
		// Check nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		global $wpdb;

		// Check transient lock
		$lock_exists = get_transient( 'nettingale_benchmark_lock' );

		// Check for running benchmarks
		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';
		$running_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} WHERE status = 'running'"
		);

		// Stuck lock = lock exists BUT no running benchmarks
		$is_stuck = ( $lock_exists && 0 === (int) $running_count );

		wp_send_json_success(
			array(
				'stuck_lock'    => $is_stuck,
				'lock_value'    => $lock_exists,
				'running_count' => (int) $running_count,
			)
		);
	}

	/**
	 * Get PHP environment status
	 *
	 * Returns current PHP settings with recommendations
	 */
	public static function get_php_environment_status() {
		// Check nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		$status = array();

		// 1. memory_limit
		$memory_limit = ini_get( 'memory_limit' );
		$memory_bytes = self::convert_to_bytes( $memory_limit );
		$status['memory_limit'] = array(
			'current'     => $memory_limit,
			'recommended' => '512M (Medium tier) / 1024M (Large tier)',
			'status'      => self::get_memory_status( $memory_bytes ),
			'bytes'       => $memory_bytes,
		);

		// 2. max_execution_time
		$max_execution_time = ini_get( 'max_execution_time' );
		$status['max_execution_time'] = array(
			'current'     => 0 === (int) $max_execution_time ? 'Unlimited (0)' : $max_execution_time . ' seconds',
			'recommended' => '300+ seconds or 0 (unlimited)',
			'status'      => self::get_execution_time_status( (int) $max_execution_time ),
		);

		// 3. post_max_size
		$post_max_size = ini_get( 'post_max_size' );
		$post_bytes = self::convert_to_bytes( $post_max_size );
		$status['post_max_size'] = array(
			'current'     => $post_max_size,
			'recommended' => '32M+',
			'status'      => self::get_file_size_status( $post_bytes ),
		);

		// 4. upload_max_filesize
		$upload_max_filesize = ini_get( 'upload_max_filesize' );
		$upload_bytes = self::convert_to_bytes( $upload_max_filesize );
		$status['upload_max_filesize'] = array(
			'current'     => $upload_max_filesize,
			'recommended' => '32M+',
			'status'      => self::get_file_size_status( $upload_bytes ),
		);

		// 5. GD Library (Critical)
		$gd_available = extension_loaded( 'gd' );
		$gd_info = array();
		if ( $gd_available && function_exists( 'gd_info' ) ) {
			$gd_info = gd_info();
		}

		$status['gd_library'] = array(
			'current'     => $gd_available ? 'Available (' . ( isset( $gd_info['GD Version'] ) ? $gd_info['GD Version'] : 'Unknown version' ) . ')' : 'Not Available',
			'recommended' => 'Required - must be installed',
			'status'      => $gd_available ? 'ok' : 'critical',
			'jpeg_support' => $gd_available && ! empty( $gd_info['JPEG Support'] ),
			'png_support' => $gd_available && ! empty( $gd_info['PNG Support'] ),
		);

		wp_send_json_success( $status );
	}

	/**
	 * Convert PHP size notation to bytes
	 *
	 * @param string $size Size string (e.g., '256M', '1G').
	 * @return int Size in bytes.
	 */
	private static function convert_to_bytes( $size ) {
		$size = trim( $size );
		$last = strtolower( $size[ strlen( $size ) - 1 ] );
		$size = (int) $size;

		switch ( $last ) {
			case 'g':
				$size *= 1024;
				// Fall through.
			case 'm':
				$size *= 1024;
				// Fall through.
			case 'k':
				$size *= 1024;
		}

		return $size;
	}

	/**
	 * Get memory limit status
	 *
	 * @param int $bytes Memory in bytes.
	 * @return string Status: ok, warning, critical.
	 */
	private static function get_memory_status( $bytes ) {
		$mb_512 = 512 * 1024 * 1024;
		$mb_256 = 256 * 1024 * 1024;
		$mb_128 = 128 * 1024 * 1024;

		if ( $bytes >= $mb_512 ) {
			return 'ok';
		} elseif ( $bytes >= $mb_256 ) {
			return 'warning';
		} else {
			return 'critical';
		}
	}

	/**
	 * Get execution time status
	 *
	 * @param int $seconds Execution time in seconds.
	 * @return string Status: ok, warning, critical.
	 */
	private static function get_execution_time_status( $seconds ) {
		if ( 0 === $seconds || $seconds >= 300 ) {
			return 'ok';
		} elseif ( $seconds >= 60 ) {
			return 'warning';
		} else {
			return 'critical';
		}
	}

	/**
	 * Get file size status
	 *
	 * @param int $bytes File size in bytes.
	 * @return string Status: ok, warning, critical.
	 */
	private static function get_file_size_status( $bytes ) {
		$mb_32 = 32 * 1024 * 1024;
		$mb_8  = 8 * 1024 * 1024;

		if ( $bytes >= $mb_32 ) {
			return 'ok';
		} elseif ( $bytes >= $mb_8 ) {
			return 'warning';
		} else {
			return 'critical';
		}
	}
}

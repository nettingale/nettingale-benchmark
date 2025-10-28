<?php
/**
 * Batch Processor Class
 *
 * Handles AJAX-based batch processing for large-scale content generation
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_Batch_Processor
 */
class Nettingale_Benchmark_Batch_Processor {

        /**
         * Retrieve the configured batch size with a sane fallback.
         *
         * @return int Batch size to use for processing.
         */
        private static function get_batch_size() {
                $batch_size = get_option( 'nettingale_benchmark_batch_size', Nettingale_Benchmark_Config::BATCH_SIZE );
                $batch_size = absint( $batch_size );

                return $batch_size > 0 ? $batch_size : Nettingale_Benchmark_Config::BATCH_SIZE;
        }

	/**
	 * Processing phases in order
	 */
	const PHASES = array(
		'initialize',
		'users',
		'taxonomies',
		'posts',
		'pages',
		'comments',
		'finalize',
	);

	/**
	 * Initialize batch processor
	 */
	public static function init() {
		// Register AJAX handlers
		add_action( 'wp_ajax_nettingale_benchmark_start', array( __CLASS__, 'ajax_start' ) );
		add_action( 'wp_ajax_nettingale_benchmark_process', array( __CLASS__, 'ajax_process' ) );
		add_action( 'wp_ajax_nettingale_benchmark_stop', array( __CLASS__, 'ajax_stop' ) );
		add_action( 'wp_ajax_nettingale_benchmark_status', array( __CLASS__, 'ajax_status' ) );
		add_action( 'wp_ajax_nettingale_benchmark_get_running', array( __CLASS__, 'ajax_get_running' ) );
		add_action( 'wp_ajax_nettingale_benchmark_process_all', array( __CLASS__, 'ajax_process_all' ) );
	}

	/**
	 * Start a new benchmark run
	 *
	 * AJAX handler for starting benchmark
	 */
	public static function ajax_start() {
		// Check permissions and nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		global $wpdb;

		// ATOMIC LOCK: Acquire MySQL lock first (prevents race conditions)
		// This is a true atomic operation at the database level
		$lock_name = 'nettingale_benchmark_start_lock';
		$lock_timeout = 0; // Don't wait, fail immediately if locked
		$lock_acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, $lock_timeout ) );

		if ( ! $lock_acquired ) {
			wp_send_json_error( array( 'message' => 'Benchmark already in progress. Please wait for it to complete.' ) );
		}

		// Now that we have the lock, check for running benchmarks
		$running = self::get_running_run();
		if ( $running ) {
			// Release MySQL lock
			$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			wp_send_json_error( array( 'message' => 'Another benchmark is currently running.' ) );
		}

		// Get tier from request
		$tier = isset( $_POST['tier'] ) ? sanitize_text_field( wp_unslash( $_POST['tier'] ) ) : 'small';

		if ( ! Nettingale_Benchmark_Config::tier_exists( $tier ) ) {
			// Release MySQL lock
			$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			wp_send_json_error( array( 'message' => 'Invalid tier selected.' ) );
		}

		// Set transient lock for additional protection (expires in 2 hours as safety measure)
		set_transient( 'nettingale_benchmark_lock', time(), 2 * HOUR_IN_SECONDS );

		// Create run record
		$run_id = self::create_run( $tier );

		// Release MySQL lock after run is created
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );

		if ( ! $run_id ) {
			// Release transient lock if run creation failed
			delete_transient( 'nettingale_benchmark_lock' );
			wp_send_json_error( array( 'message' => 'Failed to create run record.' ) );
		}

		// Force GD library for image processing to prevent ImageMagick timeout issues
		add_filter( 'wp_image_editors', 'nettingale_benchmark_force_gd_editor', 999 );

		wp_send_json_success(
			array(
				'run_id'  => $run_id,
				'tier'    => $tier,
				'message' => 'Benchmark started successfully.',
			)
		);
	}

	/**
	 * Process a batch
	 *
	 * AJAX handler for batch processing
	 */
	public static function ajax_process() {
		// Check permissions and nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		// Get run ID
		$run_id = isset( $_POST['run_id'] ) ? absint( $_POST['run_id'] ) : 0;

		if ( ! $run_id ) {
			wp_send_json_error( array( 'message' => 'Invalid run ID.' ) );
		}

		// Get run state
		$state = self::get_run_state( $run_id );

		if ( ! $state ) {
			wp_send_json_error( array( 'message' => 'Run not found.' ) );
		}

		// Verify this run is actually in 'running' status (prevent processing completed/stopped runs)
		if ( isset( $state['status'] ) && 'running' !== $state['status'] ) {
			wp_send_json_error( array( 'message' => 'This benchmark is not currently running (status: ' . esc_html( $state['status'] ) . ').' ) );
		}

		// Process current phase
		$result = self::process_batch( $run_id, $state );

		if ( is_wp_error( $result ) ) {
			self::update_run_status( $run_id, 'failed' );
			// GD filter will auto-disable when transient lock is cleared
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Stop a benchmark run
	 *
	 * AJAX handler for stopping benchmark
	 */
	public static function ajax_stop() {
		// Check permissions and nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		// Get run ID
		$run_id = isset( $_POST['run_id'] ) ? absint( $_POST['run_id'] ) : 0;

		if ( ! $run_id ) {
			wp_send_json_error( array( 'message' => 'Invalid run ID.' ) );
		}

		// Update status
		self::update_run_status( $run_id, 'stopped' );

		// Release lock when stopped (GD filter will auto-disable on next request)
		delete_transient( 'nettingale_benchmark_lock' );

		wp_send_json_success( array( 'message' => 'Benchmark stopped.' ) );
	}

	/**
	 * Get benchmark run status
	 *
	 * AJAX handler for status check
	 */
	public static function ajax_status() {
		// Check permissions and nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		// Get run ID
		$run_id = isset( $_POST['run_id'] ) ? absint( $_POST['run_id'] ) : 0;

		if ( ! $run_id ) {
			wp_send_json_error( array( 'message' => 'Invalid run ID.' ) );
		}

		$state = self::get_run_state( $run_id );

		if ( ! $state ) {
			wp_send_json_error( array( 'message' => 'Run not found.' ) );
		}

		wp_send_json_success( $state );
	}

	/**
	 * Get any currently running benchmark
	 *
	 * AJAX handler for checking running benchmarks on page load
	 */
	public static function ajax_get_running() {
		// Check permissions and nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		$running_run = self::get_running_run();

		if ( $running_run ) {
			wp_send_json_success( $running_run );
		} else {
			wp_send_json_success( array( 'running' => false ) );
		}
	}

	/**
	 * Process all batches in background
	 *
	 * AJAX handler for background processing
	 */
	public static function ajax_process_all() {
		// Check permissions and nonce
		check_ajax_referer( 'nettingale_benchmark_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		// Get run ID
		$run_id = isset( $_POST['run_id'] ) ? absint( $_POST['run_id'] ) : 0;

		if ( ! $run_id ) {
			wp_send_json_error( array( 'message' => 'Invalid run ID.' ) );
		}

		// Allow script to run even if user disconnects
		ignore_user_abort( true );
		set_time_limit( 0 );

		// Process all batches until complete
		$max_iterations = 10000; // Safety limit
		$iteration = 0;

		while ( $iteration < $max_iterations ) {
			$state = self::get_run_state( $run_id );

			if ( ! $state || $state['status'] !== 'running' ) {
				break;
			}

			// Process one batch
			$result = self::process_batch( $run_id, $state );

			if ( ! $result || is_wp_error( $result ) || ( isset( $result['done'] ) && $result['done'] ) ) {
				break;
			}

			$iteration++;

			// Small delay to prevent overwhelming the server
			usleep( 10000 ); // 10ms
		}

		$final_state = self::get_run_state( $run_id );

		// Clean up lock
		delete_transient( 'nettingale_benchmark_lock' );

		wp_send_json_success( $final_state );
	}

	/**
	 * Get currently running benchmark run
	 *
	 * @return array|false Run state or false if none running.
	 */
	private static function get_running_run() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';

		$run = $wpdb->get_row(
			"SELECT * FROM {$table_name} WHERE status = 'running' ORDER BY id DESC LIMIT 1",
			ARRAY_A
		);

		if ( ! $run ) {
			return false;
		}

		return self::get_run_state( $run['id'] );
	}

	/**
	 * Create a new benchmark run record
	 *
	 * @param string $tier Tier name.
	 * @return int|false Run ID on success, false on failure.
	 */
	private static function create_run( $tier ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';

		$result = $wpdb->insert(
			$table_name,
			array(
				'tier'          => $tier,
				'started_at'    => current_time( 'mysql' ),
				'status'        => 'running',
				'current_phase' => 'initialize',
				'current_batch' => 0,
			),
			array( '%s', '%s', '%s', '%s', '%d' )
		);

		if ( ! $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get run state
	 *
	 * @param int $run_id Run ID.
	 * @return array|false Run state or false if not found.
	 */
	private static function get_run_state( $run_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';

		$run = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$run_id
			),
			ARRAY_A
		);

		if ( ! $run ) {
			return false;
		}

		// Get tier config
		$tier_config = Nettingale_Benchmark_Config::get_tier( $run['tier'] );

		// Parse metrics JSON
		if ( ! empty( $run['metrics'] ) ) {
			$run['metrics'] = json_decode( $run['metrics'], true );
		} else {
			$run['metrics'] = array();
		}

		$run['tier_config'] = $tier_config;

		// Calculate progress percentage and message
		$progress_data = self::calculate_progress( $run, $tier_config );
		$run['progress'] = $progress_data['progress'];
		$run['message'] = $progress_data['message'];

		return $run;
	}

	/**
	 * Calculate progress percentage and message
	 *
	 * @param array $run         Run data.
	 * @param array $tier_config Tier configuration.
	 * @return array Progress data with 'progress' and 'message' keys.
	 */
	private static function calculate_progress( $run, $tier_config ) {
		$phase = $run['current_phase'];
		$batch = (int) $run['current_batch'];

		// Phase weights (total should be 100)
		$phase_weights = array(
			'initialize'  => 5,
			'users'       => 5,
			'taxonomies'  => 5,
			'posts'       => 40,
			'pages'       => 15,
			'comments'    => 25,
			'finalize'    => 5,
		);

		// Base progress for completed phases
		$base_progress = 0;
		$phase_index = array_search( $phase, self::PHASES, true );

		if ( $phase_index !== false ) {
			for ( $i = 0; $i < $phase_index; $i++ ) {
				$base_progress += $phase_weights[ self::PHASES[ $i ] ];
			}
		}

		// Calculate progress within current phase
                $phase_progress = 0;
                $message = '';
                $batch_size = self::get_batch_size();

		switch ( $phase ) {
			case 'initialize':
				$phase_progress = 50; // Halfway through initialization
				$message = 'Initializing benchmark...';
				break;

			case 'users':
				$phase_progress = 50;
				$message = sprintf( 'Creating %d users...', $tier_config['users'] );
				break;

			case 'taxonomies':
				$phase_progress = 50;
				$message = sprintf(
					'Creating %d categories and %d tags...',
					$tier_config['categories'],
					$tier_config['tags']
				);
				break;

			case 'posts':
                                $total_batches = ceil( $tier_config['posts'] / $batch_size );
                                $phase_progress = $total_batches > 0 ? ( $batch / $total_batches ) * 100 : 0;
                                $message = sprintf(
                                        'Creating posts: %d of %d',
                                        min( $batch * $batch_size, $tier_config['posts'] ),
                                        $tier_config['posts']
                                );
                                break;

                        case 'pages':
                                $total_batches = ceil( $tier_config['pages'] / $batch_size );
                                $phase_progress = $total_batches > 0 ? ( $batch / $total_batches ) * 100 : 0;
                                $message = sprintf(
                                        'Creating pages: %d of %d',
                                        min( $batch * $batch_size, $tier_config['pages'] ),
                                        $tier_config['pages']
                                );
                                break;

                        case 'comments':
                                $total_batches = ceil( $tier_config['comments'] / $batch_size );
                                $phase_progress = $total_batches > 0 ? ( $batch / $total_batches ) * 100 : 0;
                                $message = sprintf(
                                        'Creating comments: %d of %d',
                                        min( $batch * $batch_size, $tier_config['comments'] ),
                                        $tier_config['comments']
                                );
                                break;

			case 'finalize':
				$phase_progress = 50;
				$message = 'Finalizing benchmark and collecting metrics...';
				break;

			default:
				$message = 'Processing...';
				break;
		}

		// Calculate final progress percentage
		$current_phase_weight = $phase_weights[ $phase ] ?? 0;
		$progress = $base_progress + ( ( $phase_progress / 100 ) * $current_phase_weight );
		$progress = min( 99, max( 0, (int) $progress ) ); // Cap at 99% until finalize completes

		return array(
			'progress' => $progress,
			'message'  => $message,
		);
	}

	/**
	 * Update run status
	 *
	 * @param int    $run_id Run ID.
	 * @param string $status New status.
	 * @return bool Success.
	 */
	private static function update_run_status( $run_id, $status ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';

		$data = array( 'status' => $status );
		$format = array( '%s' );

		// If completing, add completion time
		if ( 'completed' === $status ) {
			$run = self::get_run_state( $run_id );
			$started = strtotime( $run['started_at'] );
			$duration = time() - $started;

			$data['completed_at'] = current_time( 'mysql' );
			$data['duration_seconds'] = $duration;
			$format[] = '%s';
			$format[] = '%d';
		}

		return $wpdb->update(
			$table_name,
			$data,
			array( 'id' => $run_id ),
			$format,
			array( '%d' )
		);
	}

	/**
	 * Update run phase
	 *
	 * @param int    $run_id Run ID.
	 * @param string $phase  Phase name.
	 * @param int    $batch  Batch number.
	 * @return bool Success.
	 */
	private static function update_run_phase( $run_id, $phase, $batch = 0 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';

		return $wpdb->update(
			$table_name,
			array(
				'current_phase' => $phase,
				'current_batch' => $batch,
			),
			array( 'id' => $run_id ),
			array( '%s', '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Process a single batch
	 *
	 * @param int   $run_id Run ID.
	 * @param array $state  Current run state.
	 * @return array|WP_Error Processing result.
	 */
	private static function process_batch( $run_id, $state ) {
		$phase = $state['current_phase'];
		$batch = $state['current_batch'];
		$tier_config = $state['tier_config'];
		$metrics = $state['metrics'];

		// Execute phase
		switch ( $phase ) {
			case 'initialize':
				$result = self::phase_initialize( $run_id, $state );
				break;

			case 'users':
				$result = self::phase_users( $run_id, $state );
				break;

			case 'taxonomies':
				$result = self::phase_taxonomies( $run_id, $state );
				break;

			case 'posts':
				$result = self::phase_posts( $run_id, $state );
				break;

			case 'pages':
				$result = self::phase_pages( $run_id, $state );
				break;

			case 'comments':
				$result = self::phase_comments( $run_id, $state );
				break;

			case 'finalize':
				$result = self::phase_finalize( $run_id, $state );
				break;

			default:
				return new WP_Error( 'invalid_phase', 'Invalid processing phase.' );
		}

		// Get updated state after phase execution to calculate accurate progress
		$updated_state = self::get_run_state( $run_id );
		if ( $updated_state ) {
			// Override progress and message with calculated values for consistency
			$result['progress'] = $updated_state['progress'];
			$result['message'] = $updated_state['message'];
		}

		return $result;
	}

	/**
	 * Phase: Initialize
	 *
	 * @param int   $run_id Run ID.
	 * @param array $state  Current run state.
	 * @return array Processing result.
	 */
	private static function phase_initialize( $run_id, $state ) {
		// Move to next phase
		self::update_run_phase( $run_id, 'users', 0 );

		return array(
			'phase'    => 'initialize',
			'complete' => true,
			'next'     => 'users',
			'progress' => 5,
			'message'  => 'Initialization complete.',
		);
	}

	/**
	 * Phase: Users
	 *
	 * @param int   $run_id Run ID.
	 * @param array $state  Current run state.
	 * @return array Processing result.
	 */
	private static function phase_users( $run_id, $state ) {
		$tier_config = $state['tier_config'];
		$total_users = $tier_config['users'];

		// Create all users in one batch (users are fast)
		$user_ids = Nettingale_Benchmark_User_Generator::create_users( $total_users, 1, $run_id );

		// Store user IDs in metrics
		$metrics = $state['metrics'];
		$metrics['user_ids'] = $user_ids;
		self::update_run_metrics( $run_id, $metrics );

		// Move to next phase
		self::update_run_phase( $run_id, 'taxonomies', 0 );

		return array(
			'phase'       => 'users',
			'complete'    => true,
			'next'        => 'taxonomies',
			'progress'    => 15,
			'message'     => sprintf( 'Created %d users.', count( $user_ids ) ),
			'users_count' => count( $user_ids ),
		);
	}

	/**
	 * Phase: Taxonomies (Categories and Tags)
	 *
	 * @param int   $run_id Run ID.
	 * @param array $state  Current run state.
	 * @return array Processing result.
	 */
	private static function phase_taxonomies( $run_id, $state ) {
		$tier_config = $state['tier_config'];

		// Create categories
		$category_ids = Nettingale_Benchmark_Taxonomy_Generator::create_categories( $tier_config['categories'], 1, $run_id );

		// Create tags
		$tag_ids = Nettingale_Benchmark_Taxonomy_Generator::create_tags( $tier_config['tags'], 1, $run_id );

		// Store in metrics
		$metrics = $state['metrics'];
		$metrics['category_ids'] = $category_ids;
		$metrics['tag_ids'] = $tag_ids;
		self::update_run_metrics( $run_id, $metrics );

		// Move to next phase
		self::update_run_phase( $run_id, 'posts', 0 );

		return array(
			'phase'            => 'taxonomies',
			'complete'         => true,
			'next'             => 'posts',
			'progress'         => 25,
			'message'          => sprintf(
				'Created %d categories and %d tags.',
				count( $category_ids ),
				count( $tag_ids )
			),
			'categories_count' => count( $category_ids ),
			'tags_count'       => count( $tag_ids ),
		);
	}

	/**
	 * Phase: Posts (batch processing)
	 *
	 * @param int   $run_id Run ID.
	 * @param array $state  Current run state.
	 * @return array Processing result.
	 */
	private static function phase_posts( $run_id, $state ) {
		$tier_config = $state['tier_config'];
                $batch = $state['current_batch'];
                $total_posts = $tier_config['posts'];
                $batch_size = self::get_batch_size();

		$start_id = ( $batch * $batch_size ) + 1;
		$count = min( $batch_size, $total_posts - ( $batch * $batch_size ) );

		if ( $count <= 0 ) {
			// Phase complete, move to pages
			self::update_run_phase( $run_id, 'pages', 0 );

			return array(
				'phase'    => 'posts',
				'complete' => true,
				'next'     => 'pages',
				'progress' => 50,
				'message'  => sprintf( 'Created %d posts.', $total_posts ),
			);
		}

		// Get infrastructure
		$metrics = $state['metrics'];
		$options = array(
			'run_id'       => $run_id,
			'user_ids'     => isset( $metrics['user_ids'] ) ? $metrics['user_ids'] : array(),
			'category_ids' => isset( $metrics['category_ids'] ) ? $metrics['category_ids'] : array(),
			'tag_ids'      => isset( $metrics['tag_ids'] ) ? $metrics['tag_ids'] : array(),
			'with_images'  => true,
		);

		// Create batch of posts
		$created_posts = Nettingale_Benchmark_Post_Generator::create_posts( $count, $start_id, $options );

		// Update batch number
		$next_batch = $batch + 1;
		self::update_run_phase( $run_id, 'posts', $next_batch );

		$processed = ( $batch + 1 ) * $batch_size;
		$progress = 25 + ( ( $processed / $total_posts ) * 25 ); // 25-50% range

		return array(
			'phase'    => 'posts',
			'complete' => false,
			'next'     => 'posts',
			'progress' => (int) $progress,
			'message'  => sprintf(
				'Created %d of %d posts...',
				min( $processed, $total_posts ),
				$total_posts
			),
			'batch'    => $next_batch,
			'created'  => count( $created_posts ),
		);
	}

	/**
	 * Phase: Pages (batch processing)
	 *
	 * @param int   $run_id Run ID.
	 * @param array $state  Current run state.
	 * @return array Processing result.
	 */
	private static function phase_pages( $run_id, $state ) {
		$tier_config = $state['tier_config'];
                $batch = $state['current_batch'];
                $total_pages = $tier_config['pages'];
                $batch_size = self::get_batch_size();

		$start_id = ( $batch * $batch_size ) + 1;
		$count = min( $batch_size, $total_pages - ( $batch * $batch_size ) );

		if ( $count <= 0 ) {
			// Phase complete, move to comments
			self::update_run_phase( $run_id, 'comments', 0 );

			return array(
				'phase'    => 'pages',
				'complete' => true,
				'next'     => 'comments',
				'progress' => 70,
				'message'  => sprintf( 'Created %d pages.', $total_pages ),
			);
		}

		// Get users
		$metrics = $state['metrics'];
		$options = array(
			'run_id'      => $run_id,
			'user_ids'    => isset( $metrics['user_ids'] ) ? $metrics['user_ids'] : array(),
			'with_images' => true,
		);

		// Create batch of pages
		$created_pages = Nettingale_Benchmark_Post_Generator::create_pages( $count, $start_id, $options );

		// Update batch number
		$next_batch = $batch + 1;
		self::update_run_phase( $run_id, 'pages', $next_batch );

		$processed = ( $batch + 1 ) * $batch_size;
		$progress = 50 + ( ( $processed / $total_pages ) * 20 ); // 50-70% range

		return array(
			'phase'    => 'pages',
			'complete' => false,
			'next'     => 'pages',
			'progress' => (int) $progress,
			'message'  => sprintf(
				'Created %d of %d pages...',
				min( $processed, $total_pages ),
				$total_pages
			),
			'batch'    => $next_batch,
			'created'  => count( $created_pages ),
		);
	}

	/**
	 * Phase: Comments
	 *
	 * @param int   $run_id Run ID.
	 * @param array $state  Current run state.
	 * @return array Processing result.
	 */
	private static function phase_comments( $run_id, $state ) {
		// Get all posts and pages
		$post_ids = Nettingale_Benchmark_Post_Generator::get_benchmark_posts( 'post' );
		$page_ids = Nettingale_Benchmark_Post_Generator::get_benchmark_posts( 'page' );
		$all_content_ids = array_merge( $post_ids, $page_ids );

		// Get users
		$metrics = $state['metrics'];
		$user_ids = isset( $metrics['user_ids'] ) ? $metrics['user_ids'] : array();

		// Calculate max comments per post from tier configuration
		$tier_config = Nettingale_Benchmark_Config::get_tier( $state['tier'] );
		$total_content_items = count( $all_content_ids );
		$max_comments_per_post = $total_content_items > 0
			? ceil( $tier_config['comments'] / $total_content_items )
			: 50;

		// Add comments to posts
		$total_comments = Nettingale_Benchmark_Post_Generator::add_comments_to_posts( $all_content_ids, $user_ids, $max_comments_per_post, $run_id );

		// Move to finalize
		self::update_run_phase( $run_id, 'finalize', 0 );

		return array(
			'phase'    => 'comments',
			'complete' => true,
			'next'     => 'finalize',
			'progress' => 90,
			'message'  => sprintf( 'Created %d comments.', $total_comments ),
		);
	}

	/**
	 * Phase: Finalize
	 *
	 * @param int   $run_id Run ID.
	 * @param array $state  Current run state.
	 * @return array Processing result.
	 */
	private static function phase_finalize( $run_id, $state ) {
		// Mark as completed first (to get accurate duration)
		self::update_run_status( $run_id, 'completed' );

		// Release lock on completion (GD filter will auto-disable on next request)
		delete_transient( 'nettingale_benchmark_lock' );

		// Collect comprehensive metrics using Metrics Collector
		Nettingale_Benchmark_Metrics_Collector::store_metrics( $run_id );

		// Get summary for response
		$summary = Nettingale_Benchmark_Metrics_Collector::get_summary( $run_id );

		return array(
			'phase'    => 'finalize',
			'complete' => true,
			'done'     => true,
			'progress' => 100,
			'message'  => 'Benchmark completed successfully!',
			'metrics'  => $summary,
		);
	}

	/**
	 * Update run metrics
	 *
	 * @param int   $run_id Run ID.
	 * @param array $metrics Metrics data.
	 * @return bool Success.
	 */
	private static function update_run_metrics( $run_id, $metrics ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';

		return $wpdb->update(
			$table_name,
			array( 'metrics' => wp_json_encode( $metrics ) ),
			array( 'id' => $run_id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}

// Initialize
Nettingale_Benchmark_Batch_Processor::init();

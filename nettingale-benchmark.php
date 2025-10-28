<?php
/**
 * Plugin Name: Nettingale Benchmark
 * Plugin URI: https://github.com/nettingale/nettingale-benchmark
 * Description: Tests real WordPress performance with actual content generation. Unlike server benchmarks, this creates real posts, comments, and images to stress-test your WordPress installation. 100% local, zero data collection.
 * Version: 1.0.0
 * Author: Nettingale
 * Author URI: https://nettingale.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * Nettingale Benchmark is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Nettingale Benchmark is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Nettingale Benchmark. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 *
 * Privacy & Transparency:
 * This plugin operates 100% locally on your WordPress installation.
 * - No data collection or transmission
 * - No external API calls or connections
 * - No tracking or analytics
 * - All data remains on your server
 * - Complete transparency with open source GPL code
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'NETTINGALE_BENCHMARK_VERSION', '1.0.0' );
define( 'NETTINGALE_BENCHMARK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NETTINGALE_BENCHMARK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader for plugin classes
 */
spl_autoload_register( function( $class ) {
	// Check if class belongs to our plugin
	if ( strpos( $class, 'Nettingale_Benchmark_' ) !== 0 ) {
		return;
	}

	// Convert class name to file name
	$file = str_replace( '_', '-', strtolower( $class ) );
	$file = str_replace( 'nettingale-benchmark-', '', $file );
	$file_path = NETTINGALE_BENCHMARK_PLUGIN_DIR . 'includes/class-nettingale-benchmark-' . $file . '.php';

	// Load the file if it exists
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

/**
 * Activation hook
 */
function nettingale_benchmark_activate() {
	// Create database tables
	nettingale_benchmark_create_tables();

	// Flush rewrite rules
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'nettingale_benchmark_activate' );

/**
 * Create database tables
 */
function nettingale_benchmark_create_tables() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';

	$sql = "CREATE TABLE $table_name (
		id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		tier varchar(20) NOT NULL,
		started_at datetime NOT NULL,
		completed_at datetime DEFAULT NULL,
		duration_seconds int(11) DEFAULT NULL,
		status varchar(20) DEFAULT 'running',
		current_phase varchar(50) DEFAULT NULL,
		current_batch int(11) DEFAULT 0,
		metrics longtext DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY status (status),
		KEY started_at (started_at)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Store database version for future upgrades
	add_option( 'nettingale_benchmark_db_version', NETTINGALE_BENCHMARK_VERSION );
}

/**
 * Deactivation hook
 */
function nettingale_benchmark_deactivate() {
	// ALWAYS clear locks on deactivation (prevent stuck "benchmark running" state)
	delete_transient( 'nettingale_benchmark_lock' );

	// Stop any running benchmarks
	global $wpdb;
	$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';
	$wpdb->query(
		"UPDATE {$table_name}
		SET status = 'stopped', completed_at = NOW()
		WHERE status = 'running'"
	);

	// Check if cleanup on deactivation is enabled
	$cleanup_on_deactivate = get_option( 'nettingale_benchmark_cleanup_on_deactivate', '0' );

	if ( '1' === $cleanup_on_deactivate ) {
		// Clean up all benchmark data
		if ( class_exists( 'Nettingale_Benchmark_Cleanup_Manager' ) ) {
			Nettingale_Benchmark_Cleanup_Manager::delete_all();
		}
	}

	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'nettingale_benchmark_deactivate' );

/**
 * Force WordPress to use GD library for image processing
 *
 * This filter is activated during benchmark runs to ensure consistent,
 * faster image processing. GD is faster than ImageMagick for our use case
 * and prevents timeout issues on servers with low max_execution_time.
 *
 * @param array $editors Array of image editor class names.
 * @return array Array containing only WP_Image_Editor_GD.
 */
function nettingale_benchmark_force_gd_editor( $editors ) {
	return array( 'WP_Image_Editor_GD' );
}

/**
 * Main plugin initialization
 */
function nettingale_benchmark_init() {
	// Force GD library if benchmark is running (check transient lock)
	// This ensures GD is used across ALL AJAX requests during a benchmark
	if ( get_transient( 'nettingale_benchmark_lock' ) ) {
		add_filter( 'wp_image_editors', 'nettingale_benchmark_force_gd_editor', 999 );
	}

	// Initialize batch processor
	if ( class_exists( 'Nettingale_Benchmark_Batch_Processor' ) ) {
		Nettingale_Benchmark_Batch_Processor::init();
	}

	// Initialize AJAX handlers
	if ( class_exists( 'Nettingale_Benchmark_Ajax_Handlers' ) ) {
		Nettingale_Benchmark_Ajax_Handlers::init();
	}
}
add_action( 'plugins_loaded', 'nettingale_benchmark_init' );

/**
 * Add admin menu
 */
function nettingale_benchmark_add_admin_menu() {
	// Add main menu page under Tools
	add_menu_page(
		'Nettingale Benchmark',                    // Page title
		'Nettingale Benchmark',                    // Menu title
		'manage_options',                          // Capability required
		'nettingale-benchmark',                    // Menu slug
		'nettingale_benchmark_seed_data_page',     // Callback function
		'dashicons-performance',                   // Icon
		80                                          // Position
	);

	// Add Seed Data submenu (first submenu replaces parent)
	add_submenu_page(
		'nettingale-benchmark',                    // Parent slug
		'Seed Data',                               // Page title
		'Seed Data',                               // Menu title
		'manage_options',                          // Capability required
		'nettingale-benchmark',                    // Menu slug (same as parent)
		'nettingale_benchmark_seed_data_page'      // Callback function
	);

	// Add History submenu
	add_submenu_page(
		'nettingale-benchmark',                    // Parent slug
		'Benchmark History',                       // Page title
		'History',                                 // Menu title
		'manage_options',                          // Capability required
		'nettingale-benchmark-history',            // Menu slug
		'nettingale_benchmark_history_page'        // Callback function
	);

	// Add Settings submenu
	add_submenu_page(
		'nettingale-benchmark',                    // Parent slug
		'Benchmark Settings',                      // Page title
		'Settings',                                // Menu title
		'manage_options',                          // Capability required
		'nettingale-benchmark-settings',           // Menu slug
		'nettingale_benchmark_settings_page'       // Callback function
	);
}
add_action( 'admin_menu', 'nettingale_benchmark_add_admin_menu' );

/**
 * Enqueue admin scripts and styles
 */
function nettingale_benchmark_enqueue_admin_assets( $hook ) {
	// Only load on our plugin pages
	$allowed_pages = array(
		'toplevel_page_nettingale-benchmark',
		'nettingale-benchmark_page_nettingale-benchmark-history',
		'nettingale-benchmark_page_nettingale-benchmark-settings',
	);

	if ( ! in_array( $hook, $allowed_pages, true ) ) {
		return;
	}

	// Enqueue admin styles
	wp_enqueue_style(
		'nettingale-benchmark-admin-styles',
		NETTINGALE_BENCHMARK_PLUGIN_URL . 'assets/css/admin-styles.css',
		array(),
		NETTINGALE_BENCHMARK_VERSION
	);

	// Enqueue batch processor JavaScript
	wp_enqueue_script(
		'nettingale-benchmark-batch-processor',
		NETTINGALE_BENCHMARK_PLUGIN_URL . 'assets/js/batch-processor.js',
		array( 'jquery' ),
		NETTINGALE_BENCHMARK_VERSION,
		true
	);

	// Enqueue metrics display JavaScript
	wp_enqueue_script(
		'nettingale-benchmark-metrics-display',
		NETTINGALE_BENCHMARK_PLUGIN_URL . 'assets/js/metrics-display.js',
		array( 'jquery' ),
		NETTINGALE_BENCHMARK_VERSION,
		true
	);

	// Enqueue cleanup manager JavaScript
	wp_enqueue_script(
		'nettingale-benchmark-cleanup-manager',
		NETTINGALE_BENCHMARK_PLUGIN_URL . 'assets/js/cleanup-manager.js',
		array( 'jquery' ),
		NETTINGALE_BENCHMARK_VERSION,
		true
	);

	// Enqueue settings manager JavaScript
	wp_enqueue_script(
		'nettingale-benchmark-settings-manager',
		NETTINGALE_BENCHMARK_PLUGIN_URL . 'assets/js/settings-manager.js',
		array( 'jquery' ),
		NETTINGALE_BENCHMARK_VERSION,
		true
	);

	// Localize script with AJAX URL and nonce (for all scripts that need it)
	$localize_data = array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'nettingale_benchmark_action' ),
	);

	wp_localize_script( 'nettingale-benchmark-batch-processor', 'nettingaleBenchmark', $localize_data );
	wp_localize_script( 'nettingale-benchmark-metrics-display', 'nettingaleBenchmark', $localize_data );
	wp_localize_script( 'nettingale-benchmark-cleanup-manager', 'nettingaleBenchmark', $localize_data );
	wp_localize_script( 'nettingale-benchmark-settings-manager', 'nettingaleBenchmark', $localize_data );
}
add_action( 'admin_enqueue_scripts', 'nettingale_benchmark_enqueue_admin_assets' );

/**
 * Render seed data page
 */
function nettingale_benchmark_seed_data_page() {
	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Load seed data page template
	require_once NETTINGALE_BENCHMARK_PLUGIN_DIR . 'admin/views/seed-data-page.php';
}

/**
 * Render history page
 */
function nettingale_benchmark_history_page() {
	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Load history page template
	require_once NETTINGALE_BENCHMARK_PLUGIN_DIR . 'admin/views/history-page.php';
}

/**
 * Render settings page
 */
function nettingale_benchmark_settings_page() {
	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Load settings page template
	require_once NETTINGALE_BENCHMARK_PLUGIN_DIR . 'admin/views/settings-page.php';
}

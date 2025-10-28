<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package Nettingale_Benchmark
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove plugin data on uninstall
 *
 * This script is called when the user clicks "Delete" on the plugin
 * in the WordPress admin plugins page. It removes all plugin data
 * including custom database tables and options.
 *
 * If "Cleanup on Deactivation" setting is enabled, it will also
 * delete all benchmark-generated content (posts, users, comments, etc.)
 */

global $wpdb;

// Check if user wants to delete all benchmark data
$cleanup_on_deactivate = get_option( 'nettingale_benchmark_cleanup_on_deactivate', '0' );

if ( '1' === $cleanup_on_deactivate ) {
	// Delete all benchmark-generated content
	// Load the cleanup manager class
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-nettingale-benchmark-cleanup-manager.php';

	if ( class_exists( 'Nettingale_Benchmark_Cleanup_Manager' ) ) {
		// Delete all benchmark data
		Nettingale_Benchmark_Cleanup_Manager::delete_all();
	}
}

// Delete custom database tables
$table_runs = $wpdb->prefix . 'nettingale_benchmark_runs';
$wpdb->query( "DROP TABLE IF EXISTS {$table_runs}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Delete plugin options
delete_option( 'nettingale_benchmark_db_version' );
delete_option( 'nettingale_benchmark_cleanup_on_deactivate' );

// Delete all transients
delete_transient( 'nettingale_benchmark_lock' );

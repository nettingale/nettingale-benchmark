<?php
/**
 * Cleanup Manager Class
 *
 * Centralized manager for removing all benchmark data
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_Cleanup_Manager
 */
class Nettingale_Benchmark_Cleanup_Manager {

	/**
	 * Delete all benchmark data including history
	 *
	 * @return array Results of cleanup operation.
	 */
	public static function delete_all() {
		$results = array(
			'success' => true,
			'deleted' => array(),
			'errors'  => array(),
		);

		// Delete comments first (they reference posts)
		$comments_deleted = self::delete_comments();
		$results['deleted']['comments'] = $comments_deleted;

		// Delete posts, pages, and attachments
		$posts_deleted = self::delete_posts();
		$results['deleted']['posts'] = $posts_deleted;

		// Delete users
		$users_deleted = self::delete_users();
		$results['deleted']['users'] = $users_deleted;

		// Delete taxonomies
		$terms_deleted = self::delete_taxonomies();
		$results['deleted']['categories'] = $terms_deleted['categories'];
		$results['deleted']['tags'] = $terms_deleted['tags'];

		// Clean up filesystem (images)
		$files_deleted = self::cleanup_filesystem();
		$results['deleted']['files'] = $files_deleted;

		// Delete all run history
		$runs_deleted = self::delete_run_history();
		$results['deleted']['runs'] = $runs_deleted;

		return $results;
	}

	/**
	 * Delete all benchmark run history
	 *
	 * @return int Number of runs deleted.
	 */
	private static function delete_run_history() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nettingale_benchmark_runs';

		// Delete all runs from the table
		$deleted = $wpdb->query( "DELETE FROM $table_name" );

		return $deleted ? (int) $deleted : 0;
	}

	/**
	 * Delete all benchmark posts, pages, and attachments
	 *
	 * @return int Number of items deleted.
	 */
	private static function delete_posts() {
		if ( ! class_exists( 'Nettingale_Benchmark_Post_Generator' ) ) {
			return 0;
		}

		$deleted = 0;

		// Delete posts
		$deleted += Nettingale_Benchmark_Post_Generator::delete_benchmark_posts( 'post' );

		// Delete pages
		$deleted += Nettingale_Benchmark_Post_Generator::delete_benchmark_posts( 'page' );

		// Delete attachments (images)
		$deleted += Nettingale_Benchmark_Post_Generator::delete_benchmark_posts( 'attachment' );

		return $deleted;
	}

	/**
	 * Delete all benchmark users
	 *
	 * @return int Number of users deleted.
	 */
	private static function delete_users() {
		if ( ! class_exists( 'Nettingale_Benchmark_User_Generator' ) ) {
			return 0;
		}

		return Nettingale_Benchmark_User_Generator::delete_benchmark_users();
	}

	/**
	 * Delete all benchmark comments
	 *
	 * @return int Number of comments deleted.
	 */
	private static function delete_comments() {
		if ( ! class_exists( 'Nettingale_Benchmark_Comment_Generator' ) ) {
			return 0;
		}

		return Nettingale_Benchmark_Comment_Generator::delete_benchmark_comments();
	}

	/**
	 * Delete all benchmark taxonomies
	 *
	 * @return array Number of categories and tags deleted.
	 */
	private static function delete_taxonomies() {
		if ( ! class_exists( 'Nettingale_Benchmark_Taxonomy_Generator' ) ) {
			return array( 'categories' => 0, 'tags' => 0 );
		}

		return Nettingale_Benchmark_Taxonomy_Generator::delete_all_benchmark_terms();
	}

	/**
	 * Clean up filesystem (remove uploaded images)
	 *
	 * @return int Number of files deleted.
	 */
	private static function cleanup_filesystem() {
		$upload_dir = wp_upload_dir();
		$benchmark_dir = $upload_dir['basedir'] . '/nettingale-benchmark';

		if ( ! is_dir( $benchmark_dir ) ) {
			return 0;
		}

		$files_deleted = 0;

		// Count files before deletion
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $benchmark_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $files as $file ) {
			if ( ! $file->isDir() ) {
				$files_deleted++;
			}
		}

		// Use WordPress filesystem API for deletion
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		// Recursively delete the directory and all its contents
		if ( $wp_filesystem ) {
			$wp_filesystem->delete( $benchmark_dir, true );
		}

		return $files_deleted;
	}

	/**
	 * Count benchmark posts
	 *
	 * @return int Number of posts.
	 */
	private static function count_posts() {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
			WHERE p.post_type = 'post'
			AND EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} pm
				WHERE pm.post_id = p.ID
				AND pm.meta_key = '_nettingale_benchmark_item'
			)"
		);
	}

	/**
	 * Count benchmark pages
	 *
	 * @return int Number of pages.
	 */
	private static function count_pages() {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
			WHERE p.post_type = 'page'
			AND EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} pm
				WHERE pm.post_id = p.ID
				AND pm.meta_key = '_nettingale_benchmark_item'
			)"
		);
	}

	/**
	 * Count benchmark attachments
	 *
	 * @return int Number of attachments.
	 */
	private static function count_attachments() {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
			WHERE p.post_type = 'attachment'
			AND EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} pm
				WHERE pm.post_id = p.ID
				AND pm.meta_key = '_nettingale_benchmark_item'
			)"
		);
	}

	/**
	 * Count benchmark users
	 *
	 * @return int Number of users.
	 */
	private static function count_users() {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->users} u
			WHERE EXISTS (
				SELECT 1 FROM {$wpdb->usermeta} um
				WHERE um.user_id = u.ID
				AND um.meta_key = '_nettingale_benchmark_user'
			)"
		);
	}

	/**
	 * Count benchmark comments
	 *
	 * @return int Number of comments.
	 */
	private static function count_comments() {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->comments} c
			WHERE EXISTS (
				SELECT 1 FROM {$wpdb->commentmeta} cm
				WHERE cm.comment_id = c.comment_ID
				AND cm.meta_key = '_nettingale_benchmark_comment'
			)"
		);
	}

	/**
	 * Count benchmark categories
	 *
	 * @return int Number of categories.
	 */
	private static function count_categories() {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			WHERE tt.taxonomy = 'category'
			AND EXISTS (
				SELECT 1 FROM {$wpdb->termmeta} tm
				WHERE tm.term_id = t.term_id
				AND tm.meta_key = '_nettingale_benchmark_term'
			)"
		);
	}

	/**
	 * Count benchmark tags
	 *
	 * @return int Number of tags.
	 */
	private static function count_tags() {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			WHERE tt.taxonomy = 'post_tag'
			AND EXISTS (
				SELECT 1 FROM {$wpdb->termmeta} tm
				WHERE tm.term_id = t.term_id
				AND tm.meta_key = '_nettingale_benchmark_term'
			)"
		);
	}

	/**
	 * Estimate filesystem size of benchmark images (actual disk space used)
	 *
	 * @return float Size in MB.
	 */
	private static function estimate_filesystem_size() {
		global $wpdb;

		// Get all benchmark attachment IDs (across all runs)
		$attachment_ids = $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
			WHERE meta_key = '_nettingale_benchmark_item'
			AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment')"
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

		return round( $total_size / 1024 / 1024, 2 );
	}

	/**
	 * Get detailed statistics about benchmark data
	 *
	 * @return array Statistics.
	 */
	public static function get_statistics() {
		return array(
			'posts'         => self::count_posts(),
			'pages'         => self::count_pages(),
			'attachments'   => self::count_attachments(),
			'users'         => self::count_users(),
			'comments'      => self::count_comments(),
			'categories'    => self::count_categories(),
			'tags'          => self::count_tags(),
			'filesystem_mb' => self::estimate_filesystem_size(),
		);
	}
}

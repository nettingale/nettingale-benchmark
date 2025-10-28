<?php
/**
 * Settings Page Template
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap nettingale-benchmark-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p class="description">
		<?php echo esc_html('Configure plugin settings and cleanup benchmark data.'); ?>
	</p>

	<!-- Privacy & Transparency Notice -->
	<div class="notice notice-info" style="margin: 15px 0; padding: 12px;">
		<p style="margin: 0;">
			<strong><?php echo esc_html('Privacy & Transparency:'); ?></strong>
			<?php echo esc_html( 'This plugin operates 100% locally on your server. No data is collected, transmitted, or stored externally.' ); ?>
		</p>
	</div>

	<!-- PHP Environment Status Section -->
	<div class="nettingale-benchmark-card" style="border-left: 4px solid #2271b1;">
		<h2><?php echo esc_html('PHP Environment Status'); ?></h2>
		<p class="description">
			<?php echo esc_html('Check your server\'s PHP configuration to ensure optimal benchmark performance.'); ?>
		</p>

		<div id="php-status-container" style="margin-top: 20px;">
			<p><?php echo esc_html('Loading PHP environment status...'); ?></p>
		</div>

		<button type="button" id="refresh-php-status" class="button" style="margin-top: 15px;">
			<?php echo esc_html('Refresh Status'); ?>
		</button>
	</div>

	<!-- Cleanup Section -->
	<div class="nettingale-benchmark-card">
		<h2><?php echo esc_html('Cleanup Benchmark Data'); ?></h2>
		<p class="description">
			<?php echo esc_html('Remove all benchmark-generated content from your WordPress site. This action is permanent and cannot be undone.'); ?>
		</p>

		<!-- Current Statistics -->
		<div id="cleanup-stats" class="nettingale-benchmark-cleanup-stats" style="margin: 20px 0;">
			<h3><?php echo esc_html('Current Benchmark Data'); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php echo esc_html('Type'); ?></th>
						<th><?php echo esc_html('Count'); ?></th>
					</tr>
				</thead>
				<tbody id="cleanup-stats-body">
					<tr><td colspan="2"><?php echo esc_html('Loading...'); ?></td></tr>
				</tbody>
			</table>
		</div>

		<!-- Action Buttons -->
		<div class="nettingale-benchmark-cleanup-actions">
			<button type="button" id="refresh-cleanup-stats" class="button">
				<?php echo esc_html('Refresh Statistics'); ?>
			</button>
			<button type="button" id="cleanup-benchmark-data" class="button button-primary button-large" style="margin-left: 10px;">
				<?php echo esc_html('Delete All Benchmark Data'); ?>
			</button>
		</div>

		<!-- Cleanup Progress -->
		<div id="cleanup-progress-container" class="nettingale-benchmark-progress-container" style="margin-top: 20px; display: none;">
			<h3><?php echo esc_html('Cleanup Progress'); ?></h3>
			<div class="nettingale-benchmark-progress">
				<div class="nettingale-benchmark-progress-bar">
					<div id="cleanup-progress-bar" class="nettingale-benchmark-progress-bar-fill" style="width: 0%;"></div>
				</div>
				<div class="nettingale-benchmark-progress-info">
					<span id="cleanup-progress-percent" class="nettingale-benchmark-progress-percent">0%</span>
					<span id="cleanup-progress-message" class="nettingale-benchmark-progress-message"><?php echo esc_html('Starting cleanup...'); ?></span>
				</div>
			</div>
			<p class="description">
				<?php echo esc_html('Please do not close this window until cleanup is complete. The cleanup will continue in the background if you navigate away.'); ?>
			</p>
		</div>

		<!-- Cleanup Results -->
		<div id="cleanup-results" class="nettingale-benchmark-cleanup-results" style="margin-top: 20px; display: none;">
			<h3><?php echo esc_html('Cleanup Results'); ?></h3>
			<div id="cleanup-results-content"></div>
		</div>
	</div>

	<!-- Plugin Settings -->
	<div class="nettingale-benchmark-card" style="margin-top: 20px;">
		<h2><?php echo esc_html('Plugin Settings'); ?></h2>
		<p class="description">
			<?php echo esc_html('Configure plugin behavior and performance options.'); ?>
		</p>

		<?php
		// Get current settings
		$cleanup_on_deactivate = get_option( 'nettingale_benchmark_cleanup_on_deactivate', '0' );
		?>

		<form id="nettingale-benchmark-settings-form">
			<?php wp_nonce_field( 'nettingale_benchmark_settings', 'nettingale_benchmark_settings_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="cleanup_on_deactivate"><?php echo esc_html('Cleanup on Deactivation/Uninstall'); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox"
								   id="cleanup_on_deactivate"
								   name="cleanup_on_deactivate"
								   value="1"
								   <?php checked( $cleanup_on_deactivate, '1' ); ?> />
							<?php echo esc_html('Automatically delete all benchmark data when plugin is deactivated or uninstalled'); ?>
						</label>
						<p class="description">
							<?php echo esc_html( 'WARNING: When enabled, all benchmark-generated content (posts, pages, comments, users, images, etc.) will be permanently deleted when you deactivate or uninstall this plugin. Plugin settings and run history are always removed on uninstall.' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php echo esc_html('Save Settings'); ?>
				</button>
			</p>
		</form>

		<div id="settings-save-message" style="display: none; margin-top: 10px;"></div>
	</div>
</div>

<!-- Cleanup Confirmation Modal -->
<div id="nettingale-cleanup-modal" class="nettingale-benchmark-modal">
	<div class="nettingale-benchmark-modal-content nettingale-cleanup-modal-content">
		<span class="nettingale-benchmark-modal-close">&times;</span>

		<h2 style="color: #dc3232; margin-bottom: 20px;">
			<?php echo esc_html('Delete All Benchmark Data'); ?>
		</h2>

		<div class="nettingale-cleanup-warning" style="background-color: #fef5f5; border-left: 4px solid #dc3232; padding: 15px; margin-bottom: 20px;">
			<p style="margin: 0; font-weight: 600; color: #c62828;">
				<?php echo esc_html('WARNING: This will permanently delete ALL benchmark data!'); ?>
			</p>
		</div>

		<p style="margin-bottom: 15px;">
			<?php echo esc_html('The following items will be permanently deleted:'); ?>
		</p>

		<div style="background-color: #f9f9f9; border: 1px solid #e0e0e0; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
			<ul style="margin: 0; padding-left: 25px; line-height: 1.8;">
				<li><?php echo esc_html('All benchmark posts and pages'); ?></li>
				<li><?php echo esc_html('All benchmark images (from filesystem and media library)'); ?></li>
				<li><?php echo esc_html('All benchmark users'); ?></li>
				<li><?php echo esc_html('All benchmark comments'); ?></li>
				<li><?php echo esc_html('All benchmark categories and tags'); ?></li>
				<li><?php echo esc_html('All benchmark run history'); ?></li>
			</ul>
		</div>

		<p style="margin-bottom: 20px; font-weight: 600;">
			<?php echo esc_html('This action CANNOT be undone!'); ?>
		</p>

		<div style="margin-bottom: 25px;">
			<label for="nettingale-cleanup-confirm-input" style="display: block; margin-bottom: 10px; font-weight: 600;">
				<?php echo esc_html('Type "DELETE" to confirm:'); ?>
			</label>
			<input type="text"
				   id="nettingale-cleanup-confirm-input"
				   class="regular-text"
				   placeholder="<?php echo esc_attr('DELETE'); ?>"
				   style="width: 100%; max-width: 400px; padding: 8px; font-size: 14px; border: 2px solid #c3c4c7; border-radius: 4px;" />
		</div>

		<div style="border-top: 1px solid #e0e0e0; padding-top: 20px; text-align: right;">
			<button type="button" id="nettingale-cleanup-cancel" class="button button-large" style="margin-right: 10px;">
				<?php echo esc_html('Cancel'); ?>
			</button>
			<button type="button" id="nettingale-cleanup-confirm" class="button button-primary button-large" disabled style="background-color: #dc3232; border-color: #dc3232;">
				<?php echo esc_html('Confirm Delete'); ?>
			</button>
		</div>
	</div>
</div>

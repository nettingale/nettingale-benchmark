<?php
/**
 * Seed Data Page Template
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
		<?php echo esc_html('Generate reproducible benchmark datasets for WordPress performance testing.'); ?>
	</p>

	<!-- Privacy & Transparency Notice -->
	<div class="notice notice-info" style="margin: 15px 0; padding: 12px;">
		<p style="margin: 0;">
			<strong><?php echo esc_html('Privacy & Transparency:'); ?></strong>
			<?php echo esc_html( 'This plugin operates 100% locally on your server. No data is collected, transmitted, or stored externally.' ); ?>
		</p>
	</div>

	<!-- Stuck Lock Warning (conditionally displayed by JavaScript) -->
	<div id="stuck-lock-warning" class="notice notice-warning" style="display: none; margin: 15px 0; padding: 12px; border-left: 4px solid #f0b849;">
		<p style="margin: 0 0 10px 0;">
			<strong><?php echo esc_html('System Lock Detected'); ?></strong>
		</p>
		<p style="margin: 0 0 10px 0;">
			<?php echo esc_html('A system lock exists but no benchmark is currently running. This may be caused by a previous benchmark that did not complete properly.'); ?>
		</p>
		<button type="button" id="clear-stuck-lock-button" class="button button-secondary">
			<?php echo esc_html('Clear Lock & Reset System'); ?>
		</button>
		<span id="clear-stuck-lock-result" style="margin-left: 10px;"></span>
	</div>

	<div class="nettingale-benchmark-card">
		<h2><?php echo esc_html('Benchmark Tier Selection'); ?></h2>

		<form id="nettingale-benchmark-form" method="post">
			<?php
			wp_nonce_field( 'nettingale_benchmark_action', 'nettingale_benchmark_nonce' );

			// Get all tier configurations
			$tiers = Nettingale_Benchmark_Config::get_all_tiers();
			?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="benchmark-tier"><?php echo esc_html('Select Tier'); ?></label>
					</th>
					<td>
						<select id="benchmark-tier" name="benchmark_tier" class="regular-text">
							<?php foreach ( $tiers as $tier_key => $tier_config ) : ?>
								<option value="<?php echo esc_attr( $tier_key ); ?>">
									<?php
									printf(
										/* translators: 1: Tier name, 2: Post count, 3: Estimated time, 4: Estimated size */
										esc_html( '%1$s - %2$s posts (%3$s, ~%4$sMB)' ),
										esc_html( ucfirst( $tier_key ) ),
										esc_html( number_format( $tier_config['posts'] ) ),
										esc_html( $tier_config['estimated_time'] ),
										esc_html( number_format( $tier_config['estimated_size_mb'] ) )
									);
									?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php echo esc_html('Choose the benchmark tier based on your testing needs.'); ?>
						</p>
					</td>
				</tr>
			</table>

			<!-- Tier Information Display -->
			<div class="nettingale-benchmark-tier-info">
				<h3><?php echo esc_html('Tier Details'); ?></h3>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php echo esc_html('Content Type'); ?></th>
							<?php foreach ( array( 'small', 'medium', 'large' ) as $tier_key ) : ?>
								<th><?php echo esc_html( ucfirst( $tier_key ) ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php
						$content_types = array(
							'posts'       => 'Posts',
							'pages'       => 'Pages',
							'attachments' => 'Attachments',
							'users'       => 'Users',
							'comments'    => 'Comments',
							'categories'  => 'Categories',
							'tags'        => 'Tags',
						);

						foreach ( $content_types as $type => $label ) :
							?>
							<tr>
								<td><?php echo esc_html( $label ); ?></td>
								<?php foreach ( array( 'small', 'medium', 'large' ) as $tier_key ) : ?>
									<?php $tier_config = Nettingale_Benchmark_Config::get_tier( $tier_key ); ?>
									<td>
										<?php
										if ( 'attachments' === $type ) {
											// Calculate attachments: (posts + pages) × 3 images each
											$attachments = ( $tier_config['posts'] + $tier_config['pages'] ) * 3;
											echo esc_html( number_format( $attachments ) );
										} else {
											echo esc_html( number_format( $tier_config[ $type ] ) );
										}
										?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>

						<!-- Estimated Time Row -->
						<tr>
							<td><?php echo esc_html('Estimated Time'); ?></td>
							<?php foreach ( array( 'small', 'medium', 'large' ) as $tier_key ) : ?>
								<?php $tier_config = Nettingale_Benchmark_Config::get_tier( $tier_key ); ?>
								<td><?php echo esc_html( $tier_config['estimated_time'] ); ?></td>
							<?php endforeach; ?>
						</tr>

						<!-- Estimated Size Row -->
						<tr>
							<td><?php echo esc_html('Estimated Size'); ?></td>
							<?php foreach ( array( 'small', 'medium', 'large' ) as $tier_key ) : ?>
								<?php $tier_config = Nettingale_Benchmark_Config::get_tier( $tier_key ); ?>
								<td>
									<?php
									$size = $tier_config['estimated_size_mb'];
									if ( $size >= 1000 ) {
										echo '~' . esc_html( number_format( $size / 1000, 1 ) ) . 'GB';
									} else {
										echo '~' . esc_html( $size ) . 'MB';
									}
									?>
								</td>
							<?php endforeach; ?>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Action Buttons -->
			<div class="nettingale-benchmark-actions">
				<button type="button" id="start-benchmark" class="button button-primary button-large">
					<?php echo esc_html('Start Benchmark'); ?>
				</button>
				<button type="button" id="stop-benchmark" class="button button-secondary" style="display: none;">
					<?php echo esc_html('Stop Benchmark'); ?>
				</button>
				<p class="description">
					<?php echo esc_html('Select a tier and click Start to begin benchmark generation.'); ?>
				</p>
			</div>

			<!-- Progress Display -->
			<div id="progress-container" style="display: none;">
				<h3><?php echo esc_html('Progress'); ?></h3>
				<div class="nettingale-benchmark-progress-bar">
					<div class="nettingale-benchmark-progress-fill" style="width: 0%;">
						<span class="nettingale-benchmark-progress-text">0%</span>
					</div>
				</div>
				<p id="current-phase"><?php echo esc_html('Ready'); ?></p>
			</div>
		</form>
	</div>
</div>

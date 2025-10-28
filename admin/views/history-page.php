<?php
/**
 * History Page Template
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
		<?php echo esc_html('View historical benchmark runs and their metrics.'); ?>
	</p>

	<!-- Privacy & Transparency Notice -->
	<div class="notice notice-info" style="margin: 15px 0; padding: 12px;">
		<p style="margin: 0;">
			<strong><?php echo esc_html('Privacy & Transparency:'); ?></strong>
			<?php echo esc_html( 'This plugin operates 100% locally on your server. No data is collected, transmitted, or stored externally.' ); ?>
		</p>
	</div>

	<div class="nettingale-benchmark-card">
		<h2><?php echo esc_html('Benchmark Run History'); ?></h2>

		<div id="history-content">
			<?php
			// Get all runs
			$all_runs = Nettingale_Benchmark_Metrics_Collector::get_all_runs();

			if ( empty( $all_runs ) ) :
				?>
				<p><?php echo esc_html('No benchmark runs found. Start a new benchmark to see results here.'); ?></p>
				<?php
			else :
				?>
				<table class="widefat fixed striped">
					<thead>
						<tr>
							<th><?php echo esc_html('ID'); ?></th>
							<th><?php echo esc_html('Tier'); ?></th>
							<th><?php echo esc_html('Status'); ?></th>
							<th><?php echo esc_html('Started'); ?></th>
							<th><?php echo esc_html('Duration'); ?></th>
							<th><?php echo esc_html('Storage'); ?></th>
							<th><?php echo esc_html('Actions'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $all_runs as $run ) : ?>
							<?php
							$metrics = $run['metrics'];
							$status_class = 'running' === $run['status'] ? 'running' : 'completed';
							$has_new_metrics = ! empty( $metrics['counts'] ) && ! empty( $metrics['sizes'] ) && ! empty( $metrics['timing'] );
							?>
							<tr>
								<td><?php echo esc_html( $run['id'] ); ?></td>
								<td><?php echo esc_html( ucfirst( $run['tier'] ) ); ?></td>
								<td><span class="nettingale-benchmark-status nettingale-benchmark-status-<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( ucfirst( $run['status'] ) ); ?></span></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $run['started_at'] ) ) ); ?></td>
								<td>
									<?php
									if ( $has_new_metrics && ! empty( $metrics['timing']['formatted'] ) ) {
										echo esc_html( $metrics['timing']['formatted'] );
									} else {
										echo '&mdash;';
									}
									?>
								</td>
								<td>
									<?php
									if ( $has_new_metrics && isset( $metrics['sizes']['filesystem_mb'] ) ) {
										$fs_mb = $metrics['sizes']['filesystem_mb'];
										if ( $fs_mb >= 1000 ) {
											echo esc_html( number_format( $fs_mb / 1000, 2 ) ) . ' GB';
										} else {
											echo esc_html( number_format( $fs_mb, 2 ) ) . ' MB';
										}
									} else {
										echo '&mdash;';
									}
									?>
								</td>
								<td>
									<?php if ( 'completed' === $run['status'] && $has_new_metrics ) : ?>
										<button type="button" class="button button-small view-metrics" data-run-id="<?php echo esc_attr( $run['id'] ); ?>">
											<?php echo esc_html('View Details'); ?>
										</button>
										<button type="button" class="button button-small export-json" data-run-id="<?php echo esc_attr( $run['id'] ); ?>">
											<?php echo esc_html('Export JSON'); ?>
										</button>
										<button type="button" class="button button-small export-csv" data-run-id="<?php echo esc_attr( $run['id'] ); ?>">
											<?php echo esc_html('Export CSV'); ?>
										</button>
									<?php else : ?>
										&mdash;
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Metrics Detail Modal (hidden by default) -->
				<div id="metrics-modal" class="nettingale-benchmark-modal" style="display: none;">
					<div class="nettingale-benchmark-modal-content">
						<span class="nettingale-benchmark-modal-close">&times;</span>
						<h2><?php echo esc_html('Benchmark Run Metrics'); ?></h2>
						<div id="metrics-detail-content"></div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

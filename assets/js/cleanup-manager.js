/**
 * Cleanup Manager JavaScript
 *
 * Handles cleanup UI interactions
 *
 * @package Nettingale_Benchmark
 */

(function($) {
	'use strict';

	var CleanupManager = {
		/**
		 * Initialize
		 */
		init: function() {
			this.loadStats();
			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			var self = this;

			$('#refresh-cleanup-stats').on('click', $.proxy(this.loadStats, this));
			$('#cleanup-benchmark-data').on('click', $.proxy(this.showCleanupModal, this));

			// Modal close events
			$('.nettingale-benchmark-modal-close').on('click', $.proxy(this.hideCleanupModal, this));
			$('#nettingale-cleanup-cancel').on('click', $.proxy(this.hideCleanupModal, this));

			// Click outside modal to close
			$('#nettingale-cleanup-modal').on('click', function(e) {
				if (e.target.id === 'nettingale-cleanup-modal') {
					self.hideCleanupModal();
				}
			});

			// Enable confirm button when "DELETE" is typed
			$('#nettingale-cleanup-confirm-input').on('input', function() {
				var value = $(this).val().toUpperCase();
				if (value === 'DELETE') {
					$('#nettingale-cleanup-confirm').prop('disabled', false);
				} else {
					$('#nettingale-cleanup-confirm').prop('disabled', true);
				}
			});

			// Handle confirm button click
			$('#nettingale-cleanup-confirm').on('click', $.proxy(this.confirmCleanup, this));

			// Close modal on Escape key
			$(document).on('keydown.cleanup-modal', function(e) {
				if (e.key === 'Escape' && $('#nettingale-cleanup-modal').is(':visible')) {
					self.hideCleanupModal();
				}
			});
		},

		/**
		 * Load cleanup statistics
		 */
		loadStats: function() {
			var self = this;

			$('#cleanup-stats-body').html('<tr><td colspan="2">Loading...</td></tr>');

			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				data: {
					action: 'nettingale_benchmark_get_cleanup_stats',
					nonce: nettingaleBenchmark.nonce
				},
				success: function(response) {
					if (response.success) {
						self.displayStats(response.data);
					} else {
						$('#cleanup-stats-body').html('<tr><td colspan="2" style="text-align: center; padding: 20px; color: #dc3232;">Failed to load statistics.</td></tr>');
					}
				},
				error: function(xhr, status, error) {
					$('#cleanup-stats-body').html('<tr><td colspan="2" style="text-align: center; padding: 20px; color: #dc3232;">Error loading statistics. Please refresh the page.</td></tr>');
				}
			});
		},

		/**
		 * Display statistics
		 */
		displayStats: function(stats) {
			var html = '';
			var total = 0;

			total = (stats.posts || 0) + (stats.pages || 0) + (stats.attachments || 0) +
					(stats.users || 0) + (stats.comments || 0) + (stats.categories || 0) + (stats.tags || 0);


			if (total === 0) {
				// Show message when no data
				html += '<tr><td colspan="2" style="text-align: center; padding: 20px; color: #666;">No benchmark data found. Start a benchmark to see data here.</td></tr>';
			} else {
				html += '<tr><td>Posts</td><td>' + (stats.posts || 0) + '</td></tr>';
				html += '<tr><td>Pages</td><td>' + (stats.pages || 0) + '</td></tr>';
				html += '<tr><td>Attachments</td><td>' + (stats.attachments || 0) + '</td></tr>';
				html += '<tr><td>Users</td><td>' + (stats.users || 0) + '</td></tr>';
				html += '<tr><td>Comments</td><td>' + (stats.comments || 0) + '</td></tr>';
				html += '<tr><td>Categories</td><td>' + (stats.categories || 0) + '</td></tr>';
				html += '<tr><td>Tags</td><td>' + (stats.tags || 0) + '</td></tr>';
				html += '<tr><td>Filesystem</td><td>' + (stats.filesystem_mb || 0) + ' MB</td></tr>';
				html += '<tr style="font-weight: bold; border-top: 2px solid #ddd;"><td>Total Items</td><td>' + total + '</td></tr>';
			}

			$('#cleanup-stats-body').html(html);

			// Show/hide buttons based on whether there's data
			if (total === 0) {
				$('#cleanup-benchmark-data').prop('disabled', true);
			} else {
				$('#cleanup-benchmark-data').prop('disabled', false);
			}
		},

		/**
		 * Show cleanup modal
		 */
		showCleanupModal: function() {
			// Reset modal state
			$('#nettingale-cleanup-confirm-input').val('');
			$('#nettingale-cleanup-confirm').prop('disabled', true);

			// Show modal with animation
			$('#nettingale-cleanup-modal').css('display', 'block').addClass('show');

			// Focus on input field
			setTimeout(function() {
				$('#nettingale-cleanup-confirm-input').focus();
			}, 300);
		},

		/**
		 * Hide cleanup modal
		 */
		hideCleanupModal: function() {
			$('#nettingale-cleanup-modal').removeClass('show');
			setTimeout(function() {
				$('#nettingale-cleanup-modal').css('display', 'none');
			}, 200);
		},

		/**
		 * Confirm cleanup
		 */
		confirmCleanup: function() {
			// Hide modal
			this.hideCleanupModal();

			// Execute cleanup
			this.executeCleanup();
		},

		/**
		 * Execute cleanup
		 */
		executeCleanup: function() {
			var self = this;

			// Disable buttons during cleanup
			$('#cleanup-benchmark-data, #refresh-cleanup-stats').prop('disabled', true);

			$('#cleanup-results-content').html('<div class="notice notice-info"><p>Cleaning up benchmark data... This may take a moment.</p></div>');
			$('#cleanup-results').show();

			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				data: {
					action: 'nettingale_benchmark_cleanup_data',
					nonce: nettingaleBenchmark.nonce,
				},
				success: function(response) {
					if (response.success && !false) {
						self.displayResults(response.data.results);
						// Reload stats after a short delay to ensure cleanup is complete
					setTimeout(function() {
						self.loadStats();
					}, 500);
					} else {
						self.showError('Failed to cleanup data.');
					}
					// Re-enable buttons
					$('#cleanup-benchmark-data, #refresh-cleanup-stats').prop('disabled', false);
				},
				error: function() {
					self.showError('Error during cleanup.');
					$('#cleanup-benchmark-data, #refresh-cleanup-stats').prop('disabled', false);
				}
			});
		},

		/**
		 * Display cleanup results
		 */
		displayResults: function(results) {
			var html = '<div class="notice notice-success"><p><strong>Cleanup Complete!</strong></p>';
			html += '<p>The following items were deleted:</p><ul>';
			html += '<li>Posts/Pages/Attachments: ' + (results.deleted.posts || 0) + '</li>';
			html += '<li>Users: ' + (results.deleted.users || 0) + '</li>';
			html += '<li>Comments: ' + (results.deleted.comments || 0) + '</li>';
			html += '<li>Categories: ' + (results.deleted.categories || 0) + '</li>';
			html += '<li>Tags: ' + (results.deleted.tags || 0) + '</li>';
			html += '<li>Files: ' + (results.deleted.files || 0) + '</li>';
		html += '<li>Run History: ' + (results.deleted.runs || 0) + '</li>';
			html += '</ul></div>';

			$('#cleanup-results-content').html(html);
			$('#cleanup-results').show();
		},

		/**
		 * Show error message
		 */
		showError: function(message) {
			var html = '<div class="notice notice-error"><p>' + this.escapeHtml(message) + '</p></div>';
			$('#cleanup-results-content').html(html);
			$('#cleanup-results').show();
		},

		/**
		 * Escape HTML
		 */
		escapeHtml: function(text) {
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, function(m) { return map[m]; });
		}
	};

	// Initialize when document is ready
	$(document).ready(function() {
		CleanupManager.init();
	});

})(jQuery);

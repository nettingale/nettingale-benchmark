/**
 * Batch Processor JavaScript
 *
 * Handles AJAX-based batch processing for benchmark generation
 *
 * @package Nettingale_Benchmark
 */

(function($) {
	'use strict';

	/**
	 * Batch Processor Object
	 */
	var NettingaleBenchmarkProcessor = {

		// Current run state
		runId: null,
		tier: null,
		isRunning: false,
		shouldStop: false,
		isProcessing: false, // Prevent multiple simultaneous AJAX calls
		reconnectAttempts: 0,
		maxReconnectAttempts: 5,
		statusPollInterval: null,
		lastProgress: 0,

		/**
		 * Initialize the processor
		 */
		init: function() {
			this.bindEvents();
			this.checkForStuckLock();
			this.checkForRunningBenchmark();
		},

		/**
		 * Bind UI events
		 */
		bindEvents: function() {
			var self = this;

			// Start button
			$('#start-benchmark').on('click', function(e) {
				e.preventDefault();
				self.start();
			});

			// Stop button
			$('#stop-benchmark').on('click', function(e) {
				e.preventDefault();
				self.stop();
			});

			// Tier selection - enable start button
			$('#benchmark-tier').on('change', function() {
				$('#start-benchmark').prop('disabled', false);
			});

			// Clear stuck lock button
			$('#clear-stuck-lock-button').on('click', function(e) {
				e.preventDefault();
				self.clearStuckLock();
			});

			// Handle page visibility changes (user switches tabs)
			$(document).on('visibilitychange', function() {
				if (!document.hidden && self.isRunning) {
					self.checkStatus();
				}
			});
		},

		/**
		 * Start benchmark processing
		 */
		start: function() {
			var self = this;

			// Get selected tier
			this.tier = $('#benchmark-tier').val();

			if (!this.tier) {
				this.showError('Please select a benchmark tier.');
				return;
			}


			// Disable start button
			$('#start-benchmark').prop('disabled', true);
			$('#benchmark-tier').prop('disabled', true);

			// Show progress container
			$('#progress-container').show();
			this.updateProgress(0, 'Starting benchmark...');

			// Send AJAX request to start
			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				timeout: 30000,
				data: {
					action: 'nettingale_benchmark_start',
					nonce: nettingaleBenchmark.nonce,
					tier: this.tier
				},
				success: function(response) {
					if (response.success) {
						self.runId = response.data.run_id;
						self.isRunning = true;
						self.shouldStop = false;
						self.reconnectAttempts = 0;

						// Show stop button
						$('#stop-benchmark').show();

						// Start status polling
						self.startStatusPolling();

						// Start batch-by-batch processing
						self.processNext();
					} else {
						self.showError(response.data.message || 'Failed to start benchmark.');
						self.reset();
					}
				},
				error: function(xhr, status, error) {
					self.showError('Network error. Please try again.');
					self.reset();
				}
			});
		},

		/**
		 * Process next batch
		 */
		processNext: function() {
			var self = this;

			// Check if we should stop
			if (this.shouldStop) {
				this.reset();
				return;
			}

			if (!this.isRunning || !this.runId) {
				return;
			}

			// Prevent multiple simultaneous calls
			if (this.isProcessing) {
				return;
			}

			this.isProcessing = true;

			// Send AJAX request to process
			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				timeout: 60000, // 60 second timeout for batch processing
				data: {
					action: 'nettingale_benchmark_process',
					nonce: nettingaleBenchmark.nonce,
					run_id: this.runId
				},
				success: function(response) {
					self.isProcessing = false;
					self.reconnectAttempts = 0; // Reset on success


					if (response.success) {
						var data = response.data;

						// Update progress
						self.lastProgress = data.progress || self.lastProgress;
						self.updateProgress(data.progress, data.message);

						// Check if done
						if (data.done) {
							self.complete(data);
						} else {
							// Process next batch after a short delay
							setTimeout(function() {
								self.processNext();
							}, 100);
						}
					} else {
						self.showError(response.data.message || 'Processing failed.');
						self.reset();
					}
				},
				error: function(xhr, status, error) {
					self.isProcessing = false;

					// Retry logic
					if (self.reconnectAttempts < self.maxReconnectAttempts) {
						self.reconnectAttempts++;
						self.updateProgress(null, 'Connection issue, retrying... (attempt ' + self.reconnectAttempts + ')');

						setTimeout(function() {
							self.processNext();
						}, 2000 * self.reconnectAttempts); // Exponential backoff
					} else {
						self.showError('Network error during processing. Too many retries.');
						self.reset();
					}
				}
			});
		},

		/**
		 * Stop benchmark processing
		 */
		stop: function() {
			var self = this;

			if (!this.isRunning || !this.runId) {
				return;
			}


			this.shouldStop = true;
			this.stopStatusPolling();
			this.updateProgress(null, 'Stopping...');

			// Send AJAX request to stop
			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				timeout: 30000,
				data: {
					action: 'nettingale_benchmark_stop',
					nonce: nettingaleBenchmark.nonce,
					run_id: this.runId
				},
				success: function(response) {
					if (response.success) {
						self.showMessage('Benchmark stopped.', 'info');
					}
					self.reset();
				},
				error: function(xhr, status, error) {
					self.showError('Failed to stop benchmark.');
					self.reset();
				}
			});
		},

		/**
		 * Complete benchmark
		 */
		complete: function(data) {

			this.isRunning = false;
			this.stopStatusPolling();

			// Update to 100%
			this.updateProgress(100, 'Benchmark completed successfully!');

			// Show metrics if available
			if (data.metrics) {
				var metricsHtml = '<div class="nettingale-benchmark-metrics">';
				metricsHtml += '<h3>Results:</h3>';
				metricsHtml += '<ul>';
				metricsHtml += '<li>Posts: ' + (data.metrics.posts || 0) + '</li>';
				metricsHtml += '<li>Pages: ' + (data.metrics.pages || 0) + '</li>';
				metricsHtml += '<li>Comments: ' + (data.metrics.comments || 0) + '</li>';
				metricsHtml += '<li>Users: ' + (data.metrics.users || 0) + '</li>';
				metricsHtml += '<li>Categories: ' + (data.metrics.categories || 0) + '</li>';
				metricsHtml += '<li>Tags: ' + (data.metrics.tags || 0) + '</li>';
				metricsHtml += '</ul>';
				metricsHtml += '</div>';

				$('#progress-container').append(metricsHtml);
			}

			// Show success message
			this.showMessage('Benchmark completed successfully! Reloading to update history...', 'success');

			// Reload page after delay to show updated history
			setTimeout(function() {
				window.location.reload();
			}, 3000);
		},

		/**
		 * Update progress bar and message
		 */
		updateProgress: function(percent, message) {
			if (percent !== null && percent !== undefined) {
				$('.nettingale-benchmark-progress-fill').css('width', percent + '%');
				$('.nettingale-benchmark-progress-text').text(Math.round(percent) + '%');
			}

			if (message) {
				$('#current-phase').text(message);
			}
		},

		/**
		 * Show error message
		 */
		showError: function(message) {
			this.showMessage(message, 'error');
		},

		/**
		 * Show message
		 */
		showMessage: function(message, type) {
			var className = 'notice notice-' + type;
			var $notice = $('<div class="' + className + ' is-dismissible"><p>' + message + '</p></div>');

			$('.nettingale-benchmark-card').prepend($notice);

			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		},

		/**
		 * Check for running benchmark on page load
		 */
		checkForRunningBenchmark: function() {
			var self = this;


			// Show loading indicator
			$('#progress-container').show();
			this.updateProgress(0, 'Checking for running benchmark...');

			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				timeout: 15000,
				data: {
					action: 'nettingale_benchmark_get_running',
					nonce: nettingaleBenchmark.nonce
				},
				success: function(response) {

					if (response.success && response.data && response.data.id) {
						// Found a running benchmark, reconnect to it

						self.runId = response.data.id;
						self.tier = response.data.tier;
						self.isRunning = true;
						self.shouldStop = false;
						self.reconnectAttempts = 0;

						// Restore progress if available
						if (response.data.progress !== undefined) {
							self.lastProgress = response.data.progress;
							self.updateProgress(response.data.progress, response.data.message || 'Reconnecting to benchmark...');
						}

						// Show progress UI
						$('#stop-benchmark').show();
						$('#start-benchmark').prop('disabled', true);
						$('#benchmark-tier').prop('disabled', true).val(self.tier);

						// Start status polling
						self.startStatusPolling();

						// Resume batch processing
						setTimeout(function() {
							self.processNext();
						}, 500);
					} else {
						// No running benchmark found
						$('#progress-container').hide();
						self.updateProgress(0, 'Ready');
					}
				},
				error: function(xhr, status, error) {
					$('#progress-container').hide();
					self.updateProgress(0, 'Ready');

					// Don't show error message on initial check, just log it
					if (status !== 'timeout') {
					}
				}
			});
		},

		/**
		 * Check current status (for periodic polling)
		 */
		checkStatus: function() {
			var self = this;

			if (!this.isRunning || !this.runId) {
				return;
			}


			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				timeout: 15000,
				data: {
					action: 'nettingale_benchmark_status',
					nonce: nettingaleBenchmark.nonce,
					run_id: this.runId
				},
				success: function(response) {

					if (response.success && response.data) {
						// Update progress from status
						if (response.data.progress !== undefined) {
							self.lastProgress = response.data.progress;
							self.updateProgress(response.data.progress, response.data.message || 'Processing...');
						}

						// Check if completed
						if (response.data.status === 'completed') {
							self.complete(response.data);
						}
					}
				},
				error: function(xhr, status, error) {
					// Don't stop on status check errors, just log them
				}
			});
		},

		/**
		 * Start periodic status polling
		 */
		startStatusPolling: function() {
			var self = this;


			// Clear any existing interval
			this.stopStatusPolling();

			// Poll every 5 seconds
			this.statusPollInterval = setInterval(function() {
				self.checkStatus();
			}, 5000);
		},

		/**
		 * Stop periodic status polling
		 */
		stopStatusPolling: function() {
			if (this.statusPollInterval) {
				clearInterval(this.statusPollInterval);
				this.statusPollInterval = null;
			}
		},

		/**
		 * Reset UI to initial state
		 */
		reset: function() {

			this.runId = null;
			this.tier = null;
			this.isRunning = false;
			this.shouldStop = false;
			this.isProcessing = false;
			this.reconnectAttempts = 0;
			this.lastProgress = 0;

			this.stopStatusPolling();

			// Reset UI
			$('#start-benchmark').prop('disabled', false);
			$('#benchmark-tier').prop('disabled', false);
			$('#stop-benchmark').hide();

			// Clear progress
			this.updateProgress(0, 'Ready');

			// Hide progress after delay
			setTimeout(function() {
				$('#progress-container').fadeOut();
			}, 3000);
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
			return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
		},

		/**
		 * Check for stuck lock on page load
		 */
		checkForStuckLock: function() {
			var self = this;

			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				timeout: 10000,
				data: {
					action: 'nettingale_benchmark_check_stuck_lock',
					nonce: nettingaleBenchmark.nonce
				},
				success: function(response) {
					if (response.success && response.data.stuck_lock) {
						// Show stuck lock warning
						$('#stuck-lock-warning').show();
					} else {
						// Hide stuck lock warning
						$('#stuck-lock-warning').hide();
					}
				},
				error: function(xhr, status, error) {
					// Silently fail - don't show error on page load
				}
			});
		},

		/**
		 * Clear stuck lock
		 */
		clearStuckLock: function() {
			var self = this;
			var $button = $('#clear-stuck-lock-button');
			var $result = $('#clear-stuck-lock-result');

			// Disable button
			$button.prop('disabled', true).text('Clearing...');
			$result.html('');

			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				timeout: 10000,
				data: {
					action: 'nettingale_benchmark_clear_locks',
					nonce: nettingaleBenchmark.nonce
				},
				success: function(response) {
					if (response.success) {
						$result.html('<span style="color: #00a32a; font-weight: bold;">✓ Lock cleared successfully!</span>');

						// Hide warning after 2 seconds
						setTimeout(function() {
							$('#stuck-lock-warning').fadeOut();
						}, 2000);
					} else {
						$result.html('<span style="color: #d63638;">Error: ' + self.escapeHtml(response.data || 'Failed to clear lock') + '</span>');
					}

					// Re-enable button
					$button.prop('disabled', false).text('Clear Lock & Reset System');
				},
				error: function(xhr, status, error) {
					$result.html('<span style="color: #d63638;">Error: Failed to communicate with server.</span>');
					$button.prop('disabled', false).text('Clear Lock & Reset System');
				}
			});
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		// Only initialize if on benchmark page
		if ($('#benchmark-tier').length) {
			NettingaleBenchmarkProcessor.init();
		}
	});

})(jQuery);

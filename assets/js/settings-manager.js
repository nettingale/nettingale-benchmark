/**
 * Settings Manager JavaScript
 *
 * Handles settings form submission
 *
 * @package Nettingale_Benchmark
 */

(function($) {
	'use strict';

	var SettingsManager = {
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.loadPhpStatus();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			$('#nettingale-benchmark-settings-form').on('submit', $.proxy(this.saveSettings, this));
			$('#refresh-php-status').on('click', $.proxy(this.loadPhpStatus, this));
		},

		/**
		 * Save settings
		 */
		saveSettings: function(e) {
			e.preventDefault();

			var self = this;
			var $form = $('#nettingale-benchmark-settings-form');
			var $button = $form.find('button[type="submit"]');
			var $message = $('#settings-save-message');

			// Get form data
			var data = {
				action: 'nettingale_benchmark_save_settings',
				nonce: $form.find('input[name="nettingale_benchmark_settings_nonce"]').val(),
				cleanup_on_deactivate: $('#cleanup_on_deactivate').is(':checked') ? '1' : '0'
			};

			// Disable button
			$button.prop('disabled', true).text('Saving...');
			$message.hide();

			// Send AJAX request
			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						self.showMessage('success', response.data.message);
					} else {
						self.showMessage('error', response.data || 'Failed to save settings.');
					}
				},
				error: function() {
					self.showMessage('error', 'Error saving settings. Please try again.');
				},
				complete: function() {
					$button.prop('disabled', false).text('Save Settings');
				}
			});
		},

		/**
		 * Show message
		 */
		showMessage: function(type, message) {
			var $message = $('#settings-save-message');
			var className = type === 'success' ? 'notice-success' : 'notice-error';
			
			$message
				.removeClass('notice-success notice-error')
				.addClass('notice ' + className)
				.html('<p>' + this.escapeHtml(message) + '</p>')
				.show();

			// Auto-hide after 5 seconds
			setTimeout(function() {
				$message.fadeOut();
			}, 5000);
		},

		/**
		 * Load PHP environment status
		 */
		loadPhpStatus: function() {
			var self = this;
			var $container = $('#php-status-container');
			var $button = $('#refresh-php-status');

			// Show loading state
			$container.html('<p>Loading PHP environment status...</p>');
			$button.prop('disabled', true);

			// Send AJAX request
			$.ajax({
				url: nettingaleBenchmark.ajaxUrl,
				type: 'POST',
				data: {
					action: 'nettingale_benchmark_get_php_status',
					nonce: nettingaleBenchmark.nonce
				},
				success: function(response) {
					if (response.success) {
						self.displayPhpStatus(response.data);
					} else {
						$container.html('<p style="color: #d63638;">Error loading PHP status: ' +
							self.escapeHtml(response.data || 'Unknown error') + '</p>');
					}
					$button.prop('disabled', false);
				},
				error: function() {
					$container.html('<p style="color: #d63638;">Error: Failed to communicate with server.</p>');
					$button.prop('disabled', false);
				}
			});
		},

		/**
		 * Display PHP environment status
		 */
		displayPhpStatus: function(data) {
			var html = '<table class="widefat" style="margin-top: 10px;">';
			html += '<thead><tr>';
			html += '<th style="width: 30%;">Setting</th>';
			html += '<th style="width: 30%;">Current Value</th>';
			html += '<th style="width: 30%;">Recommended</th>';
			html += '<th style="width: 10%; text-align: center;">Status</th>';
			html += '</tr></thead>';
			html += '<tbody>';

			// Memory Limit
			html += this.renderStatusRow(
				'PHP Memory Limit',
				data.memory_limit.current,
				data.memory_limit.recommended,
				data.memory_limit.status
			);

			// Max Execution Time
			html += this.renderStatusRow(
				'Max Execution Time',
				data.max_execution_time.current,
				data.max_execution_time.recommended,
				data.max_execution_time.status
			);

			// Post Max Size
			html += this.renderStatusRow(
				'Post Max Size',
				data.post_max_size.current,
				data.post_max_size.recommended,
				data.post_max_size.status
			);

			// Upload Max Filesize
			html += this.renderStatusRow(
				'Upload Max Filesize',
				data.upload_max_filesize.current,
				data.upload_max_filesize.recommended,
				data.upload_max_filesize.status
			);

			// GD Library (Critical)
			html += this.renderStatusRow(
				'GD Library',
				data.gd_library.current,
				data.gd_library.recommended,
				data.gd_library.status
			);

			html += '</tbody></table>';

			$('#php-status-container').html(html);
		},

		/**
		 * Render a status table row
		 */
		renderStatusRow: function(label, current, recommended, status) {
			var statusClass = 'status-' + status;
			var statusText = '';

			switch(status) {
				case 'ok':
					statusText = '✓ OK';
					break;
				case 'warning':
					statusText = '⚠ Warning';
					break;
				case 'critical':
					statusText = '✕ Critical';
					break;
				case 'info':
					statusText = 'ℹ Info';
					break;
			}

			var html = '<tr class="' + statusClass + '">';
			html += '<td><strong>' + this.escapeHtml(label) + '</strong></td>';
			html += '<td>' + this.escapeHtml(current) + '</td>';
			html += '<td>' + this.escapeHtml(recommended) + '</td>';
			html += '<td style="text-align: center;"><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>';
			html += '</tr>';

			return html;
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
		SettingsManager.init();
	});

})(jQuery);

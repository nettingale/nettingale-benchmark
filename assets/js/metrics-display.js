/**
 * Nettingale Benchmark - Metrics Display
 *
 * @package Nettingale_Benchmark
 */

(function($) {
    'use strict';

    /**
     * Initialize metrics display functionality
     */
    function init() {
        // Unbind first to prevent duplicate handlers
        $(document).off('click', '.view-metrics');
        $(document).off('click', '.export-json');
        $(document).off('click', '.export-csv');
        $(document).off('click', '.nettingale-benchmark-modal-close');

        // View Details button (using event delegation)
        $(document).on('click', '.view-metrics', function() {
            var runId = $(this).data('run-id');
            viewMetrics(runId);
        });

        // Export JSON button (using event delegation)
        $(document).on('click', '.export-json', function() {
            var runId = $(this).data('run-id');
            exportJSON(runId);
        });

        // Export CSV button (using event delegation)
        $(document).on('click', '.export-csv', function() {
            var runId = $(this).data('run-id');
            exportCSV(runId);
        });

        // Close modal (using event delegation)
        $(document).on('click', '.nettingale-benchmark-modal-close', function() {
            $('#metrics-modal').hide();
        });

        // Close modal on outside click
        $(window).off('click.modal');
        $(window).on('click.modal', function(event) {
            if ($(event.target).hasClass('nettingale-benchmark-modal')) {
                $('#metrics-modal').hide();
            }
        });
    }

    /**
     * View metrics details in modal
     */
    function viewMetrics(runId) {
        $.ajax({
            url: nettingaleBenchmark.ajaxUrl,
            type: 'POST',
            data: {
                action: 'nettingale_benchmark_get_metrics',
                nonce: nettingaleBenchmark.nonce,
                run_id: runId
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayMetricsModal(response.data);
                } else {
                    alert('Failed to load metrics: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to load metrics. Please try again.');
            }
        });
    }

    /**
     * Display metrics in modal
     */
    function displayMetricsModal(data) {
        var html = '';
        var metrics = data.metrics;
        var run = data.run;

        // Overview section
        html += '<div class="nettingale-benchmark-metrics-section">';
        html += '<h3>Overview</h3>';
        html += '<div class="nettingale-benchmark-metrics-grid">';
        html += '<div class="nettingale-benchmark-metric-card">';
        html += '<div class="nettingale-benchmark-metric-label">Tier</div>';
        html += '<div class="nettingale-benchmark-metric-value">' + escapeHtml(run.tier) + '</div>';
        html += '</div>';
        html += '<div class="nettingale-benchmark-metric-card">';
        html += '<div class="nettingale-benchmark-metric-label">Duration</div>';
        html += '<div class="nettingale-benchmark-metric-value">' + escapeHtml(metrics.timing.formatted) + '</div>';
        html += '</div>';
        html += '<div class="nettingale-benchmark-metric-card">';
        html += '<div class="nettingale-benchmark-metric-label">Total Size</div>';
        html += '<div class="nettingale-benchmark-metric-value">' + formatSize(metrics.sizes.total_mb) + '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        // Content counts section
        html += '<div class="nettingale-benchmark-metrics-section">';
        html += '<h3>Content Generated</h3>';
        html += '<table class="widefat">';
        html += '<thead><tr><th>Type</th><th>Count</th></tr></thead>';
        html += '<tbody>';
        html += '<tr><td>Posts</td><td>' + formatNumber(metrics.counts.posts) + '</td></tr>';
        html += '<tr><td>Pages</td><td>' + formatNumber(metrics.counts.pages) + '</td></tr>';
        html += '<tr><td>Comments</td><td>' + formatNumber(metrics.counts.comments) + '</td></tr>';
        html += '<tr><td>Users</td><td>' + formatNumber(metrics.counts.users) + '</td></tr>';
        html += '<tr><td>Categories</td><td>' + formatNumber(metrics.counts.categories) + '</td></tr>';
        html += '<tr><td>Tags</td><td>' + formatNumber(metrics.counts.tags) + '</td></tr>';
        html += '<tr><td>Images</td><td>' + formatNumber(metrics.counts.images) + '</td></tr>';
        html += '<tr><th>Total Items</th><th>' + formatNumber(metrics.counts.total) + '</th></tr>';
        html += '</tbody>';
        html += '</table>';
        html += '</div>';

        // Storage sizes section
        html += '<div class="nettingale-benchmark-metrics-section">';
        html += '<h3>Storage Usage</h3>';
        html += '<table class="widefat">';
        html += '<thead><tr><th>Type</th><th>Size</th></tr></thead>';
        html += '<tbody>';
        html += '<tr><td>Storage (Images & Uploads)</td><td>' + formatSize(metrics.sizes.filesystem_mb) + '</td></tr>';
        html += '</tbody>';
        html += '</table>';
        html += '</div>';

        // Performance rates section
        html += '<div class="nettingale-benchmark-metrics-section">';
        html += '<h3>Performance Rates</h3>';
        html += '<table class="widefat">';
        html += '<thead><tr><th>Metric</th><th>Rate</th></tr></thead>';
        html += '<tbody>';
        html += '<tr><td>Posts per Second</td><td>' + formatNumber(metrics.rates.posts_per_second) + '/s</td></tr>';
        html += '<tr><td>Pages per Second</td><td>' + formatNumber(metrics.rates.pages_per_second) + '/s</td></tr>';
        html += '<tr><td>Comments per Second</td><td>' + formatNumber(metrics.rates.comments_per_second) + '/s</td></tr>';
        html += '<tr><td>Total Items per Second</td><td>' + formatNumber(metrics.rates.items_per_second) + '/s</td></tr>';
        html += '<tr><td>MB per Second</td><td>' + formatNumber(metrics.rates.mb_per_second) + ' MB/s</td></tr>';
        html += '</tbody>';
        html += '</table>';
        html += '</div>';

        $('#metrics-detail-content').html(html);
        $('#metrics-modal').show();
    }

    /**
     * Export metrics as JSON
     */
    function exportJSON(runId) {
        $.ajax({
            url: nettingaleBenchmark.ajaxUrl,
            type: 'POST',
            data: {
                action: 'nettingale_benchmark_export_json',
                nonce: nettingaleBenchmark.nonce,
                run_id: runId
            },
            success: function(response) {
                if (response.success && response.data) {
                    downloadFile(
                        'nettingale-benchmark-run-' + runId + '.json',
                        JSON.stringify(response.data, null, 2),
                        'application/json'
                    );
                } else {
                    alert('Failed to export JSON: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to export JSON. Please try again.');
            }
        });
    }

    /**
     * Export metrics as CSV
     */
    function exportCSV(runId) {
        $.ajax({
            url: nettingaleBenchmark.ajaxUrl,
            type: 'POST',
            data: {
                action: 'nettingale_benchmark_export_csv',
                nonce: nettingaleBenchmark.nonce,
                run_id: runId
            },
            success: function(response) {
                if (response.success && response.data) {
                    downloadFile(
                        'nettingale-benchmark-run-' + runId + '.csv',
                        response.data,
                        'text/csv'
                    );
                } else {
                    alert('Failed to export CSV: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to export CSV. Please try again.');
            }
        });
    }

    /**
     * Download file
     */
    function downloadFile(filename, content, mimeType) {
        var blob = new Blob([content], { type: mimeType });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }

    /**
     * Format size in MB/GB
     */
    function formatSize(mb) {
        if (mb >= 1000) {
            return (mb / 1000).toFixed(2) + ' GB';
        }
        return mb.toFixed(2) + ' MB';
    }

    /**
     * Format number with commas
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Initialize on document ready
    $(document).ready(function() {
        init();

        // Reinitialize when history content is reloaded via AJAX
        $(document).on('history-content-loaded', function() {
            init();
        });
    });

})(jQuery);

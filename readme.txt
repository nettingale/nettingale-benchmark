=== Nettingale Benchmark ===
Contributors: nettingale
Tags: benchmark, performance, testing, seed data, development
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WordPress performance benchmarking and testing toolkit for generating reproducible benchmark datasets. 100% local, zero data collection.

== Description ==

Nettingale Benchmark is a comprehensive WordPress plugin designed for performance testing and benchmarking. It generates reproducible, deterministic datasets that help developers and system administrators test WordPress installations under realistic load conditions.

**Privacy First: This plugin operates 100% locally. No data is collected, stored, or transmitted outside your WordPress installation.**

= Key Features =

* **Reproducible Data Generation** - Uses fixed seed (12345) to ensure identical results across multiple runs
* **Three Benchmark Tiers** - Small, Medium, and Large datasets for different testing scenarios
* **Comprehensive Content Types** - Generates posts, pages, comments, users, categories, tags, and images
* **Real Image Generation** - Creates actual JPEG images using GD library (no ImageMagick required)
* **Batch Processing** - AJAX-based processing prevents timeouts and memory issues
* **Background Processing** - Benchmarks continue running even if you navigate away from the page
* **Detailed Metrics** - Collects performance metrics including timing, rates, and storage sizes
* **Safe Cleanup** - Marker-based system ensures only benchmark data is deleted
* **Export Options** - Export metrics in JSON or CSV format

= What Makes This Different =

Most benchmark plugins test your server (CPU speed, disk I/O, network). This plugin tests WordPress itself.

* **Real WordPress operations** - Creates actual posts, comments, users, images using wp_insert_post(), not simulations
* **Real database patterns** - Tests WordPress table structures and queries, not generic MySQL tests
* **Reproducible results** - Same seed (12345) produces identical content every time for before/after testing

Use server benchmarks to choose hosting. Use this to test WordPress performance with realistic content.

= Benchmark Tiers =

**Small Tier (2-5 minutes)**
* 500 posts, 50 pages
* 1,000 comments
* 100 users
* 10 categories, 50 tags
* ~50MB disk space

**Medium Tier (20-40 minutes)**
* 5,000 posts, 200 pages
* 10,000 comments
* 1,000 users
* 25 categories, 200 tags
* ~500MB disk space

**Large Tier (3-6 hours)**
* 50,000 posts, 1,000 pages
* 100,000 comments
* 10,000 users
* 50 categories, 1,000 tags
* ~5GB disk space

= Use Cases =

* **Performance Testing** - Test how your WordPress site handles large amounts of content
* **Hosting Evaluation** - Compare different hosting providers with consistent datasets
* **Plugin Development** - Test plugins with realistic content volumes
* **Theme Testing** - Verify theme performance with various content types
* **Optimization Testing** - Measure the impact of optimization plugins and techniques
* **Database Benchmarking** - Test database performance under load

= Technical Details =

* **Deterministic Generation** - Same seed produces identical results every time
* **GD Library Only** - No ImageMagick dependency for maximum compatibility
* **10 Color Schemes** - Deterministic color selection based on post ID
* **WordPress Standards** - Follows WordPress coding standards and best practices
* **Security First** - Proper nonces, capability checks, and input sanitization
* **No External Dependencies** - Pure WordPress and PHP implementation
* **100% Local** - All processing happens on your server, no external API calls
* **Zero Data Collection** - No analytics, tracking, or data transmission
* **Complete Transparency** - All code is open source and auditable

= Metrics Collected =

* Content counts (posts, pages, comments, users, categories, tags, images)
* Filesystem storage (MB) - images and uploads
* Generation performance rates
* Execution time
* Performance rates (posts/second, MB/second, etc.)

== Installation ==

1. Upload the `nettingale-benchmark` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools > Nettingale Benchmark to access the plugin

= Minimum Requirements =

* WordPress 5.8 or greater
* PHP 7.4 or greater (8.3+ recommended)
* GD Library installed and enabled
* MySQL 8.0+ or MariaDB 10.6+
* Sufficient disk space for chosen tier

= Recommended Settings =

* PHP 8.3+ for best performance and security
* PHP memory_limit: 256M or higher
* PHP max_execution_time: 300 or higher (or 0 for unlimited)
* PHP post_max_size: 32M or higher
* PHP upload_max_filesize: 32M or higher

== Frequently Asked Questions ==

= Is the generated data reproducible? =

Yes! Using the same tier and seed (12345) will always produce identical results. This is critical for performance testing and benchmarking.

= Can I use this on a production site? =

While you can, it's NOT recommended. This plugin generates large amounts of test data and is designed for development, staging, or dedicated testing environments.

= How do I clean up the benchmark data? =

Go to Tools > Nettingale Benchmark > Settings tab. Click "Preview Cleanup" to see what will be deleted, then click "Delete All Benchmark Data" and type "DELETE" to confirm.

= What happens if I navigate away during a benchmark? =

The benchmark continues running in the background! You can safely navigate to other WordPress pages or close your browser. When you return to the Seed Data page, the plugin will automatically detect the running benchmark and show you the current progress.

= Does it require ImageMagick? =

No! The plugin uses PHP's GD library for maximum compatibility. GD is included with most PHP installations by default.

= What happens if I run out of disk space? =

The benchmark will fail gracefully and can be cleaned up. Always ensure sufficient disk space before starting a large tier.

= Can I change the seed value? =

The seed is fixed at 12345 for reproducibility. Changing it would require code modification and would break reproducibility guarantees.

= How accurate are the estimated times? =

Estimated times are based on average server performance. Actual times vary based on server resources, PHP configuration, and database performance.

= Will this slow down my site? =

Only while the benchmark is running. The generated content may slow down queries and admin screens due to the large number of posts.

= Can I customize the tier configurations? =

Not through the UI. Tier configurations are fixed to ensure reproducible benchmarks. Custom configurations would require code modifications.

= Does it work with multisite? =

The plugin is designed for single-site installations. Multisite compatibility has not been tested.

= What user roles are created? =

The plugin creates users with all standard WordPress roles: Administrator, Editor, Author, Contributor, and Subscriber.

= Are the images unique? =

Yes! Each image is uniquely generated based on its ID with deterministic colors and patterns. Same ID always produces the same image.

= Can I export the metrics? =

Yes! After a benchmark completes, go to the History tab and click "View Metrics" on any completed run. You can export as JSON or CSV.

= What database tables are created? =

The plugin creates one table: `wp_nettingale_benchmark_runs` for benchmark tracking.

== Screenshots ==

1. Seed Data tab - Select benchmark tier and start processing
2. Progress indicator showing real-time batch processing status
3. History tab displaying completed benchmark runs with metrics
4. Detailed metrics modal with content counts and performance rates
5. Cleanup tab with preview and safe deletion options
6. Settings tab for configuring plugin behavior
7. Background processing with auto-reconnection

== Changelog ==

= 1.0.0 =
* First stable release for WordPress.org
* Updated license from GPL-2.0 to GPL-3.0
* Updated database requirements to MySQL 8.0+ / MariaDB 10.6+ (WordPress.org 2025 standards)
* Added PHP 8.3+ recommendation for optimal performance and security
* Excluded assets-wordpress-org from plugin distribution (SVN assets only)
* Release automation with GitHub Actions

= 0.99 =
* Initial pre-release for WordPress.org submission
* SECURITY: Fixed SQL injection vulnerability - properly using $wpdb->prepare() for all queries
* COMPLIANCE: WordPress Filesystem API for file operations (wp_delete_file)
* COMPLIANCE: Removed .gitignore from plugin distribution
* COMPLIANCE: Added uninstall.php for proper cleanup when plugin is deleted
* ENHANCEMENT: uninstall.php respects "Cleanup on Deactivation" setting
* ENHANCEMENT: Smart stuck lock detection on Seed Data page
* ENHANCEMENT: PHP Environment Status display in Settings with color-coded recommendations
* ENHANCEMENT: Comprehensive PHP configuration checker (memory, execution time, upload limits, GD library)
* ENHANCEMENT: GD library filter persists across ALL AJAX batch requests
* ENHANCEMENT: Plugin deactivation properly clears locks and stops running benchmarks
* ENHANCEMENT: Atomic database-level locking prevents race conditions
* ENHANCEMENT: Updated tier estimates based on actual benchmark results
* ENHANCEMENT: Improved comment distribution algorithm to accurately meet tier targets
* UI IMPROVEMENT: Replaced native browser dialogs with professional modal for cleanup confirmation
* UI IMPROVEMENT: Status indicators use color-coding (Green=OK, Yellow=Warning, Red=Critical)
* BUG FIX: GD library now used exclusively (no ImageMagick)
* BUG FIX: Removed ImageMagick from PHP Environment Status display
* Three benchmark tiers (Small, Medium, Large)
* Reproducible data generation with fixed seed (12345)
* Content generators for posts, pages, comments, users, categories, tags
* Real image generation with GD library (no ImageMagick required)
* 10 deterministic color schemes
* Batch processing with AJAX prevents timeouts
* Background processing - benchmarks continue even if you navigate away
* Comprehensive metrics collection with performance rates
* JSON and CSV export
* Safe cleanup with marker system
* Preview before deletion
* Settings configuration
* History tracking of benchmark runs
* WordPress coding standards compliant
* Security: Proper nonces, capability checks, input sanitization, output escaping
* Privacy: 100% local processing, zero data collection
* Passes WordPress Plugin Check with 0 ERROR-level issues
* Ready for WordPress.org submission

== Upgrade Notice ==

= 0.99 =
Initial pre-release of Nettingale Benchmark for WordPress.org submission. Includes security fixes, WordPress.org compliance, and comprehensive benchmarking features. Install to start generating reproducible WordPress benchmark datasets.

== Credits ==

Developed by Nettingale for the WordPress community.

== Privacy & Transparency ==

**We take your privacy seriously. This plugin is designed with a privacy-first approach:**

= Zero Data Collection =
* No analytics or tracking code
* No external API calls or connections
* No data transmission to any third-party servers
* No phone home functionality
* No cookies or browser storage used

= 100% Local Processing =
* All benchmark data is generated and stored locally on your server
* Data never leaves your WordPress installation
* You have complete control over all generated content
* All database queries are local to your WordPress database
* All file operations are local to your WordPress filesystem

= Complete Transparency =
* Open source GPL v2 licensed code
* All code is publicly auditable on GitHub
* No obfuscated or encrypted code
* No hidden functionality
* Clear documentation of all features

= What Data Is Stored Locally =
The plugin only stores data locally in your WordPress database:
* Benchmark run records (timing, counts, metrics)
* Generated benchmark content (posts, users, comments, etc.)

All of this data can be viewed, exported, and deleted at any time through the plugin interface.

= Your Rights =
* You own all generated data
* You can export all metrics as JSON or CSV
* You can delete all benchmark data at any time
* No account creation or registration required
* No terms of service or privacy policy acceptance needed

== Support ==

For bug reports, feature requests, and support, please visit:
https://github.com/nettingale/nettingale-benchmark

== Contributing ==

Contributions are welcome! Please submit pull requests to:
https://github.com/nettingale/nettingale-benchmark

# Nettingale Benchmark

![WordPress Plugin Version](https://img.shields.io/badge/version-0.99-blue.svg)
![WordPress Compatibility](https://img.shields.io/badge/wordpress-5.8%2B-brightgreen.svg)
![PHP Version](https://img.shields.io/badge/php-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPLv2%2B-red.svg)
![Privacy](https://img.shields.io/badge/privacy-100%25%20local-success.svg)

**WordPress performance benchmarking plugin that generates reproducible test datasets. 100% local, zero data collection.**

## What Makes This Different

Most benchmarks test your server. This tests **WordPress**.

| Server Benchmarks | Nettingale Benchmark |
|-------------------|---------------------|
| Fibonacci calculations | Real `wp_insert_post()` calls |
| Generic file writes | WordPress image uploads with thumbnails |
| Generic MySQL queries | WordPress taxonomy queries and meta joins |
| Simulated operations | Actual WordPress functions |

Use server benchmarks to compare hosting. Use this to test WordPress performance with realistic content volumes.

---

## Features

- **Reproducible** - Fixed seed (12345) ensures identical results across runs
- **Three Tiers** - Small (5 min), Medium (30 min), Large (4 hours)
- **Complete Content** - Posts, pages, comments, users, categories, tags, images
- **Real Images** - JPEG generation using GD library
- **Batch Processing** - AJAX-based to prevent timeouts
- **Background Processing** - Runs even if you navigate away
- **Detailed Metrics** - Timing, rates, storage sizes
- **Safe Cleanup** - Marker-based deletion of benchmark data only
- **Export Options** - JSON and CSV formats
- **100% Private** - Zero data collection, no external connections

---

## Table of Contents

- [Installation](#installation)
- [Requirements](#requirements)
- [Benchmark Tiers](#benchmark-tiers)
- [Usage](#usage)
- [Reproducibility](#reproducibility)
- [Architecture](#architecture)
- [Development](#development)
- [Privacy](#privacy)
- [License](#license)
- [Support](#support)

---

## Installation

### From WordPress.org
(Coming soon after review)

### From GitHub

```bash
cd wp-content/plugins
git clone https://github.com/nettingale/nettingale-benchmark.git
```

### Manual Installation

1. Download from [GitHub Releases](https://github.com/nettingale/nettingale-benchmark/releases)
2. Extract and upload to `/wp-content/plugins/`
3. Activate through **Plugins > Installed Plugins**
4. Access at **Nettingale Benchmark** in admin menu

---

## Requirements

### Minimum
- **WordPress:** 5.8+
- **PHP:** 7.4+
- **PHP Extensions:** GD Library
- **Database:** MySQL 8.0+ or MariaDB 10.6+

### Recommended
- **PHP:** 8.3+ (for best performance and security)
- **Memory:** 256M or higher
- **Execution Time:** 300 seconds or unlimited

### Recommended PHP Settings
```ini
memory_limit = 256M
max_execution_time = 300
```

---

## Benchmark Tiers

### Small Tier
**Duration:** 2-5 minutes | **Disk Space:** ~50MB

- 500 posts, 50 pages
- 100 users, 1,000 comments
- 10 categories, 50 tags
- 1,650 images

**Use Case:** Quick testing, development, CI/CD

### Medium Tier
**Duration:** 20-40 minutes | **Disk Space:** ~500MB

- 5,000 posts, 200 pages
- 1,000 users, 10,000 comments
- 25 categories, 200 tags
- 15,600 images

**Use Case:** Realistic testing, plugin stress testing

### Large Tier
**Duration:** 3-6 hours | **Disk Space:** ~5GB

- 50,000 posts, 1,000 pages
- 10,000 users, 100,000 comments
- 50 categories, 1,000 tags
- 153,000 images

**Use Case:** Extreme stress testing, enterprise-scale

---

## Usage

### Running a Benchmark

1. Navigate to **Nettingale Benchmark**
2. Select a tier (Small, Medium, Large)
3. Click **Start Benchmark**
4. Monitor real-time progress
5. View results in **History** tab

### Background Processing

Benchmarks run in the background:
- Start a benchmark
- Navigate away or close browser
- Benchmark continues processing
- Return anytime to see progress

### Viewing Results

1. Go to **History** tab
2. Click **View Metrics** on completed run
3. Review detailed statistics
4. Export as JSON or CSV

### Cleanup

1. Go to **Settings** tab
2. Click **Preview Cleanup**
3. Click **Delete All Benchmark Data**
4. Type `DELETE` to confirm

---

## Reproducibility

### Fixed Seed

Uses a **fixed seed (12345)** for deterministic generation:

```php
mt_srand(12345);
```

### Guarantees

- ✅ Same content every time
- ✅ Pixel-perfect image reproduction
- ✅ Identical metadata and relationships
- ✅ Consistent user data
- ✅ Deterministic colors (10 schemes based on post ID % 10)

### Testing

Run the same tier twice and compare metrics:

```bash
# First run
Posts: 500, DB: 45.2MB, Files: 105.8MB

# Second run
Posts: 500, DB: 45.2MB, Files: 105.8MB

# Metrics identical ✓
```

---

## Architecture

### Plugin Structure

```
nettingale-benchmark/
├── nettingale-benchmark.php       # Main plugin file
├── includes/                       # PHP classes
│   ├── class-nettingale-benchmark-ajax-handlers.php
│   ├── class-nettingale-benchmark-batch-processor.php
│   ├── class-nettingale-benchmark-cleanup-manager.php
│   ├── class-nettingale-benchmark-comment-generator.php
│   ├── class-nettingale-benchmark-config.php
│   ├── class-nettingale-benchmark-content-generator.php
│   ├── class-nettingale-benchmark-image-generator.php
│   ├── class-nettingale-benchmark-metrics-collector.php
│   ├── class-nettingale-benchmark-post-generator.php
│   ├── class-nettingale-benchmark-taxonomy-generator.php
│   └── class-nettingale-benchmark-user-generator.php
├── admin/views/                    # Admin pages
│   ├── seed-data-page.php
│   ├── history-page.php
│   └── settings-page.php
└── assets/                         # CSS and JavaScript
    ├── css/admin-styles.css
    └── js/
        ├── batch-processor.js
        ├── cleanup-manager.js
        ├── metrics-display.js
        └── settings-manager.js
```

### Processing Phases

1. **Initialize** - Create run record
2. **Users** - Generate users with all roles
3. **Taxonomies** - Generate categories and tags
4. **Posts** - Generate posts with images (batched)
5. **Pages** - Generate pages with images (batched)
6. **Comments** - Generate comments on posts
7. **Finalize** - Collect metrics and mark complete

### Database

Single custom table: `wp_nettingale_benchmark_runs`

Generated content stored in standard WordPress tables with meta markers:
- `_nettingale_benchmark_item` - Posts/pages
- `_nettingale_benchmark_user` - Users
- `_nettingale_benchmark_comment` - Comments
- `_nettingale_benchmark_term` - Categories/tags

---

## Development

### Setup

```bash
git clone https://github.com/nettingale/nettingale-benchmark.git
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/nettingale-benchmark
```

### Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Functions: `nettingale_benchmark_function_name()`
- Classes: `Nettingale_Benchmark_Class_Name`
- Files: `class-nettingale-benchmark-*.php`

### Security

- ✅ Nonce verification on all forms/AJAX
- ✅ Capability checks (`manage_options`)
- ✅ Input sanitization
- ✅ Output escaping
- ✅ Prepared SQL statements

### Contributing

1. Check [GitHub Issues](https://github.com/nettingale/nettingale-benchmark/issues)
2. Fork and create feature branch
3. Follow WordPress coding standards
4. Test thoroughly
5. Submit Pull Request

---

## Privacy

**This plugin is 100% private and transparent:**

- ❌ No data collection
- ❌ No external API calls
- ❌ No tracking or analytics
- ❌ No user profiling
- ✅ All data stays on your server
- ✅ Open source GPL code
- ✅ Fully auditable

See [PRIVACY.md](PRIVACY.md) for complete details.

---

## License

**GNU General Public License v2 or later**

```
Nettingale Benchmark - WordPress Performance Benchmarking Plugin
Copyright (C) 2024 Nettingale

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

See [LICENSE](LICENSE) for full details.

---

## Support

- **Bug Reports:** [GitHub Issues](https://github.com/nettingale/nettingale-benchmark/issues)
- **Documentation:** [GitHub Wiki](https://github.com/nettingale/nettingale-benchmark/wiki)
- **Website:** [nettingale.com](https://nettingale.com)

---

## Disclaimer

This plugin generates large amounts of test data. **Use on development, staging, or dedicated testing environments only.** Do not use on production sites with live content. Always backup before use.

---

**Made with ❤️ by [Nettingale](https://nettingale.com)**

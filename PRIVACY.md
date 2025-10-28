# Privacy & Transparency Statement

**Last Updated:** 2025-01-25

## Our Privacy Commitment

Nettingale Benchmark is built with **privacy as a core principle**, not an afterthought.

## Zero Data Collection

We do **NOT** collect any data from your WordPress installation:

- ❌ No analytics or usage tracking
- ❌ No external API calls or connections
- ❌ No data transmission to third-party servers
- ❌ No phone home functionality
- ❌ No cookies or browser storage
- ❌ No user profiling or fingerprinting
- ❌ No hidden telemetry

## 100% Local Processing

Everything happens on your server:

- ✅ All benchmark data generated locally
- ✅ All data stored in your WordPress database
- ✅ All images stored in your uploads directory
- ✅ All database queries are local
- ✅ All file operations are local
- ✅ No internet connection required for operation

## Complete Transparency

Our code is completely open and auditable:

- ✅ GPL v2 licensed (fully open source)
- ✅ Hosted publicly on GitHub
- ✅ No obfuscated or encrypted code
- ✅ No hidden functionality
- ✅ All features clearly documented

## What Data Is Stored (Locally Only)

The plugin stores data **only in your local WordPress database**:

### 1. Benchmark Run Records
**Table:** `wp_nettingale_benchmark_runs`
**Contains:**
- Run ID, tier, status, timestamps
- Content counts (posts, pages, comments, users, etc.)
- Performance metrics (timing, rates, sizes)
- Stored locally in your MySQL/MariaDB database

### 2. Generated Benchmark Content
**Stored in standard WordPress tables:**
- Posts and pages (with `_nettingale_benchmark_item` meta marker)
- Users (with `_nettingale_benchmark_user` meta marker)
- Comments (with `_nettingale_benchmark_comment` meta marker)
- Categories and tags (with `_nettingale_benchmark_term` meta marker)
- JPEG images in WordPress uploads directory

All of this data:
- Can be viewed through the plugin interface
- Can be exported as JSON or CSV
- Can be deleted at any time through the Cleanup tab
- **Never leaves your server**

## Your Rights & Control

- ✅ **Complete Ownership** - You own all generated data
- ✅ **Full Access** - View all data through plugin or database directly
- ✅ **Export Anytime** - Export metrics as JSON or CSV at any time
- ✅ **Delete Anytime** - Full cleanup with one click, any time
- ✅ **No Lock-In** - No account required, no service dependency
- ✅ **No Terms Required** - No terms of service or privacy policy to accept
- ✅ **Offline Operation** - Works without internet connection

## WordPress.org Compliance

This plugin follows all WordPress.org plugin directory guidelines:

- **Guideline 6:** Ethical practices (no deceptive practices)
- **Guideline 7:** No required accounts or external services
- **Guideline 11:** No tracking without user consent (we don't track at all)
- **Guideline 12:** Documented and approved external connections (we have none)

## Code Verification

Don't just trust us - verify it yourself! Search the codebase for any external calls:

```bash
# Search for WordPress HTTP API
grep -r "wp_remote" . --include="*.php"
# Result: None found

# Search for cURL functions
grep -r "curl_" . --include="*.php"
# Result: None found

# Search for external file reads
grep -r "file_get_contents.*http" . --include="*.php"
# Result: None found

# Search for socket connections
grep -r "fsockopen" . --include="*.php"
# Result: None found

# Search for analytics tracking
grep -ri "google.*analytics\|gtag\|ga(\|_gaq" . --include="*.php" --include="*.js"
# Result: None found (only word "Analytics" in content generation)
```

All code is publicly available for review at: https://github.com/nettingale/nettingale-benchmark

## Privacy Promise

**We will never add:**
- Data collection or tracking
- External API calls or connections
- Analytics or telemetry
- User profiling or fingerprinting
- Mandatory account creation
- Service dependencies

**If we ever need to add any external connectivity in the future, it will be:**
1. Opt-in only (disabled by default)
2. Clearly documented in code and documentation
3. Requires explicit user consent
4. Announced prominently in changelog
5. With clear explanation of what data is transmitted and why

## Questions or Concerns?

- **Review the code:** [GitHub Repository](https://github.com/nettingale/nettingale-benchmark)
- **Ask questions:** [GitHub Issues](https://github.com/nettingale/nettingale-benchmark/issues)
- **Email us:** support@nettingale.com

## Third-Party Services

This plugin does **NOT** use any third-party services, including:

- ❌ Google Analytics
- ❌ Mixpanel
- ❌ Segment
- ❌ Hotjar
- ❌ Facebook Pixel
- ❌ Any tracking or analytics service
- ❌ Any cloud services
- ❌ Any external APIs

## Data Storage Location

All data is stored in:

1. **Your WordPress Database**
   - Custom table: `wp_nettingale_benchmark_runs`
   - Standard tables: `wp_posts`, `wp_users`, `wp_comments`, `wp_terms`, etc.

2. **Your WordPress Filesystem**
   - Generated images: `wp-content/uploads/[year]/[month]/`

**Location:** Your server only. Never transmitted elsewhere.

## GDPR & Privacy Regulations

While this plugin doesn't collect personal data and thus isn't subject to GDPR requirements for data processing, we still follow privacy-first principles:

- ✅ Data minimization (only store what's needed for functionality)
- ✅ User control (can export and delete all data)
- ✅ Transparency (this document and clear code)
- ✅ Security (WordPress security best practices)
- ✅ No third-party sharing (there is no third-party access)

## Changes to This Policy

If we ever update this privacy policy (for example, to add any optional features that might involve data transmission), we will:

1. Update this document with date stamp
2. Announce in plugin changelog
3. Require opt-in for any new data collection
4. Provide clear documentation of changes

## Contact

- **Email:** support@nettingale.com
- **GitHub:** https://github.com/nettingale/nettingale-benchmark
- **Website:** https://nettingale.com

---

**Bottom Line:** This plugin collects zero data. Everything stays on your server. You have complete control. We're completely transparent about how it works. Don't trust - verify the code yourself.

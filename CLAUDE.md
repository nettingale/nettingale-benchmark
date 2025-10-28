# Nettingale Benchmark - Development Guidelines

## Agent Usage (CRITICAL - ALWAYS USE)

**ALWAYS use the nettingale-benchmark-agent when working on this plugin.**

This agent ensures:
- WordPress plugin standards compliance
- Step-by-step incremental development
- wordpress.org submission readiness
- Proper testing after each step
- Documentation updates

**How to invoke:**
```
When working on ANY task related to nettingale-benchmark, use the Task tool to launch the nettingale-benchmark-agent.
```

## Development Approach

### Incremental Steps (MANDATORY)
- Follow the 40-step implementation plan at `/workspaces/ng-launchpad/tasks/NETTINGALE_BENCHMARK.md`
- Complete ONE step at a time
- Test thoroughly after each step
- Get user verification before proceeding
- NEVER skip steps or combine multiple steps

### Current Progress
Track implementation steps in the plan document and verify completion criteria for each step.

## WordPress Standards

### Function/Class Naming
- **Functions**: `nettingale_benchmark_function_name()`
- **Classes**: `Nettingale_Benchmark_Class_Name`
- **CSS Classes**: `.nettingale-benchmark-class-name`
- **JS Objects**: `NettingaleBenchmark`

### File Organization
```
nettingale-benchmark/
├── nettingale-benchmark.php     # Main plugin file
├── includes/                     # PHP classes (class-*.php)
├── admin/                        # Admin interface
│   ├── class-admin-page.php
│   └── views/                    # Template files
├── assets/
│   ├── js/                       # JavaScript files
│   └── css/                      # CSS files
├── readme.txt                    # WordPress.org format
└── README.md                     # GitHub format
```

### Security Requirements
- Use nonces on ALL forms and AJAX requests
- Check capabilities: `current_user_can( 'manage_options' )`
- Escape ALL output: `esc_html()`, `esc_attr()`, `esc_url()`
- Sanitize ALL input: `sanitize_text_field()`, etc.
- Use WordPress database functions (NOT raw SQL)

## Reproducibility (CRITICAL)

### Fixed Seed
```php
define( 'NETTINGALE_BENCHMARK_SEED', 12345 );
```

### Deterministic Generation
- Same seed + same ID = IDENTICAL results every time
- Test reproducibility by running benchmarks twice and comparing metrics
- ALL random operations must use seeded randomization

### Testing Reproducibility
```bash
# Run Small tier twice
# Export metrics both times
# Metrics MUST be identical
```

## Image Generation

### GD Library Only (NO ImageMagick)
- Maximum compatibility requirement
- Check for GD extension availability
- Graceful error handling if unavailable

### Image Specifications
- Featured: 1200x630px, JPEG 85% quality
- Content: 800x600px, JPEG 85% quality
- Thumbnail: 300x300px, JPEG 90% quality

### 10 Color Schemes
Must be deterministic based on `post_id % 10`

## Batch Processing

### Phases (Sequential)
1. Initialize run record
2. Generate users
3. Generate taxonomies (categories/tags)
4. Generate posts (batches of 100)
5. Generate pages (batches of 100)
6. Generate images
7. Generate comments
8. Finalize (collect metrics)

### AJAX Requirements
- Batch size: 100 items per request
- State tracking in database
- Resume capability after interruption
- Resource monitoring (memory/time)

## Cleanup Safety

### Marker System
- Posts/Pages: `_nettingale_benchmark_item` post meta
- Users: `nettingale_benchmark_user` user meta
- Comments: `_nettingale_benchmark_comment` comment meta
- Terms: `_nettingale_benchmark_term` term meta

### Safety Requirements
- ALWAYS require explicit confirmation
- Provide dry-run preview
- Only delete items with benchmark markers
- Verify counts before deletion

## Testing Requirements

### Testing Method (CRITICAL)
**ALWAYS test using PHP in the 909f9b50.ng-devs.com container - DO NOT create test files**

Use this command pattern:
```bash
docker exec 909f9b50.ng-devs.com php -r "
define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';

// Your test code here using WordPress functions
// Example: Nettingale_Benchmark_User_Generator::create_users(2, 1);
"
```

**Why this method:**
- Direct access to WordPress functions
- No leftover test files to clean up
- Faster execution
- Cleaner workflow

### After Each Step
- Deploy plugin to test WordPress site with rsync command
- Test using PHP in 909f9b50.ng-devs.com container (see Testing Method above)
- Activate plugin successfully
- No PHP errors or warnings
- No JavaScript console errors
- Feature works as expected
- **CLEANUP ALL TEST DATA** (posts, images, attachments, users, comments, etc.)
- Verify cleanup completed successfully
- **ASK USER FOR CONFIRMATION** before proceeding to next step

### Deploy Plugin Command
After completing each step, deploy to test WordPress site:
```bash
# Copy plugin files with --delete to ensure fresh version
# Excludes: CLAUDE.md/claude.md (dev docs), .gitignore (hidden file), .git (dev files)
sudo rsync -av --delete \
  --exclude="CLAUDE.md" \
  --exclude="claude.md" \
  --exclude=".gitignore" \
  --exclude=".git" \
  --exclude="assets-wordpress-org" \
  /workspaces/nettingale/nettingale-benchmark/ \
  /mnt/ng/user_data/019a0264-007a-7aa0-b141-69662f309410/domains/909f9b50.ng-devs.com/www_data/wp-content/plugins/nettingale-benchmark/

# Fix ownership
sudo chown -R www-data:www-data /mnt/ng/user_data/019a0264-007a-7aa0-b141-69662f309410/domains/909f9b50.ng-devs.com/www_data/wp-content/plugins/nettingale-benchmark
```

**Important notes:**
- `CLAUDE.md`/`claude.md` are development documentation files (not needed in production)
- `.gitignore` and `.git` directory are excluded because WordPress.org does NOT allow hidden files or git repositories in plugin distributions
- `assets-wordpress-org/` contains banners/icons/screenshots for WordPress.org listing only (uploaded separately to SVN assets/ folder, not part of plugin ZIP)
- These files are fine for local development but will cause ERROR-level issues in WordPress Plugin Check
- The rsync command ensures the deployed version matches what will be submitted to WordPress.org

### Before Release
- Test on PHP 7.4, 8.0, 8.1, 8.2
- Test on WordPress 5.8+
- Test on fresh WordPress install
- Verify reproducibility (run twice, compare)
- Test complete cleanup (no residual data)
- Validate readme.txt format
- Check coding standards compliance

## Git Workflow

### Branch Naming
Use descriptive branch names with type prefix:
- `feature/feature-name` - New features
- `fix/bug-description` - Bug fixes
- `release/vX.Y.Z` - Release preparation
- `hotfix/critical-fix` - Critical production fixes

### Commit Messages
Follow conventional commit format:
```
<type>: <short summary>

<detailed description>

- Bullet point changes
- Another change
- Final change
```

**Types:** feat, fix, docs, style, refactor, test, chore, release

**Example:**
```
Release v1.0.0 - First stable release with GPL-3.0

- Bump version from 0.99 to 1.0.0
- Update license from GPL-2.0 to GPL-3.0
- Add WordPress-recommended GPL boilerplate
- Update database requirements (MySQL 8.0+, MariaDB 10.6+)
```

### Creating Pull Requests
Always use `gh` CLI for consistency:

```bash
# 1. Create branch
git checkout -b feature/new-feature

# 2. Make changes and commit
git add -A
git commit -m "feat: Add new feature

- Implemented feature X
- Updated documentation
- Added tests"

# 3. Push branch
git push origin feature/new-feature

# 4. Create PR with gh CLI
gh pr create --title "Add New Feature" --body "## Summary
Brief description of what this PR does.

## Changes
- **Added**: New feature X
- **Updated**: Documentation
- **Fixed**: Related bug

## Impact
Describe the impact of these changes."
```

### PR Body Template
```markdown
## Summary
Brief description of what this PR does.

## Changes
- **Added**: List new features
- **Updated**: List modifications
- **Fixed**: List bug fixes
- **Removed**: List deletions

## Impact
Describe the impact of these changes.
```

**Important:**
- Keep PRs focused on single feature/fix
- Reference issues if applicable
- Update changelog in readme.txt
- Ensure all tests pass before creating PR

## Documentation Requirements

### Update After Changes
- `readme.txt` - WordPress.org changelog
- `README.md` - GitHub documentation
- Code comments (PHPDoc blocks)
- Implementation plan progress

### readme.txt Format
```
= 1.0.0 =
* Initial release
* Feature: Data seeding with three tiers
* Feature: Metrics collection and export
```

## Tier Configurations

### Small (Quick Test - 5-10 minutes)
- 500 posts, 50 pages, 1,000 comments
- 100 users, 10 categories, 50 tags
- ~150MB disk space

### Medium (Standard Benchmark - 30-60 minutes)
- 5,000 posts, 200 pages, 10,000 comments
- 1,000 users, 25 categories, 200 tags
- ~1.5GB disk space

### Large (Stress Test - 4-8 hours)
- 50,000 posts, 1,000 pages, 100,000 comments
- 10,000 users, 50 categories, 1,000 tags
- ~15GB disk space

## Key Files Reference

- **Implementation Plan**: `/workspaces/ng-launchpad/tasks/NETTINGALE_BENCHMARK.md`
- **Agent Definition**: `/workspaces/nettingale/.claude/agents/nettingale-benchmark-agent.md`
- **Project Root**: `/workspaces/nettingale/nettingale-benchmark/`

## WordPress.org Preparation

### Required Files
- `readme.txt` (WordPress.org format)
- `README.md` (GitHub format)
- `LICENSE` (GPL v2 or later)
- Plugin headers in main file
- Screenshot images (for wordpress.org)

### Submission Checklist
- [ ] All code follows WordPress coding standards
- [ ] All strings internationalized
- [ ] Security audit completed
- [ ] Tested on multiple PHP/WP versions
- [ ] readme.txt validated
- [ ] Screenshots created
- [ ] Documentation complete

## Remember

1. **ALWAYS use nettingale-benchmark-agent** when working on this plugin
2. **Follow steps sequentially** - never skip or combine steps
3. **Test after every change** before proceeding
4. **Reproducibility is critical** - same seed must produce identical results
5. **WordPress standards are mandatory** - this will be public on wordpress.org
6. **Security is non-negotiable** - proper escaping, sanitization, nonces, capabilities
7. **GD only** - no ImageMagick dependencies
8. **Document everything** - update readme.txt and README.md as you go

## Getting Started

When beginning work on this plugin:
1. Launch nettingale-benchmark-agent
2. Read the implementation plan
3. Identify current step
4. Complete that step
5. Test thoroughly
6. Get user verification
7. Move to next step

Never work directly on this plugin without using the specialized agent.

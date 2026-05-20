# TreeSizeMailer Package - AI Agent Instructions

## Project Overview

This is a **Laravel package** (not a full application) that scans directory structures, calculates sizes, and emails formatted reports. It's designed for monitoring disk usage across Laravel applications.

**Package Name**: `dxgx/tree-size-mailer`  
**Namespace**: `DeadSimpleApps\TreeSizeMailer`  
**Laravel Versions**: 9.x, 10.x, 11.x, 12.x  
**PHP**: 8.2+

## Essential Commands

```bash
# Manual testing via Artisan command
php artisan dg:tree-size-mailer

# Package development (no test suite yet)
composer install
```

## Architecture & Structure

```
src/
  ├── TreeSizeMailerServiceProvider.php  # Auto-discovered service provider
  ├── Commands/
  │   └── TreeSizeReportCommand.php      # Artisan command (dg:tree-size-mailer)
  └── Mail/
      └── TreeSizeReportMail.php         # Mailable for email reports
config/
  └── tree-size-mailer.php               # Publishable configuration
resources/views/
  └── email.blade.php                    # Email template
```

### Service Provider Pattern

The package uses Laravel's auto-discovery via `composer.json` extra section:
- Registers the Artisan command
- Publishes config (`tree-size-mailer-config` tag)
- Publishes views (`tree-size-mailer-views` tag)
- Loads views from package namespace (`tree-size-mailer::email`)

## Code Conventions

### PHP Standards

- **Strict typing**: Use return types (`void`, `array`, `string`, `bool`) on all methods
- **Type hints**: Always type-hint parameters
- **Property types**: Declare types for all class properties
- **Doc blocks**: Required for complex methods, especially with detailed parameter/return descriptions

### Laravel Conventions

- **Config access**: Always use `config('tree-size-mailer.key', $default)` with fallback defaults
- **Path handling**: Relative paths use `./` prefix for consistency
- **Environment variables**: Support `.env` overrides via `env()` in config file only
- **Mailable pattern**: Use modern `Envelope` and `Content` mailables (Laravel 9+)

### Naming & Organization

- Command signature: `dg:tree-size-mailer` (follows Laravel kebab-case convention)
- Publishable tags: `tree-size-mailer-{config|views}` (descriptive with package prefix)
- Config keys: snake_case (e.g., `min_file_size`, `breakdown_dirs`)

## Configuration System

All features are configuration-driven via `config/tree-size-mailer.php`:

- **Multi-section reports**: Overview, Tree View, Detailed, Custom Breakdowns
- **Size filtering**: Different thresholds per section (`min_overview_size`, `min_tree_size`, etc.)
- **Directory exclusion**: Wildcard pattern matching (e.g., `/vendor*`, `*test*`)
- **Custom breakdowns**: Configurable depth levels for specific directories
- **Row limits**: Prevent oversized email reports

When adding features, prioritize configuration options over hardcoded behavior.

## Testing & Development

**Current State**: No automated tests yet.

**Manual testing workflow**:
1. Update code in `src/`
2. Run `php artisan dg:tree-size-mailer`
3. Check console output and received email
4. Verify configuration changes work as expected

**Future considerations**: When adding tests, use Pest (see user memory notes).

## Key Implementation Details

### Directory Size Calculation

The command uses `RecursiveIteratorIterator` with `CATCH_GET_CHILD` flag to handle permission errors gracefully. Path exclusion uses wildcard pattern matching for flexibility.

### Email Report Structure

Four configurable sections in order:
1. **Root Level Overview**: First-level directories only
2. **Tree View**: Hierarchical structure with visual indentation
3. **Detailed Report**: Flat list, sorted by size, with row limit
4. **Custom Breakdowns**: User-defined directories with custom depth

### Path Normalization

- Input paths: Allow flexible formats (with/without leading `./` or `/`)
- Storage: Normalize to `./relative/path` format
- Comparison: Convert to `/absolute/path` for pattern matching

## Common Tasks

### Adding New Configuration Options

1. Add to `config/tree-size-mailer.php` with doc block and default
2. Support environment variable override where appropriate
3. Update [README.md](README.md) configuration section
4. Use in code with `config('tree-size-mailer.new_option', $default)`

### Modifying Report Sections

1. Update data building in `TreeSizeReportCommand::handle()`
2. Pass new data to `TreeSizeReportMail` constructor
3. Update mailable properties
4. Modify `email.blade.php` template
5. Document in [README.md](README.md)

### Supporting New Laravel Versions

1. Update `composer.json` require constraints
2. Test service provider auto-discovery
3. Verify mailable compatibility (Envelope/Content pattern)
4. Update [CHANGELOG.md](CHANGELOG.md) and [README.md](README.md)

## Dependencies

**Minimal by design**: Only Laravel core packages required.

- `illuminate/console`: Artisan command support
- `illuminate/mail`: Email delivery
- `illuminate/support`: Service provider, helpers

**No external packages**: Keeps maintenance burden low and compatibility high.

## Documentation

- [README.md](README.md): User-facing installation and usage
- [CHANGELOG.md](CHANGELOG.md): Version history and breaking changes
- Config file comments: Inline documentation for all options

When making changes, update relevant documentation in the same commit.

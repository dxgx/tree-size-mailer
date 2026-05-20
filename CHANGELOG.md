# Changelog

All notable changes to `dxgx/tree-size-mailer` will be documented in this file.

## [1.6.0] - 2026-05-20

### Added
- `layout` configuration option to control which email sections are displayed and their order
- Support for customizable report layouts (choose 1-3 sections from available options)
- Plain text email version automatically generated alongside HTML version
- Four configurable layout sections: `root_level_overview`, `directory_tree`, `detailed_directory_sizes`, `custom_breakdowns`

### Changed
- Email reports now conditionally display sections based on `layout` configuration
- Both HTML (styled tables) and plain text (ASCII formatted) versions are generated for all emails
- Section order can be customized by reordering the `layout` array

## [1.5.0] - 2026-05-20

### Added
- `tree_view_depth` configuration option to independently control tree view depth (separate from `max_depth`)
- Environment variable `TREE_SIZE_REPORT_TREE_VIEW_DEPTH` for tree view depth configuration

### Changed
- Tree view depth is now configurable separately via `tree_view_depth` config option
- Tree view section header now displays the actual configured depth dynamically

## [1.4.0] - 2026-05-19

### Added
- Root Level Overview section showing first-level directories only with their total recursive sizes
- New section appears first in email report, providing quick summary of main directories

### Changed
- Email report now has Root Level Overview as the first section
- Improved report structure with clearer hierarchy

## [1.0.0] - 2026-05-14

### Added
- Initial release
- Directory tree size scanning and reporting
- Four report sections: Overview, Detailed, Vendor Breakdown, Tree View
- Email delivery with HTML formatting
- Comprehensive configuration options via config file and environment variables
- Configurable scan path, depth, and size thresholds
- Support for multiple email recipients
- Laravel 11.x and 12.x compatibility
- Artisan command: `dg:tree-size-mailer`
- Service provider with auto-discovery
- Publishable config and views
- Complete documentation and usage examples

### Features
- **Overview Section**: High-level directory summary with configurable depth
- **Detailed Report**: Complete directory listing with size sorting
- **Vendor Analysis**: Composer package size breakdown
- **Tree View**: Hierarchical directory structure with visual indentation
- **Flexible Configuration**: Environment variables and config file support
- **Size Filtering**: Configurable minimum size thresholds for each section
- **Multi-Recipient**: Send reports to multiple email addresses
- **Custom Scan Paths**: Scan any directory, not just the Laravel root

### Configuration Options
- `recipients`: Array of email addresses
- `scan_path`: Base directory to analyze
- `max_depth`: Maximum directory levels (default: 5)
- `min_file_size`: Minimum size for detailed report (default: 100 KB)
- `min_overview_size`: Minimum size for overview (default: 1 MB)
- `min_tree_size`: Minimum size for tree view (default: 1 MB)
- `app_name`: Application name for email subject

### Environment Variables
- `TREE_SIZE_REPORT_EMAIL`: Primary recipient email
- `TREE_SIZE_REPORT_SCAN_PATH`: Custom scan directory
- `TREE_SIZE_REPORT_MAX_DEPTH`: Maximum depth
- `TREE_SIZE_REPORT_MIN_SIZE`: Minimum file size
- `TREE_SIZE_REPORT_MIN_OVERVIEW_SIZE`: Minimum overview size
- `TREE_SIZE_REPORT_MIN_TREE_SIZE`: Minimum tree size

### Technical Details
- PHP 8.2+ required
- Laravel 11.x and 12.x support
- PSR-4 autoloading
- Service provider auto-discovery
- Publishable assets (config and views)
- Recursive directory iteration
- Size calculation and formatting
- Exception handling for permission errors

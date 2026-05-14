# Changelog

All notable changes to `tree-size-mailer` will be documented in this file.

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
- Artisan command: `tree-size:report`
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
- `DISK_REPORT_EMAIL`: Primary recipient email
- `DISK_REPORT_SCAN_PATH`: Custom scan directory
- `DISK_REPORT_MAX_DEPTH`: Maximum depth
- `DISK_REPORT_MIN_SIZE`: Minimum file size
- `DISK_REPORT_MIN_OVERVIEW_SIZE`: Minimum overview size
- `DISK_REPORT_MIN_TREE_SIZE`: Minimum tree size

### Technical Details
- PHP 8.2+ required
- Laravel 11.x and 12.x support
- PSR-4 autoloading
- Service provider auto-discovery
- Publishable assets (config and views)
- Recursive directory iteration
- Size calculation and formatting
- Exception handling for permission errors

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Recipients
    |--------------------------------------------------------------------------
    |
    | Email addresses that will receive the tree size report. You can specify
    | multiple recipients. The TREE_SIZE_REPORT_EMAIL environment variable will
    | override the first recipient if set.
    |
    */

    'recipients' => [
        env('TREE_SIZE_REPORT_EMAIL', 'admin@example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Path
    |--------------------------------------------------------------------------
    |
    | The base directory path to scan for size analysis. Defaults to the
    | Laravel application base path. You can override this to scan a
    | different directory or use TREE_SIZE_REPORT_SCAN_PATH env variable.
    |
    */

    'scan_path' => env('TREE_SIZE_REPORT_SCAN_PATH', base_path()),

    /*
    |--------------------------------------------------------------------------
    | Maximum Directory Depth
    |--------------------------------------------------------------------------
    |
    | Maximum number of directory levels to analyze for the overview and
    | tree view sections. Deeper directories will still be scanned but
    | not displayed at their full depth in the report.
    |
    | Default: 5 levels
    |
    */

    'max_depth' => (int) env('TREE_SIZE_REPORT_MAX_DEPTH', 5),

    /*
    |--------------------------------------------------------------------------
    | Minimum File Size
    |--------------------------------------------------------------------------
    |
    | Minimum size in bytes for directories to appear in the detailed report
    | and vendor breakdown. Smaller directories are excluded to reduce noise.
    |
    | Default: 102400 bytes (100 KB)
    |
    */

    'min_file_size' => (int) env('TREE_SIZE_REPORT_MIN_SIZE', 102400),

    /*
    |--------------------------------------------------------------------------
    | Minimum Overview Size
    |--------------------------------------------------------------------------
    |
    | Minimum size in bytes for directories to appear in the overview section.
    | This is typically larger than min_file_size to show only significant
    | directories in the high-level overview.
    |
    | Default: 1048576 bytes (1 MB)
    |
    */

    'min_overview_size' => (int) env('TREE_SIZE_REPORT_MIN_OVERVIEW_SIZE', 1048576),

    /*
    |--------------------------------------------------------------------------
    | Minimum Tree Size
    |--------------------------------------------------------------------------
    |
    | Minimum size in bytes for directories to appear in the tree view.
    | Directories smaller than this threshold are excluded from the
    | hierarchical tree display.
    |
    | Default: 1048576 bytes (1 MB)
    |
    */

    'min_tree_size' => (int) env('TREE_SIZE_REPORT_MIN_TREE_SIZE', 1048576),

    /*
    |--------------------------------------------------------------------------
    | Excluded Directories
    |--------------------------------------------------------------------------
    |
    | Array of directory path patterns to exclude from all reports. Patterns
    | are matched against directory paths only (files are not checked).
    | Supports wildcard matching with * character.
    |
    | Pattern matching rules:
    |   "/vendor*"  - Excludes dirs starting with /vendor
    |                 (matches: /vendor, /vendor_folder, /vendor/sub/path)
    |   "*vendor"   - Excludes dirs ending with vendor
    |                 (matches: /vendor, /my_vendor, but not /my_vendor_is)
    |   "*vendor*"  - Excludes dirs containing vendor anywhere
    |                 (matches: /vendor, /my/vendor/path, /vendor_path)
    |
    | For performance optimization, directories are sorted and checked in order.
    | More specific patterns should be listed first.
    |
    | Example array syntax:
    |   - ['/node_modules', '/vendor', 'cache', 'storage/logs']
    |   - ['test', 'tmp', 'build', 'dist']
    |
    */

    'excluded_dirs' => [
        // '/node_modules',
        // '/vendor*',
        // '*/cache',
        // '*test*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | The application name to use in the email subject line. Falls back to
    | the Laravel app name if not specified.
    |
    */

    'app_name' => env('APP_NAME', 'Laravel App'),

];

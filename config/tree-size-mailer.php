<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Recipients
    |--------------------------------------------------------------------------
    |
    | Email addresses that will receive the tree size report. You can specify
    | multiple recipients. The DISK_REPORT_EMAIL environment variable will
    | override the first recipient if set.
    |
    */

    'recipients' => [
        env('DISK_REPORT_EMAIL', 'admin@example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Path
    |--------------------------------------------------------------------------
    |
    | The base directory path to scan for size analysis. Defaults to the
    | Laravel application base path. You can override this to scan a
    | different directory or use DISK_REPORT_SCAN_PATH env variable.
    |
    */

    'scan_path' => env('DISK_REPORT_SCAN_PATH', base_path()),

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

    'max_depth' => (int) env('DISK_REPORT_MAX_DEPTH', 5),

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

    'min_file_size' => (int) env('DISK_REPORT_MIN_SIZE', 102400),

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

    'min_overview_size' => (int) env('DISK_REPORT_MIN_OVERVIEW_SIZE', 1048576),

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

    'min_tree_size' => (int) env('DISK_REPORT_MIN_TREE_SIZE', 1048576),

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

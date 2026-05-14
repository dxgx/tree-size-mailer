# Tree Size Mailer

A Laravel package that generates comprehensive directory tree size reports and emails them to specified recipients. Perfect for monitoring disk usage, identifying large directories, and tracking storage growth over time.

## Features

- 📊 **Overview Section** - High-level directory size summary
- 📂 **Detailed Report** - All directories with configurable minimum size threshold
- 📦 **Vendor Breakdown** - Composer package sizes analysis
- 🌳 **Tree View** - Hierarchical directory structure with indentation
- ⚙️ **Fully Configurable** - Control scan depth, size thresholds, and recipients
- 📧 **Email Reports** - Automatic email delivery with HTML formatting
- 🔧 **Environment Support** - Configure via `.env` variables

## Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.x
- Configured mail driver

## Installation

Install the package via Composer:

```bash
composer require deadsimpleapps/tree-size-mailer
```

### Publish Configuration

Publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag=tree-size-mailer-config
```

This creates `config/tree-size-mailer.php` in your application.

### Publish Views (Optional)

If you want to customize the email template:

```bash
php artisan vendor:publish --tag=tree-size-mailer-views
```

This publishes the email blade template to `resources/views/vendor/tree-size-mailer/`.

## Configuration

### Configuration File

After publishing, edit `config/tree-size-mailer.php`:

```php
return [
    // Email recipients (array of email addresses)
    'recipients' => [
        env('DISK_REPORT_EMAIL', 'admin@example.com'),
    ],

    // Base directory to scan (defaults to Laravel base path)
    'scan_path' => env('DISK_REPORT_SCAN_PATH', base_path()),

    // Maximum directory depth for overview and tree view
    'max_depth' => (int) env('DISK_REPORT_MAX_DEPTH', 5),

    // Minimum size (bytes) for detailed report entries
    'min_file_size' => (int) env('DISK_REPORT_MIN_SIZE', 102400), // 100 KB

    // Minimum size (bytes) for overview section
    'min_overview_size' => (int) env('DISK_REPORT_MIN_OVERVIEW_SIZE', 1048576), // 1 MB

    // Minimum size (bytes) for tree view
    'min_tree_size' => (int) env('DISK_REPORT_MIN_TREE_SIZE', 1048576), // 1 MB

    // Application name for email subject
    'app_name' => env('APP_NAME', 'Laravel App'),
];
```

### Environment Variables

Add these to your `.env` file:

```bash
# Required: Email recipient(s)
DISK_REPORT_EMAIL=admin@example.com

# Optional: Advanced configuration
DISK_REPORT_SCAN_PATH=/path/to/scan
DISK_REPORT_MAX_DEPTH=5
DISK_REPORT_MIN_SIZE=102400         # 100 KB
DISK_REPORT_MIN_OVERVIEW_SIZE=1048576  # 1 MB
DISK_REPORT_MIN_TREE_SIZE=1048576      # 1 MB
```

### Multiple Recipients

To send reports to multiple email addresses, edit the config file directly:

```php
'recipients' => [
    'admin@example.com',
    'devops@example.com',
    'manager@example.com',
],
```

## Usage

### Manual Execution

Run the report command manually:

```bash
php artisan tree-size:report
```

The command will:
1. Scan the configured directory
2. Generate size reports (overview, detailed, vendor breakdown, tree view)
3. Email the report to all configured recipients
4. Display summary statistics in the console

### Scheduled Execution

Add the command to your application's scheduler for automatic reports.

#### Laravel 11+ (using `schedule` in `bootstrap/app.php` or `routes/console.php`)

**Option 1: In `routes/console.php`:**

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('tree-size:report')->daily();
```

**Option 2: In `bootstrap/app.php`:**

```php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('tree-size:report')->daily();
})
```

#### Laravel 10 and below (using `app/Console/Kernel.php`)

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('tree-size:report')->daily();
}
```

### Scheduling Examples

```php
// Daily at 2:00 AM
Schedule::command('tree-size:report')->dailyAt('02:00');

// Weekly on Monday at 6:00 AM
Schedule::command('tree-size:report')->weeklyOn(1, '06:00');

// Monthly on the 1st at 3:00 AM
Schedule::command('tree-size:report')->monthlyOn(1, '03:00');

// Every Sunday at midnight
Schedule::command('tree-size:report')->weekly()->sundays()->at('00:00');
```

## Report Sections

The email report includes four main sections:

### 1. Overview
High-level summary of directories up to the configured depth (default: 5 levels). Shows only directories larger than `min_overview_size` (default: 1 MB).

### 2. Detailed Directory Sizes
Complete list of all directories with their sizes, sorted by size descending. Excludes directories smaller than `min_file_size` (default: 100 KB) and vendor directories (shown separately).

### 3. Vendor Package Breakdown
Analysis of Composer vendor packages, showing the size of each package up to 3 directory levels deep. Useful for identifying large dependencies.

### 4. Directory Tree
Hierarchical tree view with visual indentation showing the directory structure. Only includes directories larger than `min_tree_size` (default: 1 MB).

## Console Output

When you run the command, you'll see summary statistics:

```
Tree size report generated:
  Overview: 45 dirs, 2.34 GB
  Detailed: 312 dirs, 3.12 GB
  Vendor: 156 packages, 487.23 MB
  Tree: 89 items, 2.67 GB
Tree size report emailed to: admin@example.com
```

## Customization

### Custom Email Template

After publishing views, customize `resources/views/vendor/tree-size-mailer/email.blade.php`. The template receives these variables:

- `$rows` - Detailed directory list
- `$overview` - Overview section data
- `$vendorBreakdown` - Vendor package sizes
- `$treeView` - Hierarchical tree data
- `$basePath` - Scanned directory path
- `$generatedAt` - Report generation timestamp

### Custom Scan Path

To scan a different directory (e.g., multi-tenant setup):

```php
'scan_path' => '/var/www/client-sites/client-123',
```

Or use environment variable:

```bash
DISK_REPORT_SCAN_PATH=/var/www/client-sites/client-123
```

## Size Thresholds

Adjust thresholds to control report granularity:

- **Small projects:** Lower thresholds (50 KB, 500 KB)
- **Large projects:** Higher thresholds (1 MB, 10 MB)
- **Storage monitoring:** Lower overview threshold, higher detailed threshold

Example for large project:

```php
'min_file_size' => 524288,        // 512 KB
'min_overview_size' => 10485760,  // 10 MB
'min_tree_size' => 5242880,       // 5 MB
```

## Troubleshooting

### No Email Received

1. Verify mail configuration: `php artisan config:cache`
2. Check mail logs: `tail -f storage/logs/laravel.log`
3. Test mail: `php artisan tinker` then `Mail::raw('Test', fn($msg) => $msg->to('test@example.com')->subject('Test'))`

### Permission Errors

Ensure the web server user has read access to the scan directory:

```bash
chmod -R 755 /path/to/scan
```

### Memory Errors

For very large directory trees, increase PHP memory:

```bash
php -d memory_limit=512M artisan tree-size:report
```

### Timeout Issues

For slow file systems, increase max execution time:

```bash
php -d max_execution_time=600 artisan tree-size:report
```

## Security Considerations

- Limit scan paths to trusted directories
- Restrict email recipients to authorized personnel
- Be cautious when publishing views (may expose directory structure)
- Use queue for large scans to avoid blocking requests

## License

MIT License. See LICENSE file for details.

## Support

For issues, questions, or contributions, please contact:
- Email: dargud@gmail.com

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

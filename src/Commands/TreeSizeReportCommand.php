<?php

namespace DeadSimpleApps\TreeSizeMailer\Commands;

use DeadSimpleApps\TreeSizeMailer\Mail\TreeSizeReportMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TreeSizeReportCommand extends Command
{
    protected $signature = 'dg:tree-size-mailer';

    protected $description = 'Generate a directory tree size report and email it';

    public function handle(): void
    {
        $basePath = config('tree-size-mailer.scan_path', base_path());
        $rootLevel = $this->buildRootLevelView($basePath);
        $rows = $this->buildReport($basePath);
        $treeView = $this->buildTreeView($basePath);
        
        // Build custom directory breakdowns
        $customBreakdowns = $this->buildCustomBreakdowns($basePath);

        // Calculate totals for each section
        $rootLevelTotal = array_sum(array_column($rootLevel, 'size_bytes'));
        $detailedTotal = array_sum(array_column($rows, 'size_bytes'));
        $treeTotal = array_sum(array_column($treeView, 'size_bytes'));

        $this->info('Tree size report generated:');
        $this->info('  Root Level: ' . count($rootLevel) . ' dirs, ' . $this->formatSize($rootLevelTotal));
        $this->info('  Detailed: ' . count($rows) . ' dirs, ' . $this->formatSize($detailedTotal));
        
        foreach ($customBreakdowns as $breakdown) {
            $total = array_sum(array_column($breakdown['items'], 'size_bytes'));
            $this->info('  ' . $breakdown['title'] . ': ' . count($breakdown['items']) . ' items, ' . $this->formatSize($total));
        }
        
        $this->info('  Tree: ' . count($treeView) . ' items, ' . $this->formatSize($treeTotal));

        $recipients = config('tree-size-mailer.recipients', ['admin@example.com']);
        
        // Gather config for display in email
        $config = [
            'max_depth' => config('tree-size-mailer.max_depth', 5),
            'tree_view_depth' => config('tree-size-mailer.tree_view_depth', 5),
            'min_file_size' => config('tree-size-mailer.min_file_size', 102400),
            'min_overview_size' => config('tree-size-mailer.min_overview_size', 1048576),
            'min_tree_size' => config('tree-size-mailer.min_tree_size', 1048576),
            'detailed_max_rows' => config('tree-size-mailer.detailed_max_rows', 100),
            'breakdown_dirs' => config('tree-size-mailer.breakdown_dirs', []),
            'detailed_total' => $detailedTotal,
            'detailed_total_human' => $this->formatSize($detailedTotal),
            'root_level_total' => $rootLevelTotal,
            'root_level_total_human' => $this->formatSize($rootLevelTotal),
        ];

        foreach ($recipients as $email) {
            Mail::to($email)->send(new TreeSizeReportMail($rootLevel, $rows, $treeView, $customBreakdowns, $basePath, $config));
        }

        $this->info('Tree size report emailed to: ' . implode(', ', $recipients));
    }

    /**
     * Check if a directory path should be excluded based on configured patterns.
     * Only checks directory paths, not filenames.
     *
     * @param string $path The directory path to check (should start with ./)
     * @return bool True if the directory should be excluded
     */
    private function isExcluded(string $path): bool
    {
        $excludedDirs = config('tree-size-mailer.excluded_dirs', []);
        
        if (empty($excludedDirs)) {
            return false;
        }

        // Normalize path - ensure it starts with /
        $normalizedPath = '/' . ltrim($path, './');

        foreach ($excludedDirs as $pattern) {
            // Normalize pattern
            $pattern = '/' . ltrim($pattern, './');
            
            if ($this->matchesPattern($normalizedPath, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match a directory path against a wildcard pattern.
     * Supports * wildcard for matching any characters.
     *
     * Examples:
     *   "/vendor*" matches /vendor, /vendor_folder, /vendor/sub/path
     *   "*vendor" matches /vendor, /my_vendor (but not /my_vendor_is)
     *   "*vendor*" matches /vendor, /my/vendor/path, /vendor_path
     *
     * @param string $path The directory path to check
     * @param string $pattern The pattern to match against
     * @return bool True if path matches pattern
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // If no wildcard, do exact match or prefix match for subdirectories
        if (strpos($pattern, '*') === false) {
            return $path === $pattern || str_starts_with($path, $pattern . '/');
        }

        // Convert pattern to regex
        // Escape special regex characters except *
        $regex = preg_quote($pattern, '/');
        // Replace escaped \* with .*
        $regex = str_replace('\*', '.*', $regex);
        // Anchor the pattern
        $regex = '/^' . $regex . '(\/.*)?$/';

        return (bool) preg_match($regex, $path);
    }

    private function buildReport(string $basePath): array
    {
        $dirSizes = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            foreach ($iterator as $object) {
                if ($object->isDir()) {
                    $dirSizes[$object->getPathname()] = $this->dirSize($object->getPathname());
                }
            }
        } catch (\Exception $e) {
            $this->warn('Error scanning: ' . $e->getMessage());
        }

        arsort($dirSizes);

        $rows = [];
        $minSize = config('tree-size-mailer.min_file_size', 102400);
        $maxRows = config('tree-size-mailer.detailed_max_rows', 100);
        $breakdownDirs = config('tree-size-mailer.breakdown_dirs', []);

        // First, add breakdown directories as collapsed entries
        foreach ($breakdownDirs as $breakdownPath => $depth) {
            $normalizedPath = ltrim($breakdownPath, './');
            $fullPath = $basePath . '/' . $normalizedPath;
            
            if (is_dir($fullPath)) {
                $totalSize = $this->calculateRecursiveSize($fullPath);
                
                if ($totalSize >= $minSize) {
                    $relativePath = './' . $normalizedPath;
                    $breakdownId = 'breakdown-' . str_replace(['/', ' ', '.'], '-', trim($breakdownPath, './'));
                    $rows[] = [
                        'path' => $relativePath,
                        'size_bytes' => $totalSize,
                        'size_human' => $this->formatSize($totalSize),
                        'is_breakdown' => true,
                        'breakdown_id' => $breakdownId,
                    ];
                }
            }
        }

        foreach ($dirSizes as $dir => $size) {
            // Skip items smaller than configured minimum
            if ($size < $minSize) {
                continue;
            }

            // Convert to relative path
            $relativePath = str_starts_with($dir, $basePath)
                ? './' . ltrim(substr($dir, strlen($basePath)), '/')
                : $dir;

            // Check if directory is excluded (replaces old vendor-only check)
            if ($this->isExcluded($relativePath)) {
                continue;
            }
            
            // Check if directory is in breakdown_dirs configuration or is a subdirectory of one
            if ($this->isInBreakdownDirs($relativePath, $breakdownDirs)) {
                continue;
            }

            $rows[] = [
                'path' => $relativePath,
                'size_bytes' => $size,
                'size_human' => $this->formatSize($size),
                'is_breakdown' => false,
            ];
        }

        // Sort all rows by size
        usort($rows, function($a, $b) {
            return $b['size_bytes'] <=> $a['size_bytes'];
        });

        // Apply row limit after sorting (but keep all for total calculation)
        if ($maxRows > 0 && count($rows) > $maxRows) {
            $rows = array_slice($rows, 0, $maxRows);
        }

        return $rows;
    }

    /**
     * Build a view of only the first level directories in the root.
     *
     * @param string $basePath The base path to scan
     * @return array Array of first-level directories with their total recursive sizes
     */
    private function buildRootLevelView(string $basePath): array
    {
        $rootDirs = [];
        
        try {
            // Scan only the immediate subdirectories of basePath
            $iterator = new \DirectoryIterator($basePath);
            
            foreach ($iterator as $item) {
                if ($item->isDot() || !$item->isDir()) {
                    continue;
                }
                
                $dirName = $item->getFilename();
                $relativePath = './' . $dirName;
                
                // Check if directory is excluded
                if ($this->isExcluded($relativePath)) {
                    continue;
                }
                
                // Calculate recursive size for this directory
                $totalSize = $this->calculateRecursiveSize($item->getPathname());
                
                $rootDirs[] = [
                    'name' => $dirName,
                    'path' => $relativePath,
                    'size_bytes' => $totalSize,
                    'size_human' => $this->formatSize($totalSize),
                ];
            }
        } catch (\Exception $e) {
            $this->warn('Error building root level view: ' . $e->getMessage());
        }
        
        // Sort by size (largest first)
        usort($rootDirs, function($a, $b) {
            return $b['size_bytes'] <=> $a['size_bytes'];
        });
        
        return $rootDirs;
    }

    private function dirSize(string $path): int
    {
        $size = 0;
        try {
            foreach (new \DirectoryIterator($path) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            // skip unreadable
        }

        return $size;
    }

    /**
     * Calculate the total recursive size of a directory including all subdirectories.
     *
     * @param string $path The directory path
     * @return int Total size in bytes
     */
    private function calculateRecursiveSize(string $path): int
    {
        $size = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            foreach ($iterator as $object) {
                if ($object->isFile()) {
                    $size += $object->getSize();
                }
            }
        } catch (\Exception $e) {
            // skip unreadable
        }

        return $size;
    }

    private function buildTreeView(string $basePath): array
    {
        $tree = [];
        $minSize = config('tree-size-mailer.min_tree_size', 1048576);
        $maxDepth = config('tree-size-mailer.tree_view_depth', 5);

        try {
            // Build directory structure with sizes
            $dirSizes = [];
            $filesSizes = []; // Track direct files in each directory
            $filesCounts = []; // Track file counts in each directory

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            foreach ($iterator as $object) {
                if ($object->isFile()) {
                    $path = $object->getPathname();
                    $relativePath = str_starts_with($path, $basePath)
                        ? ltrim(substr($path, strlen($basePath)), '/')
                        : $path;

                    $parts = explode('/', $relativePath);
                    array_pop($parts); // Remove filename to get directory

                    // Check if any parent directory should be excluded
                    for ($i = 1; $i <= count($parts); $i++) {
                        $dirPath = './' . implode('/', array_slice($parts, 0, $i));
                        if ($this->isExcluded($dirPath)) {
                            continue 2; // Skip this file entirely
                        }
                    }

                    $fileSize = $object->getSize();

                    // Track directory sizes for ALL levels (including deeper than configured max)
                    for ($i = 1; $i <= count($parts); $i++) {
                        $dirPath = implode('/', array_slice($parts, 0, $i));
                        if (!isset($dirSizes[$dirPath])) {
                            $dirSizes[$dirPath] = 0;
                        }
                        $dirSizes[$dirPath] += $fileSize;
                    }

                    // Track direct files only in directories (for "(files)" entries)
                    // Only track up to configured max depth since we won't display deeper
                    if (count($parts) <= $maxDepth) {
                        $dirPath = implode('/', $parts);
                        if (!isset($filesSizes[$dirPath])) {
                            $filesSizes[$dirPath] = 0;
                            $filesCounts[$dirPath] = 0;
                        }
                        $filesSizes[$dirPath] += $fileSize;
                        $filesCounts[$dirPath]++;
                    }
                }
            }

            // Build hierarchical structure
            $structure = $this->buildTreeStructure($dirSizes, $filesSizes, $filesCounts, $minSize, $maxDepth);

            // Format as flat list with indentation
            $tree = $this->formatTreeLines($structure, 0);
        } catch (\Exception $e) {
            $this->warn('Error building tree view: ' . $e->getMessage());
        }

        return $tree;
    }

    private function buildTreeStructure(array $dirSizes, array $filesSizes, array $filesCounts, int $minSize, int $maxDepth): array
    {
        $tree = [];

        // Get top-level directories
        $topLevel = [];
        foreach ($dirSizes as $path => $size) {
            if (strpos($path, '/') === false && $size >= $minSize) {
                $topLevel[$path] = $size;
            }
        }

        arsort($topLevel);

        foreach ($topLevel as $name => $size) {
            $node = [
                'name' => $name,
                'size' => $size,
                'size_human' => $this->formatSize($size),
                'children' => $this->buildTreeChildren($name, $dirSizes, $filesSizes, $filesCounts, $minSize, 1, $maxDepth),
            ];
            $tree[] = $node;
        }

        return $tree;
    }

    private function buildTreeChildren(string $parentPath, array $dirSizes, array $filesSizes, array $filesCounts, int $minSize, int $level, int $maxDepth): array
    {
        if ($level >= $maxDepth) {
            return [];
        }

        $children = [];

        // Get direct children
        $childDirs = [];
        foreach ($dirSizes as $path => $size) {
            if (str_starts_with($path, $parentPath . '/')) {
                $remainder = substr($path, strlen($parentPath) + 1);
                if (strpos($remainder, '/') === false && $size >= $minSize) {
                    $childDirs[$remainder] = $size;
                }
            }
        }

        arsort($childDirs);

        foreach ($childDirs as $name => $size) {
            $fullPath = $parentPath . '/' . $name;
            $node = [
                'name' => $name,
                'size' => $size,
                'size_human' => $this->formatSize($size),
                'children' => $this->buildTreeChildren($fullPath, $dirSizes, $filesSizes, $filesCounts, $minSize, $level + 1, $maxDepth),
            ];
            $children[] = $node;
        }

        // Add (files) entry if direct files >= configured minimum
        if (isset($filesSizes[$parentPath]) && $filesSizes[$parentPath] >= $minSize) {
            $fileCount = $filesCounts[$parentPath] ?? 0;
            $filesLabel = $fileCount === 1 ? '1 file' : $fileCount . ' files';
            $children[] = [
                'name' => '(' . $filesLabel . ')',
                'size' => $filesSizes[$parentPath],
                'size_human' => $this->formatSize($filesSizes[$parentPath]),
                'children' => [],
            ];
        }

        return $children;
    }

    private function formatTreeLines(array $nodes, int $depth, string $prefix = ''): array
    {
        $lines = [];
        $count = count($nodes);

        foreach ($nodes as $index => $node) {
            $isLast = ($index === $count - 1);
            
            // Build current line prefix
            if ($depth === 0) {
                $currentPrefix = '';
            } else {
                $currentPrefix = $prefix . ($isLast ? '└── ' : '├── ');
            }

            // Determine if we should hide the size for this node
            // Hide size if: parent has only 1 child directory OR parent has only files (no subdirectories)
            $shouldHideSize = false;
            if (!empty($node['children'])) {
                // Count directory children (exclude "(x files)" entries)
                $dirChildrenCount = 0;
                foreach ($node['children'] as $child) {
                    if (!str_starts_with($child['name'], '(')) {
                        $dirChildrenCount++;
                    }
                }
                
                // Hide size if there's exactly 1 directory child OR 0 directory children (only files)
                if ($dirChildrenCount <= 1) {
                    $shouldHideSize = true;
                }
            }

            $lines[] = [
                'indent' => $currentPrefix,
                'name' => $node['name'],
                'size_human' => $shouldHideSize ? '' : $node['size_human'],
                'size_bytes' => $node['size'],
            ];

            // Process children with increased depth
            if (!empty($node['children'])) {
                // Build prefix for children
                if ($depth === 0) {
                    $childPrefix = '';
                } else {
                    $childPrefix = $prefix . ($isLast ? '    ' : '│   ');
                }
                $childLines = $this->formatTreeLines($node['children'], $depth + 1, $childPrefix);
                $lines = array_merge($lines, $childLines);
            }
        }

        return $lines;
    }

    /**
     * Check if a directory path is configured for custom breakdown.
     *
     * @param string $path The directory path to check
     * @param array $breakdownDirs The breakdown configuration array
     * @return bool True if the directory or any of its parents is in breakdown config
     */
    private function isInBreakdownDirs(string $path, array $breakdownDirs): bool
    {
        if (empty($breakdownDirs)) {
            return false;
        }

        $normalizedPath = '/' . ltrim($path, './');

        foreach ($breakdownDirs as $breakdownPath => $depth) {
            $normalizedBreakdownPath = '/' . ltrim($breakdownPath, './');
            
            // Check if path matches or is a subdirectory of a breakdown path
            if ($normalizedPath === $normalizedBreakdownPath || str_starts_with($normalizedPath, $normalizedBreakdownPath . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build custom directory breakdowns based on configuration.
     *
     * @param string $basePath The base path to scan
     * @return array Array of breakdown sections with their items
     */
    private function buildCustomBreakdowns(string $basePath): array
    {
        $breakdownDirs = config('tree-size-mailer.breakdown_dirs', []);
        $breakdowns = [];

        if (empty($breakdownDirs)) {
            return $breakdowns;
        }

        foreach ($breakdownDirs as $breakdownPath => $depth) {
            $normalizedPath = '/' . ltrim($breakdownPath, './');
            $title = trim($normalizedPath, '/') ?: 'Root';
            $title = ucfirst(str_replace(['/', '_', '-'], ' ', $title));
            
            $breakdown = $this->buildDirectoryBreakdown($basePath, $breakdownPath, $depth);
            
            if (!empty($breakdown)) {
                $totalSize = array_sum(array_column($breakdown, 'size_bytes'));
                $breakdownId = 'breakdown-' . str_replace(['/', ' ', '.'], '-', trim($breakdownPath, './'));
                $originalCount = count($breakdown);
                $maxRows = config('tree-size-mailer.detailed_max_rows', 100);
                $isLimited = false;
                
                // Apply row limit to breakdown items
                if ($maxRows > 0 && count($breakdown) > $maxRows) {
                    $breakdown = array_slice($breakdown, 0, $maxRows);
                    $isLimited = true;
                }
                
                $breakdowns[] = [
                    'path' => $breakdownPath,
                    'title' => $title . ' Breakdown (' . $depth . ' Level' . ($depth > 1 ? 's' : '') . ')',
                    'depth' => $depth,
                    'items' => $breakdown,
                    'total_bytes' => $totalSize,
                    'total_human' => $this->formatSize($totalSize),
                    'breakdown_id' => $breakdownId,
                    'is_limited' => $isLimited,
                    'original_count' => $originalCount,
                    'displayed_count' => count($breakdown),
                ];
            }
        }

        return $breakdowns;
    }

    /**
     * Build a breakdown for a specific directory with custom depth.
     *
     * @param string $basePath The base path to scan
     * @param string $breakdownPath The directory to break down (e.g., '/vendor')
     * @param int $depth The depth level for breakdown
     * @return array The breakdown items
     */
    private function buildDirectoryBreakdown(string $basePath, string $breakdownPath, int $depth): array
    {
        $directorySizes = [];
        $normalizedBreakdownPath = ltrim($breakdownPath, './');

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            foreach ($iterator as $object) {
                if ($object->isFile()) {
                    $path = $object->getPathname();
                    $relativePath = str_starts_with($path, $basePath)
                        ? ltrim(substr($path, strlen($basePath)), '/')
                        : $path;

                    // Only process files within the breakdown directory
                    if (!str_starts_with($relativePath, $normalizedBreakdownPath . '/') && $relativePath !== $normalizedBreakdownPath) {
                        continue;
                    }

                    // Remove the breakdown path prefix and get directory path (exclude filename)
                    $subPath = substr($relativePath, strlen($normalizedBreakdownPath));
                    $subPath = ltrim($subPath, '/');
                    
                    $parts = explode('/', $subPath);
                    array_pop($parts); // Remove filename
                    
                    // Limit to configured depth
                    $levelCount = min(count($parts), $depth);
                    if ($levelCount === 0) {
                        continue; // Skip root-level files in breakdown dir
                    }
                    
                    $breakdownSubPath = implode('/', array_slice($parts, 0, $levelCount));
                    $fullPath = $normalizedBreakdownPath . '/' . $breakdownSubPath;

                    if (!isset($directorySizes[$fullPath])) {
                        $directorySizes[$fullPath] = 0;
                    }
                    $directorySizes[$fullPath] += $object->getSize();
                }
            }
        } catch (\Exception $e) {
            $this->warn('Error scanning breakdown directory: ' . $e->getMessage());
        }

        arsort($directorySizes);

        $breakdown = [];
        $minSize = config('tree-size-mailer.min_file_size', 102400);

        foreach ($directorySizes as $dir => $size) {
            // Skip items smaller than configured minimum
            if ($size < $minSize) {
                continue;
            }

            $breakdown[] = [
                'path' => './' . $dir,
                'size_bytes' => $size,
                'size_human' => $this->formatSize($size),
            ];
        }

        return $breakdown;
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}

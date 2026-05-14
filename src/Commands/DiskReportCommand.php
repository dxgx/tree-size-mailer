<?php

namespace DeadSimpleApps\TreeSizeMailer\Commands;

use DeadSimpleApps\TreeSizeMailer\Mail\DiskReportMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DiskReportCommand extends Command
{
    protected $signature = 'tree-size:report';

    protected $description = 'Generate a directory tree size report and email it';

    public function handle(): void
    {
        $basePath = config('tree-size-mailer.scan_path', base_path());
        $rows = $this->buildReport($basePath);
        $overview = $this->buildOverview($basePath);
        $vendorBreakdown = $this->buildVendorBreakdown($basePath);
        $treeView = $this->buildTreeView($basePath);

        // Calculate totals for each section
        $overviewTotal = array_sum(array_column($overview, 'size_bytes'));
        $detailedTotal = array_sum(array_column($rows, 'size_bytes'));
        $vendorTotal = array_sum(array_column($vendorBreakdown, 'size_bytes'));
        $treeTotal = array_sum(array_column($treeView, 'size_bytes'));

        $this->info('Tree size report generated:');
        $this->info('  Overview: ' . count($overview) . ' dirs, ' . $this->formatSize($overviewTotal));
        $this->info('  Detailed: ' . count($rows) . ' dirs, ' . $this->formatSize($detailedTotal));
        $this->info('  Vendor: ' . count($vendorBreakdown) . ' packages, ' . $this->formatSize($vendorTotal));
        $this->info('  Tree: ' . count($treeView) . ' items, ' . $this->formatSize($treeTotal));

        $recipients = config('tree-size-mailer.recipients', ['admin@example.com']);

        foreach ($recipients as $email) {
            Mail::to($email)->send(new DiskReportMail($rows, $overview, $vendorBreakdown, $treeView, $basePath));
        }

        $this->info('Tree size report emailed to: ' . implode(', ', $recipients));
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

        foreach ($dirSizes as $dir => $size) {
            // Skip items smaller than configured minimum
            if ($size < $minSize) {
                continue;
            }

            // Convert to relative path
            $relativePath = str_starts_with($dir, $basePath)
                ? './' . ltrim(substr($dir, strlen($basePath)), '/')
                : $dir;

            // Skip vendor directories
            if (str_starts_with($relativePath, './vendor/') || $relativePath === './vendor') {
                continue;
            }

            $rows[] = [
                'path' => $relativePath,
                'size_bytes' => $size,
                'size_human' => $this->formatSize($size),
            ];
        }

        return $rows;
    }

    private function buildOverview(string $basePath): array
    {
        $topLevelSizes = [];
        $maxDepth = config('tree-size-mailer.max_depth', 4);

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

                    // Skip vendor directories for overview
                    if (str_starts_with($relativePath, 'vendor/')) {
                        continue;
                    }

                    // Get directory path only (exclude filename), then limit to configured levels
                    $parts = explode('/', $relativePath);
                    // Remove the filename (last part) to get only the directory path
                    array_pop($parts);
                    // Take up to configured directory levels
                    $levelCount = min(count($parts), $maxDepth);
                    if ($levelCount === 0) {
                        continue; // Skip root-level files
                    }
                    $topLevel = implode('/', array_slice($parts, 0, $levelCount));

                    if (!isset($topLevelSizes[$topLevel])) {
                        $topLevelSizes[$topLevel] = 0;
                    }
                    $topLevelSizes[$topLevel] += $object->getSize();
                }
            }
        } catch (\Exception $e) {
            $this->warn('Error scanning for overview: ' . $e->getMessage());
        }

        arsort($topLevelSizes);

        $overview = [];
        $minSize = config('tree-size-mailer.min_overview_size', 1048576);

        foreach ($topLevelSizes as $dir => $size) {
            // Skip items smaller than configured minimum
            if ($size < $minSize) {
                continue;
            }

            $overview[] = [
                'path' => './' . $dir,
                'size_bytes' => $size,
                'size_human' => $this->formatSize($size),
            ];
        }

        return $overview;
    }

    private function buildVendorBreakdown(string $basePath): array
    {
        $vendorSizes = [];

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

                    // Only process vendor directories
                    if (!str_starts_with($relativePath, 'vendor/')) {
                        continue;
                    }

                    // Get vendor path (3 levels: vendor/package/subfolder), exclude filename
                    $parts = explode('/', $relativePath);
                    array_pop($parts); // Remove filename
                    $levelCount = min(count($parts), 3);
                    if ($levelCount === 0) {
                        continue;
                    }
                    $vendorPackage = implode('/', array_slice($parts, 0, $levelCount));

                    if (!isset($vendorSizes[$vendorPackage])) {
                        $vendorSizes[$vendorPackage] = 0;
                    }
                    $vendorSizes[$vendorPackage] += $object->getSize();
                }
            }
        } catch (\Exception $e) {
            $this->warn('Error scanning vendor: ' . $e->getMessage());
        }

        arsort($vendorSizes);

        $breakdown = [];
        $minSize = config('tree-size-mailer.min_file_size', 102400);

        foreach ($vendorSizes as $dir => $size) {
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

    private function buildTreeView(string $basePath): array
    {
        $tree = [];
        $minSize = config('tree-size-mailer.min_tree_size', 1048576);
        $maxDepth = config('tree-size-mailer.max_depth', 5);

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
            $tree = $this->formatTreeLines($structure, '', true, []);
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

    private function formatTreeLines(array $nodes, string $prefix, bool $isRoot, array $parentPrefixes): array
    {
        $lines = [];
        $count = count($nodes);

        foreach ($nodes as $index => $node) {
            $isLast = ($index === $count - 1);
            $connector = $isRoot ? '' : ($isLast ? '└── ' : '├── ');
            $currentPrefix = $prefix . $connector;

            $lines[] = [
                'indent' => $currentPrefix,
                'name' => $node['name'],
                'size_human' => $node['size_human'],
                'size_bytes' => $node['size'],
            ];

            // Process children with updated prefix (add 8 spaces per level, no vertical lines)
            if (!empty($node['children'])) {
                $childPrefix = $prefix . ($isRoot ? '' : '        ');
                $childLines = $this->formatTreeLines($node['children'], $childPrefix, false, []);
                $lines = array_merge($lines, $childLines);
            }
        }

        return $lines;
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

================================================================================
DIRECTORY TREE SIZE REPORT
================================================================================

Generated: {{ $generatedAt }}
Base path: {{ $basePath }}

@php
foreach ($config['layout'] as $section) {
    if ($section === 'root_level_overview') {
        echo "\n";
        echo "================================================================================\n";
        echo "ROOT LEVEL OVERVIEW - Total: {$config['root_level_total_human']}\n";
        echo "================================================================================\n\n";
        
        foreach ($rootLevel as $row) {
            echo str_pad($row['size_human'], 12, ' ', STR_PAD_LEFT) . "  {$row['name']}\n";
        }
        
        echo "\nNote: First-level directories only • Sizes include all subdirectories\n";
        
    } elseif ($section === 'directory_tree') {
        echo "\n";
        echo "================================================================================\n";
        echo "DIRECTORY TREE (Top Items, {$config['tree_view_depth']} Depth)\n";
        echo "================================================================================\n\n";
        
        foreach ($treeView as $row) {
            echo str_pad($row['size_human'], 12, ' ', STR_PAD_LEFT) . "  {$row['indent']}{$row['name']}\n";
        }
        
        $minTreeSizeMB = number_format($config['min_tree_size'] / 1024 / 1024, 1);
        echo "\nNote: Max depth: {$config['tree_view_depth']} levels • Min size: {$minTreeSizeMB} MB\n";
        
    } elseif ($section === 'detailed_directory_sizes') {
        echo "\n";
        echo "================================================================================\n";
        echo "DETAILED DIRECTORY SIZES (All Levels) - Total: {$config['detailed_total_human']}\n";
        echo "================================================================================\n\n";
        
        foreach ($rows as $row) {
            $breakdown = ($row['is_breakdown'] ?? false) ? ' - see breakdown below' : '';
            echo str_pad($row['size_human'], 12, ' ', STR_PAD_LEFT) . "  {$row['path']}{$breakdown}\n";
        }
        
        $minFileSizeKB = number_format($config['min_file_size'] / 1024, 0);
        echo "\nNote: Limited to {$config['detailed_max_rows']} rows • Min size: {$minFileSizeKB} KB\n";
        
    } elseif ($section === 'custom_breakdowns') {
        foreach ($customBreakdowns as $breakdown) {
            echo "\n";
            echo "================================================================================\n";
            echo strtoupper($breakdown['title']) . " - Total: {$breakdown['total_human']}\n";
            echo "================================================================================\n\n";
            
            foreach ($breakdown['items'] as $row) {
                echo str_pad($row['size_human'], 12, ' ', STR_PAD_LEFT) . "  {$row['path']}\n";
            }
            
            $minFileSizeKB = number_format($config['min_file_size'] / 1024, 0);
            $limitNote = $breakdown['is_limited'] 
                ? "• Limited to {$breakdown['displayed_count']} of {$breakdown['original_count']} items" 
                : '';
            echo "\nNote: Depth: {$breakdown['depth']} levels • Min size: {$minFileSizeKB} KB {$limitNote}\n";
        }
    }
}
@endphp

================================================================================
End of Report
================================================================================

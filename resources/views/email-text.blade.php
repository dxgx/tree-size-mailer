================================================================================
DIRECTORY TREE SIZE REPORT
================================================================================

Generated: {{ $generatedAt }}
Base path: {{ $basePath }}

@foreach($config['layout'] as $section)
@if($section === 'root_level_overview')
================================================================================
ROOT LEVEL OVERVIEW - Total: {{ $config['root_level_total_human'] }}
================================================================================

@foreach($rootLevel as $row)
{{ str_pad($row['size_human'], 12, ' ', STR_PAD_LEFT) }}  {{ $row['name'] }}
@endforeach

Note: First-level directories only • Sizes include all subdirectories

@elseif($section === 'directory_tree')
================================================================================
DIRECTORY TREE (Top Items, {{ $config['tree_view_depth'] }} Depth)
================================================================================

@foreach($treeView as $row)
{{ str_pad($row['size_human'], 12, ' ', STR_PAD_LEFT) }}  {{ $row['indent'] }}{{ $row['name'] }}
@endforeach

Note: Max depth: {{ $config['tree_view_depth'] }} levels • Min size: {{ number_format($config['min_tree_size'] / 1024 / 1024, 1) }} MB

@elseif($section === 'detailed_directory_sizes')
================================================================================
DETAILED DIRECTORY SIZES (All Levels) - Total: {{ $config['detailed_total_human'] }}
================================================================================

@foreach($rows as $row)
{{ str_pad($row['size_human'], 12, ' ', STR_PAD_LEFT) }}  {{ $row['path'] }}@if($row['is_breakdown'] ?? false) - see breakdown below@endif

@endforeach

Note: Limited to {{ $config['detailed_max_rows'] }} rows • Min size: {{ number_format($config['min_file_size'] / 1024, 0) }} KB

@elseif($section === 'custom_breakdowns')
@foreach($customBreakdowns as $breakdown)
================================================================================
{{ strtoupper($breakdown['title']) }} - Total: {{ $breakdown['total_human'] }}
================================================================================

@foreach($breakdown['items'] as $row)
{{ str_pad($row['size_human'], 12, ' ', STR_PAD_LEFT) }}  {{ $row['path'] }}
@endforeach

Note: Depth: {{ $breakdown['depth'] }} levels • Min size: {{ number_format($config['min_file_size'] / 1024, 0) }} KB @if($breakdown['is_limited'])• Limited to {{ $breakdown['displayed_count'] }} of {{ $breakdown['original_count'] }} items@endif

@endforeach
@endif
@endforeach

================================================================================
End of Report
================================================================================

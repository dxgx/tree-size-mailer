<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: monospace; font-size: 13px; color: #222; background: #fff; }
        h2 { font-size: 16px; margin-top: 24px; }
        h2:first-of-type { margin-top: 0; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 5px; }
        th { background: #f0f0f0; text-align: left; padding: 6px 10px; border-bottom: 2px solid #ccc; }
        td { padding: 4px 10px; border-bottom: 1px solid #eee; }
        td.size { text-align: right; white-space: nowrap; font-weight: bold; width: 90px; font-family: monospace; }
        td.tree-path { font-family: monospace; }
        .tree-table td { padding: 0.5px 10px; line-height: 1.3; }
        .muted { color: #888; font-size: 11px; }
        .section-note { color: #888; font-size: 10px; margin-top: 2px; margin-bottom: 20px; font-style: italic; }
        .overview-section { background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    </style>
</head>
<body>
    <h2>📁 Directory Tree Size Report</h2>
    <p class="muted">Generated: {{ $generatedAt }}<br>Base path: {{ $basePath }}</p>

    <h2>🌳 Directory Tree (Top Items, {{ $config['max_depth'] }} Depth)</h2>
    <table class="tree-table">
        <thead>
            <tr>
                <th>Size</th>
                <th>Path</th>
            </tr>
        </thead>
        <tbody>
            @foreach($treeView as $row)
            <tr>
                <td class="size" style="border: none;">{{ $row['size_human'] }}</td>
                <td class="tree-path" style="white-space: pre; border: none;">{{ $row['indent'] }}{{ $row['name'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p class="section-note">Max depth: {{ $config['max_depth'] }} levels • Min size: {{ number_format($config['min_tree_size'] / 1024 / 1024, 1) }} MB</p>

    <h2>📂 Detailed Directory Sizes (All Levels) - Total: {{ $config['detailed_total_human'] }}</h2>
    <table>
        <thead>
            <tr>
                <th>Size</th>
                <th>Directory</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td class="size">{{ $row['size_human'] }}</td>
                <td>{{ $row['path'] }}@if($row['is_breakdown'] ?? false) <strong><a href="#{{ $row['breakdown_id'] }}" style="color: #666; text-decoration: none;">- see breakdown below</a></strong>@endif</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p class="section-note">Limited to {{ $config['detailed_max_rows'] }} rows • Min size: {{ number_format($config['min_file_size'] / 1024, 0) }} KB</p>

    @foreach($customBreakdowns as $breakdown)
    <h2 id="{{ $breakdown['breakdown_id'] }}">📦 {{ $breakdown['title'] }} - Total: {{ $breakdown['total_human'] }}</h2>
    <table>
        <thead>
            <tr>
                <th>Size</th>
                <th>Directory</th>
            </tr>
        </thead>
        <tbody>
            @foreach($breakdown['items'] as $row)
            <tr>
                <td class="size">{{ $row['size_human'] }}</td>
                <td>{{ $row['path'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p class="section-note">Depth: {{ $breakdown['depth'] }} levels • Min size: {{ number_format($config['min_file_size'] / 1024, 0) }} KB @if($breakdown['is_limited'])• <strong>Limited to {{ $breakdown['displayed_count'] }} of {{ $breakdown['original_count'] }} items</strong>@endif</p>
    @endforeach
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: monospace; font-size: 13px; color: #222; background: #fff; }
        h2 { font-size: 16px; margin-top: 24px; }
        h2:first-of-type { margin-top: 0; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th { background: #f0f0f0; text-align: left; padding: 6px 10px; border-bottom: 2px solid #ccc; }
        td { padding: 4px 10px; border-bottom: 1px solid #eee; }
        td.size { text-align: right; white-space: nowrap; font-weight: bold; width: 90px; }
        .muted { color: #888; font-size: 11px; }
        .overview-section { background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    </style>
</head>
<body>
    <h2>📁 Directory Tree Size Report</h2>
    <p class="muted">Generated: {{ $generatedAt }}<br>Base path: {{ $basePath }}</p>

    <div class="overview-section">
        <h2>📊 Overview (Top Levels, only dirs)</h2>
        <table>
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Directory</th>
                </tr>
            </thead>
            <tbody>
                @foreach($overview as $row)
                <tr>
                    <td class="size">{{ $row['size_human'] }}</td>
                    <td>{{ $row['path'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2>📂 Detailed Directory Sizes (All Levels)</h2>
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
                <td>{{ $row['path'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2>📦 Vendor Package Breakdown (3 Levels)</h2>
    <table>
        <thead>
            <tr>
                <th>Size</th>
                <th>Package</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vendorBreakdown as $row)
            <tr>
                <td class="size">{{ $row['size_human'] }}</td>
                <td>{{ $row['path'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2>🌳 Directory Tree (Top Items, Configured Depth)</h2>
    <table>
        <thead>
            <tr>
                <th>Size</th>
                <th>Path</th>
            </tr>
        </thead>
        <tbody>
            @foreach($treeView as $row)
            <tr>
                <td class="size">{{ $row['size_human'] }}</td>
                <td style="white-space: pre;">{{ $row['indent'] }}{{ $row['name'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

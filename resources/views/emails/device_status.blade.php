<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
<h4>Devices Summary</h4>
<table>
    <tr>
        <th>Online Devices</th>
        <th>Offline (Short-Term)</th>
        <th>Offline (Long-Term)</th>
    </tr>
    <tr>
        <td style="color: {{ $onlineCount > 0 ? '#4CAF50' : '#333' }}">{{ $onlineCount }}</td>
        <td style="color: {{ $shortOfflineCount > 0 ? '#FF5722' : '#333' }}">{{ $shortOfflineCount }}</td>
        <td style="color: {{ $longOfflineCount > 0 ? '#FF5722' : '#333' }}">{{ $longOfflineCount }}</td>
    </tr>
</table>
</body>
</html>

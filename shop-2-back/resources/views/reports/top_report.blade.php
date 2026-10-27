<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Profit Report</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<h1>Top Profit Report</h1>
<p>Generated on: {{ now()->format('Y-m-d H:i') }}</p>

@if (!empty($categories))
    <h2>Top Categories</h2>
    <table>
        <tr><th>Category</th><th>Profit</th></tr>
        @foreach ($categories as $category)
            <tr>
                <td>{{ $category['name'] }}</td>
                <td>${{ number_format($category['total_revenue'], 2) }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if (!empty($products))
    <h2>Top Products</h2>
    <table>
        <tr><th>Product</th><th>Profit</th></tr>
        @foreach ($products as $product)
            <tr>
                <td>{{ $product['name'] }}</td>
                <td>${{ number_format($product['total_revenue'], 2) }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if (!empty($users))
    <h2>Top Users</h2>
    <table>
        <tr><th>User</th><th>Profit</th></tr>
        @foreach ($users as $user)
            <tr>
                <td>{{ $user['name'] }}</td>
                <td>${{ number_format($user['total_revenue'], 2) }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if (!empty($periods))
    <h2>Top Periods</h2>
    <table>
        <tr><th>Period</th><th>Profit</th></tr>
        @foreach ($periods as $period)
            <tr>
                <td>{{ $period['period'] }}</td>
                <td>${{ number_format($period['total_revenue'], 2) }}</td>
            </tr>
        @endforeach
    </table>
@endif
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annual Report</title>
</head>
<body>
<h1>Your unpaid order has been canceled</h1>

<ul>
    @foreach($order as $key => $value)
        @if(is_array($value))
            <li>
                <strong>{{ ucfirst($key) }}:</strong>
                <ul>
                    @foreach($value as $k => $val)
                        <li><strong>{{ ucfirst($k) }}:</strong> {{ $val }}</li>
                    @endforeach
                </ul>
            </li>
        @else
            <li><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</li>
        @endif
    @endforeach
</ul>

</body>
</html>

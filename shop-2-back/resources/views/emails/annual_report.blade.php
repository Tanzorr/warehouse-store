<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annual Report</title>
</head>
<body>
<h1>Annual User Report</h1>
<p>Here is your annual report:</p>

<ul>
    @foreach($report as $key => $value)
        <li><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</li>
    @endforeach
</ul>

<p>Thank you!</p>
</body>
</html>

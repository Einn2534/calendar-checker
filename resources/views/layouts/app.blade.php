<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Calendar Checker')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            background: #f5f5f5;
            color: #1f2937;
        }
        header {
            background: #1d4ed8;
            color: #fff;
            padding: 1.5rem 2rem;
        }
        main {
            max-width: 960px;
            margin: 2rem auto;
            background: #fff;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
        }
        a.button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            background: #2563eb;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        a.button:hover {
            background: #1e3a8a;
        }
        ul.availabilities {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0 0;
        }
        ul.availabilities li {
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9fafb;
        }
        ul.availabilities li span {
            font-variant-numeric: tabular-nums;
        }
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
<header>
    <h1>@yield('title', 'Calendar Checker')</h1>
</header>
<main>
    @yield('content')
</main>
</body>
</html>

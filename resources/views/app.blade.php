<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="bg-white text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <div id="app"></div>
</body>
</html>

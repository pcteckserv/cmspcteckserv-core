<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Administração' }}</title>
    @vite([
        'vendor/pcteckserv/cms-core/resources/css/admin.scss',
        'vendor/pcteckserv/cms-core/resources/js/admin.js',
    ])
</head>
<body>
    @yield('content')
</body>
</html>

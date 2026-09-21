<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#0e1830">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="neverland-whatsapp" content="{{ preg_replace('/\D+/', '', (string) config('services.whatsapp.number')) }}">
        <meta name="description" content="Neverland Store — marketplace akun Total Football siap main.">
        <title>Neverland Store</title>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Tools Management</title>
        @vite(['resources/css/app.css', 'resources/js/dashboard/main.jsx'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
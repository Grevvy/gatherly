<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">  {{-- add this --}}

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    {{-- IMPORTANT: load app.jsx, not app.js --}}
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
  </head>
  <body class="antialiased p-6">
    <h1 class="text-2xl mb-4">Welcome to Gatherly</h1>

    {{-- React island --}}
    <div
      data-react-component="Hello"
      data-props='@json(["name" => "Gerrit"])'>
    </div>
  </body>
</html>

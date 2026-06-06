<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title','Portal')</title>
  @include('partials.favicon')
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/brands.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/fontawesome.min.css">
  <style>
    .progress-fill-anim {
      transform-origin: left center;
      will-change: width, transform;
      transition: width 0.55s cubic-bezier(0.22, 1, 0.36, 1), background-color 0.3s ease;
      animation: progress-fill-reveal 0.48s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes progress-fill-reveal {
      0% { transform: scaleX(0.12); opacity: 0.65; }
      100% { transform: scaleX(1); opacity: 1; }
    }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
  @yield('content')
</body>
</html>

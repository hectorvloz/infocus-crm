@php
  $faviconSettings = $appSettings ?? ((new \App\Repositories\FileStore('settings.json'))->find('settings') ?: []);
  $faviconPath = $faviconSettings['favicon'] ?? ($faviconSettings['logo_small'] ?? null) ?? '/favicon.ico';
  $faviconRelativePath = ltrim((string) $faviconPath, '/');
  $faviconPublicPath = public_path($faviconRelativePath);
  $faviconVersionRaw = !empty($faviconSettings['updated_at'])
      ? (string) $faviconSettings['updated_at']
      : (is_file($faviconPublicPath) ? (string) filemtime($faviconPublicPath) : '1');
  $faviconVersion = '?v=' . rawurlencode($faviconVersionRaw);
  $faviconExt = strtolower(pathinfo(parse_url((string) $faviconPath, PHP_URL_PATH) ?: (string) $faviconPath, PATHINFO_EXTENSION));
  $faviconType = match ($faviconExt) {
      'svg' => 'image/svg+xml',
      'png' => 'image/png',
      'jpg', 'jpeg' => 'image/jpeg',
      'webp' => 'image/webp',
      default => 'image/x-icon',
  };
  $faviconUrl = preg_match('/^https?:\/\//i', (string) $faviconPath)
      ? (string) $faviconPath
      : asset($faviconRelativePath);
@endphp

<link rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}{{ $faviconVersion }}">
<link rel="shortcut icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}{{ $faviconVersion }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}{{ $faviconVersion }}">

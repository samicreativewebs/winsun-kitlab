<?php
// Restrict public execution to first-party PHP endpoints; uploads are static images.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$decoded = rawurldecode($path);
if (str_contains($decoded, "\0") || str_contains($decoded, '..')) { http_response_code(403); exit; }
if (preg_match('~^/uploads/~', $decoded) && !preg_match('~^/uploads/[0-9a-f]{24}\.(?:png|jpg|webp)$~', $decoded)) { http_response_code(403); exit; }
if (str_ends_with(strtolower($decoded), '.php') && !in_array($decoded, ['/index.php','/api.php','/admin.php','/setup.php'], true)) { http_response_code(404); exit; }
if ($decoded === '/' || $decoded === '') { require __DIR__.'/public/index.php'; return true; }
if (is_file(__DIR__.'/public'.$decoded)) return false;
http_response_code(404); echo 'Not found';

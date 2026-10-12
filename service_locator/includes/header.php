<?php
/**
 * includes/header.php
 * Shared <head> + opening <body> markup for all ServiceLocator pages.
 * Expects (optional): $page_title (string) set by the calling page before include.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

if (!isset($page_title)) {
    $page_title = 'ServiceLocator';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?> — ServiceLocator</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['Inter', 'Segoe UI', 'sans-serif'] },
        colors: {
          brand: {
            50: '#F0FDF4', 100: '#DCFCE7', 200: '#BBF7D0',
            500: '#22C55E', 600: '#16A34A', 700: '#15803D', 900: '#065F46',
          },
        },
      },
    },
  };
</script>
<style>
  body { font-family: 'Inter', 'Segoe UI', sans-serif; }
  .nav-scroll::-webkit-scrollbar { display: none; }
  .nav-scroll { scrollbar-width: none; }
  .map-pattern {
    background-color: #EAF3EC;
    background-image:
      linear-gradient(#D7E5DA 1px, transparent 1px),
      linear-gradient(90deg, #D7E5DA 1px, transparent 1px);
    background-size: 24px 24px;
  }
</style>
</head>
<body class="bg-brand-50 antialiased">
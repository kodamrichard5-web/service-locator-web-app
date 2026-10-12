<?php
/**
 * admin/includes/header.php
 * Shared desktop shell for the admin panel: sidebar nav + topbar.
 * Self-contained under admin/ — does not depend on the customer/vendor
 * includes/ folder at all.
 * Expects: $page_title, $page_subtitle (optional), $active_admin_tab set by the
 * calling page before include. Relies on $pdo and $_SESSION['admin_id'] being
 * in scope already (every admin page requires db.php + calls require_admin()).
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/functions.php'; // admin's own copy

if (!isset($page_title)) {
    $page_title = 'Admin';
}
if (!isset($page_subtitle)) {
    $page_subtitle = '';
}
if (!isset($active_admin_tab)) {
    $active_admin_tab = '';
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

$nav_items = [
    'dashboard'  => ['label' => 'Dashboard',  'icon' => 'home',       'href' => 'dashboard.php'],
    'bookings'   => ['label' => 'Bookings',   'icon' => 'calendar',   'href' => 'bookings.php'],
    'providers'  => ['label' => 'Providers',  'icon' => 'briefcase',  'href' => 'providers.php'],
    'users'      => ['label' => 'Users',      'icon' => 'user-circle','href' => 'users.php'],
    'services'   => ['label' => 'Services',   'icon' => 'wrench',     'href' => 'services.php'],
    'categories' => ['label' => 'Categories', 'icon' => 'grid',       'href' => 'services.php'],
    'reviews'    => ['label' => 'Reviews',    'icon' => 'star',       'href' => 'reviews.php'],
    'payments'   => ['label' => 'Payments',   'icon' => 'cash',       'href' => 'payments.php'],
    'reports'    => ['label' => 'Reports',    'icon' => 'chart-bar',  'href' => 'reports.php'],
    'messages'   => ['label' => 'Messages',   'icon' => 'message',    'href' => 'messages.php'],
    'settings'   => ['label' => 'Settings',   'icon' => 'gear',       'href' => 'settings.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?> — ServiceLocator Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
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
</style>
</head>
<body class="bg-gray-50 antialiased">

<div class="flex min-h-screen">

  <!-- Sidebar -->
  <aside id="sidebar" class="w-64 bg-white border-r border-gray-200 flex flex-col fixed inset-y-0 left-0 z-30 -translate-x-full lg:translate-x-0 transition-transform duration-200">
    <div class="h-16 flex items-center gap-2.5 px-5 border-b border-gray-100 shrink-0">
      <div class="w-9 h-9 rounded-full bg-brand-500 flex items-center justify-center shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="white"/>
          <circle cx="12" cy="9" r="2.5" fill="#22C55E"/>
        </svg>
      </div>
      <div class="leading-tight">
        <p class="font-extrabold text-brand-900 text-sm">ServiceLocator</p>
        <p class="text-[10px] text-gray-400 font-semibold tracking-wide">ADMIN PANEL</p>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-1">
      <?php foreach ($nav_items as $key => $item):
          $active = $key === $active_admin_tab;
      ?>
        <a href="<?= $item['href'] ?>"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $active ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
          <?= svg_icon($item['icon'], 'w-5 h-5 shrink-0') ?>
          <?= $item['label'] ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="border-t border-gray-100 p-3 space-y-1 shrink-0">
      <div class="flex items-center gap-2.5 px-2 py-2">
        <div class="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold text-xs shrink-0">
          <?= htmlspecialchars(strtoupper(substr($admin_name, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8') ?></p>
          <p class="text-[11px] text-gray-400">Super Admin</p>
        </div>
      </div>
      <a href="../logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 transition-colors">
        <?= svg_icon('logout', 'w-5 h-5 shrink-0') ?>
        Logout
      </a>
    </div>
  </aside>

  <!-- Sidebar overlay (mobile) -->
  <div id="sidebarOverlay" class="fixed inset-0 bg-black/30 z-20 hidden lg:hidden"></div>

  <!-- Main -->
  <div class="flex-1 flex flex-col min-w-0 lg:ml-64">

    <!-- Topbar -->
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between gap-4 px-4 sm:px-6 sticky top-0 z-10">
      <div class="flex items-center gap-3 min-w-0">
        <button id="sidebarToggle" aria-label="Toggle menu" class="lg:hidden w-9 h-9 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-600 shrink-0">
          <?= svg_icon('menu', 'w-5 h-5') ?>
        </button>
        <div class="min-w-0">
          <h1 class="text-lg font-bold text-gray-900 truncate"><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></h1>
          <?php if ($page_subtitle): ?>
            <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($page_subtitle, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
        </div>
      </div>

      <div class="flex items-center gap-3 sm:gap-4 shrink-0">
        <div class="relative hidden sm:block">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
            <?= svg_icon('search', 'w-4 h-4') ?>
          </span>
          <input type="text" placeholder="Search anything..." disabled
            class="w-56 h-10 rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm text-gray-500 placeholder-gray-400">
        </div>
        <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 shrink-0">
          <?= svg_icon('bell', 'w-4.5 h-4.5') ?>
        </div>
        <div class="hidden sm:flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold text-xs">
            <?= htmlspecialchars(strtoupper(substr($admin_name, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
          </div>
          <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      </div>
    </header>

    <main class="flex-1 p-4 sm:p-6">
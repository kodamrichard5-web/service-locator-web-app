<?php
/**
 * admin/includes/auth_header.php
 * Minimal centered-card page shell for admin/setup.php and admin/login.php —
 * these run before an admin is authenticated, so they don't get the sidebar.
 * Self-contained under admin/ — does not depend on the customer/vendor
 * includes/ folder at all.
 * Expects: $page_title set by the calling page before include.
 */

if (!isset($page_title)) {
    $page_title = 'Admin';
}
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
<body class="bg-brand-50 antialiased">
<?php
/**
 * includes/functions.php
 * Shared helper functions used across ServiceLocator pages.
 * Auto-included by includes/header.php — no need to require it manually.
 */

/** Return an inline SVG icon (stroke-based, currentColor) by key. */
function svg_icon(string $name, string $classes = 'w-6 h-6'): string
{
    $icons = [
        'broom'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75l-8.5 8.5m0 0L3 19.5l7.25-2.75m-4.5-4.5l4.5 4.5m3.5-11.5l4.5 4.5-6 6-4.5-4.5 6-6z"/>',
        'wrench'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.75a4.5 4.5 0 10-5.885 5.885l-5.5 5.5a1.5 1.5 0 002.12 2.12l5.5-5.5A4.5 4.5 0 0014.25 6.75z"/>',
        'bolt'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3L4.5 13.5h6L10.5 21l9-10.5h-6L13.5 3z"/>',
        'leaf'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25c8.284 0 13.5-5.216 13.5-13.5v-3h-3c-8.284 0-13.5 5.216-13.5 13.5v3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25S8 14 14.5 8.75"/>',
        'bug'         => '<circle cx="12" cy="13" r="4.5" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.5V6m0 0L9.5 3.75M12 6l2.5-2.25M7.5 13H4m16 0h-3.5M8 17l-2.5 2.5M16 17l2.5 2.5M8.25 10L6 8.25m9.75 1.75L18 8.25"/>',
        'paintbrush'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 01-4.244-4.243L14.379 2.786a1.5 1.5 0 012.12 0l1.715 1.715a1.5 1.5 0 010 2.121L9.53 16.122z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122L6.5 19.5c-1 1-3.5 1.5-4.5.5s-.5-3.5.5-4.5l3.378-3.03"/>',
        'truck'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h11.25v9H2.25v-9zM13.5 11.25h3.522a1.5 1.5 0 011.396.955l1.128 2.907V17.25h-6.046"/><circle cx="6" cy="18" r="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="16.5" cy="18" r="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'tool'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.75a4.5 4.5 0 10-5.885 5.885l-5.5 5.5a1.5 1.5 0 002.12 2.12l5.5-5.5A4.5 4.5 0 0014.25 6.75z"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 4.5l1.5 1.5-3 3-1.5-1.5z"/>',
        'bell'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>',
        'shield'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l1.5 1.5 3.75-3.75M12 3l7.5 3v5.25c0 4.836-3.216 8.876-7.5 10.155-4.284-1.28-7.5-5.32-7.5-10.155V6l7.5-3z"/>',
        'search'      => '<circle cx="10.5" cy="10.5" r="6.75" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 20.25l-4.85-4.85"/>',
        'filter'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6h15M6.75 12h10.5M10.5 18h3"/>',
        'pin'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 6-7.5 11.25-7.5 11.25S4.5 16.5 4.5 10.5a7.5 7.5 0 1115 0z"/>',
        'star'        => '<path fill="currentColor" stroke="none" d="M12 2.25l2.917 6.062 6.583.897-4.833 4.615 1.204 6.626L12 17.14l-5.871 3.31 1.204-6.626L2.5 9.209l6.583-.897L12 2.25z"/>',
        'user-circle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 20.16v-.815c0-.98-.626-1.855-1.556-2.166a13.9 13.9 0 00-7.388 0c-.93.31-1.556 1.185-1.556 2.166v.815M12 12a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z"/><circle cx="12" cy="12" r="9.75" stroke-linecap="round" stroke-linejoin="round"/>',
        'home'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L22.28 12M4.5 9.75V19.5a1.5 1.5 0 001.5 1.5h3.75v-6.75h4.5V21H18a1.5 1.5 0 001.5-1.5V9.75"/>',
        'calendar'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M4.5 6h15A1.5 1.5 0 0121 7.5v12A1.5 1.5 0 0119.5 21h-15A1.5 1.5 0 013 19.5v-12A1.5 1.5 0 014.5 6z"/>',
        'message'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15.75L4.5 19.5V6a1.5 1.5 0 011.5-1.5h12A1.5 1.5 0 0119.5 6v8.25a1.5 1.5 0 01-1.5 1.5H8.25z"/>',
        'heart'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25s-7.5-4.612-9.75-9.075C.6 7.53 2.7 4.5 6 4.5c2.1 0 3.6 1.2 6 3.75 2.4-2.55 3.9-3.75 6-3.75 3.3 0 5.4 3.03 3.75 6.675C19.5 15.638 12 20.25 12 20.25z"/>',
        'briefcase'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 8.25h16.5A1.5 1.5 0 0121.75 9.75v9A1.5 1.5 0 0120.25 20.25H3.75a1.5 1.5 0 01-1.5-1.5v-9a1.5 1.5 0 011.5-1.5zM8.25 8.25V6a1.5 1.5 0 011.5-1.5h4.5A1.5 1.5 0 0115.75 6v2.25"/>',
        'arrow-left'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>',
        'check'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>',
        'clipboard'   => '<rect x="6" y="4.5" width="12" height="16.5" rx="2" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5V3.75A1.5 1.5 0 0110.5 2.25h3A1.5 1.5 0 0115 3.75V4.5M9 10.5h6M9 14h6M9 17.5h3"/>',
        'dollar'      => '<circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v10m2.5-7.5c0-1.104-1.12-2-2.5-2s-2.5.896-2.5 2c0 1.104 1.12 1.6 2.5 2s2.5.896 2.5 2c0 1.104-1.12 2-2.5 2s-2.5-.896-2.5-2"/>',
        'chevron-down'=> '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>',
    ];
    $path = $icons[$name] ?? $icons['tool'];
    return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.6\" class=\"{$classes}\">{$path}</svg>";
}

/** Render a row of 5 stars, filled up to $rating (supports halves visually via full-star rounding). */
function star_rating(float $rating, string $classes = 'w-4 h-4'): string
{
    $out = '';
    $rounded = round($rating);
    for ($i = 1; $i <= 5; $i++) {
        $color = $i <= $rounded ? 'text-yellow-400' : 'text-gray-200';
        $out .= svg_icon('star', $classes . ' ' . $color . ' inline-block');
    }
    return $out;
}

/** Return [bg_class, text_class, label] for a booking status. */
function status_badge_classes(string $status): array
{
    $status = strtolower($status);
    $map = [
        'pending'     => ['bg-yellow-100', 'text-yellow-700', 'Pending'],
        'accepted'    => ['bg-blue-100',   'text-blue-700',   'Accepted'],
        'in-progress' => ['bg-purple-100', 'text-purple-700', 'In-Progress'],
        'completed'   => ['bg-green-100',  'text-green-700',  'Completed'],
        'cancelled'   => ['bg-gray-100',   'text-gray-600',   'Cancelled'],
    ];
    return $map[$status] ?? ['bg-gray-100', 'text-gray-600', ucfirst($status)];
}

/** Format a DB date + time into a friendly string. */
function format_booking_datetime(?string $date, ?string $time): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date . ' ' . ($time ?? ''));
    if ($ts === false) {
        return htmlspecialchars($date . ' ' . ($time ?? ''), ENT_QUOTES, 'UTF-8');
    }
    return $time ? date('M j, Y \a\t g:i A', $ts) : date('M j, Y', $ts);
}

/** Build a colored initials avatar (used since vendors have no photo column yet). */
function initials_avatar(string $name, string $size = 'w-14 h-14', string $textSize = 'text-lg'): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(substr($parts[0] ?? '?', 0, 1) . substr($parts[1] ?? '', 0, 1));
    return "<div class=\"{$size} rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold {$textSize} shrink-0\">"
        . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8')
        . "</div>";
}

/** Human-friendly relative time, e.g. "20 mins ago", "3 hours ago", "2 days ago". */
function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 0) {
        $diff = 0;
    }
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return $m . ' min' . ($m === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago';
    }
    $d = (int) floor($diff / 86400);
    return $d . ' day' . ($d === 1 ? '' : 's') . ' ago';
}

/** Format a number as Ghanaian Cedi, e.g. "GHC 1,250". */
function format_ghc(float $amount, int $decimals = 0): string
{
    return 'GHC ' . number_format($amount, $decimals);
}

/** Classify how soon a scheduled booking is, for the Urgent/High Priority/Standard badge. */
function urgency_info(int $hoursUntil): array
{
    if ($hoursUntil <= 4) {
        return ['Urgent', 'bg-green-100', 'text-green-700'];
    }
    if ($hoursUntil <= 24) {
        return ['High Priority', 'bg-orange-500', 'text-white'];
    }
    return ['Standard', 'bg-blue-500', 'text-white'];
}

/** Time-of-day greeting, e.g. "Good morning,". */
function time_greeting(): string
{
    $hour = (int) date('G');
    if ($hour < 12) {
        return 'Good morning,';
    }
    if ($hour < 17) {
        return 'Good afternoon,';
    }
    return 'Good evening,';
}

/** Require a logged-in customer; redirect to login otherwise. Call BEFORE any HTML output. */
function require_customer(): void
{
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
        header('Location: ' . auth_relative_path() . 'auth/login.php');
        exit;
    }
}

/** Require a logged-in vendor; redirect to login otherwise. Call BEFORE any HTML output. */
function require_vendor(): void
{
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'vendor') {
        header('Location: ' . auth_relative_path() . 'auth/login.php');
        exit;
    }
}

/** Bottom-nav helper: active vs inactive icon color. Shared by includes/footer.php and includes/vendor_footer.php. */
function nav_classes(string $tab, string $active_tab): string
{
    return $tab === $active_tab ? 'text-brand-600' : 'text-gray-400 hover:text-gray-600';
}

/** Bottom-nav helper: active vs inactive label weight. Shared by includes/footer.php and includes/vendor_footer.php. */
function nav_label_classes(string $tab, string $active_tab): string
{
    return $tab === $active_tab ? 'text-[11px] font-semibold mt-0.5' : 'text-[11px] font-medium mt-0.5';
}

/** Best-effort relative path back to the project root from common subfolders. */
function auth_relative_path(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($script, '/customer/') !== false || strpos($script, '/vendor/') !== false) {
        return '../';
    }
    return '';
}
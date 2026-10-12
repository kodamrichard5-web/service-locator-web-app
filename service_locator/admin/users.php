<?php
/**
 * admin/users.php
 * Customer directory. Read-only for now — see note at the bottom of this file
 * about what a future suspend/ban action would need.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';
require_admin();

$search = trim($_GET['q'] ?? '');

$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = u.id) AS booking_count
        FROM users u WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (u.name LIKE :search OR u.email LIKE :search)";
    $params['search'] = '%' . $search . '%';
}
$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'Users';
$page_subtitle = 'Customers registered on the platform';
$active_admin_tab = 'users';
include 'includes/header.php';
?>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">

  <div class="p-5 border-b border-gray-100">
    <form method="GET" class="relative max-w-sm">
      <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
        <?= svg_icon('search', 'w-4 h-4') ?>
      </span>
      <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search users..."
        class="w-full h-10 rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
    </form>
  </div>

  <?php if (empty($users)): ?>
    <div class="p-12 text-center">
      <span class="w-14 h-14 rounded-full bg-brand-50 flex items-center justify-center mx-auto mb-3">
        <?= svg_icon('user-circle', 'w-6 h-6 text-brand-500') ?>
      </span>
      <p class="text-sm font-semibold text-gray-700">No users found</p>
      <p class="text-sm text-gray-400 mt-1">Customers will appear here once they register.</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
            <th class="font-medium py-3 px-5">Customer</th>
            <th class="font-medium py-3 px-2">Phone</th>
            <th class="font-medium py-3 px-2">Address</th>
            <th class="font-medium py-3 px-2">Bookings</th>
            <th class="font-medium py-3 px-5">Joined</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr class="border-b border-gray-50 last:border-0">
              <td class="py-3 px-5">
                <div class="flex items-center gap-2.5">
                  <?= initials_avatar($u['name'], 'w-8 h-8', 'text-[11px]') ?>
                  <div class="min-w-0">
                    <p class="font-medium text-gray-800 truncate"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></p>
                  </div>
                </div>
              </td>
              <td class="py-3 px-2 text-gray-600 whitespace-nowrap"><?= htmlspecialchars($u['phone'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="py-3 px-2 text-gray-600 max-w-[220px] truncate"><?= htmlspecialchars($u['address'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="py-3 px-2 text-gray-600 whitespace-nowrap"><?= (int) $u['booking_count'] ?></td>
              <td class="py-3 px-5 text-gray-500 whitespace-nowrap text-xs"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>

<!--
  Note: this page is intentionally read-only. Suspending a customer account
  would need a `status` column added to the `users` table (mirroring the one
  `vendors` already has), plus a check in auth/login.php and require_customer()
  to block suspended accounts from logging in. Not built yet — say the word
  if you want that added.
-->

<?php include 'includes/footer.php'; ?>
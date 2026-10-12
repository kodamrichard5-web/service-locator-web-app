<?php
/**
 * customer/profile.php
 * "My Profile" menu hub — profile card + navigation list.
 * The actual edit forms live in edit-profile.php now; this page is a hub.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$customer_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT name, email, photo_url FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $customer_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../logout.php');
    exit;
}

// Notifications badge — real signal (unread messages from vendors), not a fabricated count.
$unreadStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM messages WHERE customer_id = :id AND sender_role = 'vendor' AND is_read = 0"
);
$unreadStmt->execute(['id' => $customer_id]);
$unreadCount = (int) $unreadStmt->fetchColumn();

$page_title = 'My Profile';
$active_tab = 'profile';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl bg-white min-h-screen shadow-sm">

    <header class="px-5 pt-6 pb-4">
      <h1 class="text-3xl font-extrabold text-brand-900 text-center">My Profile</h1>
    </header>

    <!-- Profile card -->
    <div class="px-5">
      <a href="edit-profile.php" class="flex items-center gap-4 bg-brand-50 rounded-2xl p-5 hover:bg-brand-100 transition-colors">
        <?php if (!empty($user['photo_url'])): ?>
          <img src="<?= htmlspecialchars($user['photo_url'], ENT_QUOTES, 'UTF-8') ?>" alt=""
               class="w-16 h-16 rounded-full object-cover border-2 border-white shrink-0">
        <?php else: ?>
          <?= initials_avatar($user['name'], 'w-16 h-16', 'text-xl') ?>
        <?php endif; ?>
        <div class="min-w-0 flex-1">
          <p class="text-xl font-extrabold text-brand-900 truncate"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></p>
          <p class="text-sm text-gray-600 truncate"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?= svg_icon('chevron-right', 'w-5 h-5 text-gray-400 shrink-0') ?>
      </a>
    </div>

    <!-- Menu -->
    <div class="px-5 mt-6 pb-6">
      <div class="divide-y divide-gray-100 border-t border-b border-gray-100">

        <a href="bookings.php" class="flex items-center gap-4 py-4">
          <?= svg_icon('calendar', 'w-6 h-6 text-brand-600') ?>
          <span class="flex-1 text-gray-800 font-medium">My Bookings</span>
          <?= svg_icon('chevron-right', 'w-5 h-5 text-gray-300') ?>
        </a>

        <a href="payment-methods.php" class="flex items-center gap-4 py-4">
          <?= svg_icon('credit-card', 'w-6 h-6 text-brand-600') ?>
          <span class="flex-1 text-gray-800 font-medium">Payment Methods</span>
          <?= svg_icon('chevron-right', 'w-5 h-5 text-gray-300') ?>
        </a>

        <a href="addresses.php" class="flex items-center gap-4 py-4">
          <?= svg_icon('pin', 'w-6 h-6 text-brand-600') ?>
          <span class="flex-1 text-gray-800 font-medium">Saved Addresses</span>
          <?= svg_icon('chevron-right', 'w-5 h-5 text-gray-300') ?>
        </a>

        <a href="messages.php" class="flex items-center gap-4 py-4">
          <?= svg_icon('bell', 'w-6 h-6 text-brand-600') ?>
          <span class="flex-1 text-gray-800 font-medium">Notifications</span>
          <?php if ($unreadCount > 0): ?>
            <span class="min-w-[1.375rem] h-5.5 px-1.5 rounded-full bg-red-500 text-white text-[11px] font-bold flex items-center justify-center">
              <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
            </span>
          <?php endif; ?>
          <?= svg_icon('chevron-right', 'w-5 h-5 text-gray-300') ?>
        </a>

        <a href="favorites.php" class="flex items-center gap-4 py-4">
          <?= svg_icon('heart', 'w-6 h-6 text-brand-600') ?>
          <span class="flex-1 text-gray-800 font-medium">Favorites</span>
          <?= svg_icon('chevron-right', 'w-5 h-5 text-gray-300') ?>
        </a>

        <a href="help.php" class="flex items-center gap-4 py-4">
          <?= svg_icon('help-circle', 'w-6 h-6 text-brand-600') ?>
          <span class="flex-1 text-gray-800 font-medium">Help &amp; Support</span>
          <?= svg_icon('chevron-right', 'w-5 h-5 text-gray-300') ?>
        </a>

        <a href="terms.php" class="flex items-center gap-4 py-4">
          <?= svg_icon('document', 'w-6 h-6 text-brand-600') ?>
          <span class="flex-1 text-gray-800 font-medium">Terms</span>
          <?= svg_icon('chevron-right', 'w-5 h-5 text-gray-300') ?>
        </a>

        <a href="../logout.php" class="flex items-center gap-4 py-4">
          <?= svg_icon('logout', 'w-6 h-6 text-red-500') ?>
          <span class="flex-1 text-red-500 font-medium">Logout</span>
        </a>

      </div>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
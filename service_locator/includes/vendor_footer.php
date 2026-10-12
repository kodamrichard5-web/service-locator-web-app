<?php
/**
 * includes/vendor_footer.php
 * Bottom navigation for the technician (vendor) side of the app.
 * Expects (optional): $active_tab set by the calling page before include.
 * Valid values: 'dashboard' | 'jobs' | 'earnings' | 'messages' | 'profile'
 *
 * Relies on $pdo and $_SESSION['user_id'] already being in scope, since every
 * vendor page requires db.php and calls require_vendor() before including this.
 */

if (!isset($active_tab)) {
    $active_tab = '';
}

$footer_unread = 0;
if (isset($pdo, $_SESSION['user_id'])) {
    $footer_unread_stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM messages WHERE vendor_id = :id AND sender_role = 'customer' AND is_read = 0"
    );
    $footer_unread_stmt->execute(['id' => $_SESSION['user_id']]);
    $footer_unread = (int) $footer_unread_stmt->fetchColumn();
}
?>

<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-2px_8px_rgba(0,0,0,0.04)]">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl grid grid-cols-5 nav-scroll">

    <a href="dashboard.php" class="flex flex-col items-center justify-center py-2.5 <?= nav_classes('dashboard', $active_tab) ?>">
      <?= svg_icon('home', 'w-6 h-6') ?>
      <span class="<?= nav_label_classes('dashboard', $active_tab) ?>">Dashboard</span>
    </a>

    <a href="bookings.php" class="flex flex-col items-center justify-center py-2.5 <?= nav_classes('jobs', $active_tab) ?>">
      <?= svg_icon('briefcase', 'w-6 h-6') ?>
      <span class="<?= nav_label_classes('jobs', $active_tab) ?>">Jobs</span>
    </a>

    <a href="earnings.php" class="flex flex-col items-center justify-center py-2.5 <?= nav_classes('earnings', $active_tab) ?>">
      <?= svg_icon('cash', 'w-6 h-6') ?>
      <span class="<?= nav_label_classes('earnings', $active_tab) ?>">Earnings</span>
    </a>

    <a href="messages.php" class="relative flex flex-col items-center justify-center py-2.5 <?= nav_classes('messages', $active_tab) ?>">
      <span class="relative">
        <?= svg_icon('message', 'w-6 h-6') ?>
        <?php if ($footer_unread > 0): ?>
          <span class="absolute -top-1.5 -right-2 min-w-[1.125rem] h-[1.125rem] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
            <?= $footer_unread > 9 ? '9+' : $footer_unread ?>
          </span>
        <?php endif; ?>
      </span>
      <span class="<?= nav_label_classes('messages', $active_tab) ?>">Messages</span>
    </a>

    <a href="profile.php" class="flex flex-col items-center justify-center py-2.5 <?= nav_classes('profile', $active_tab) ?>">
      <?= svg_icon('user-circle', 'w-6 h-6') ?>
      <span class="<?= nav_label_classes('profile', $active_tab) ?>">Profile</span>
    </a>

  </div>
</nav>

</body>
</html>
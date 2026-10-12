<?php
/**
 * includes/footer.php
 * Shared bottom navigation + closing markup for logged-in customer pages.
 * Expects (optional): $active_tab set by the calling page before include.
 * Valid values: 'home' | 'bookings' | 'messages' | 'favorites' | 'profile'
 */

if (!isset($active_tab)) {
    $active_tab = '';
}
?>

<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-2px_8px_rgba(0,0,0,0.04)]">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl grid grid-cols-5 nav-scroll">

    <a href="home.php" class="flex flex-col items-center justify-center py-2.5 <?= nav_classes('home', $active_tab) ?>">
      <?= svg_icon('home', 'w-6 h-6') ?>
      <span class="<?= nav_label_classes('home', $active_tab) ?>">Home</span>
    </a>

    <a href="bookings.php" class="flex flex-col items-center justify-center py-2.5 <?= nav_classes('bookings', $active_tab) ?>">
      <?= svg_icon('calendar', 'w-6 h-6') ?>
      <span class="<?= nav_label_classes('bookings', $active_tab) ?>">Bookings</span>
    </a>

    <a href="messages.php" class="flex flex-col items-center justify-center py-2.5 <?= nav_classes('messages', $active_tab) ?>">
      <?= svg_icon('message', 'w-6 h-6') ?>
      <span class="<?= nav_label_classes('messages', $active_tab) ?>">Messages</span>
    </a>

    <a href="favorites.php" class="flex flex-col items-center justify-center py-2.5 <?= nav_classes('favorites', $active_tab) ?>">
      <?= svg_icon('heart', 'w-6 h-6') ?>
      <span class="<?= nav_label_classes('favorites', $active_tab) ?>">Favorites</span>
    </a>

    <a href="profile.php" class="flex flex-col items-center justify-center py-2.5 <?= nav_classes('profile', $active_tab) ?>">
      <?= svg_icon('user-circle', 'w-6 h-6') ?>
      <span class="<?= nav_label_classes('profile', $active_tab) ?>">Profile</span>
    </a>

  </div>
</nav>

</body>
</html>
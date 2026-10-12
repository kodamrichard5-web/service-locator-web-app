<?php
/**
 * vendor/messages.php
 * Lists conversations the vendor has with customers — mirrors customer/messages.php,
 * reversed direction.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_vendor();

$vendor_id = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT
        u.id AS customer_id,
        u.name AS customer_name,
        (SELECT m2.message FROM messages m2
            WHERE m2.vendor_id = :vid1 AND m2.customer_id = u.id
            ORDER BY m2.created_at DESC LIMIT 1)       AS last_message,
        (SELECT m3.created_at FROM messages m3
            WHERE m3.vendor_id = :vid2 AND m3.customer_id = u.id
            ORDER BY m3.created_at DESC LIMIT 1)       AS last_time,
        (SELECT COUNT(*) FROM messages m4
            WHERE m4.vendor_id = :vid3 AND m4.customer_id = u.id
              AND m4.sender_role = 'customer' AND m4.is_read = 0) AS unread_count
     FROM users u
     WHERE u.id IN (
         SELECT DISTINCT customer_id FROM messages WHERE vendor_id = :vid4
         UNION
         SELECT DISTINCT customer_id FROM bookings WHERE vendor_id = :vid5
     )
     ORDER BY last_time IS NULL, last_time DESC"
);
$stmt->execute([
    'vid1' => $vendor_id, 'vid2' => $vendor_id, 'vid3' => $vendor_id,
    'vid4' => $vendor_id, 'vid5' => $vendor_id,
]);
$conversations = $stmt->fetchAll();

$page_title = 'Messages';
$active_tab = 'messages';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl">

    <div class="px-5 pt-6">
      <div class="bg-white rounded-2xl shadow-sm py-6 px-5">
        <h1 class="text-3xl font-extrabold text-brand-900 text-center">Messages</h1>
      </div>
    </div>

    <div class="px-5 mt-6">

      <?php if (empty($conversations)): ?>

        <div class="bg-white rounded-2xl shadow-sm p-10 flex flex-col items-center text-center">
          <span class="w-16 h-16 rounded-full bg-brand-50 flex items-center justify-center mb-4">
            <?= svg_icon('message', 'w-7 h-7 text-brand-500') ?>
          </span>
          <h3 class="text-xl font-bold text-gray-800">No messages yet</h3>
          <p class="text-gray-500 mt-2">Conversations with your customers will appear here</p>
        </div>

      <?php else: ?>

        <div class="space-y-2 pb-4">
          <?php foreach ($conversations as $c): ?>
            <a href="chat.php?customer_id=<?= (int) $c['customer_id'] ?>"
               class="flex items-center gap-3 bg-white border border-gray-100 rounded-2xl shadow-sm p-3.5 hover:shadow-md transition-shadow">
              <?= initials_avatar($c['customer_name'], 'w-12 h-12', 'text-sm') ?>
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                  <p class="font-bold text-gray-900 truncate"><?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                  <?php if ($c['last_time']): ?>
                    <span class="text-[11px] text-gray-400 shrink-0"><?= date('M j', strtotime($c['last_time'])) ?></span>
                  <?php endif; ?>
                </div>
                <p class="text-sm text-gray-500 truncate mt-0.5">
                  <?= $c['last_message']
                      ? htmlspecialchars($c['last_message'], ENT_QUOTES, 'UTF-8')
                      : '<span class="italic text-gray-400">No messages yet</span>' ?>
                </p>
              </div>
              <?php if ((int) $c['unread_count'] > 0): ?>
                <span class="shrink-0 min-w-[1.375rem] h-5.5 px-1.5 rounded-full bg-brand-500 text-white text-[11px] font-bold flex items-center justify-center">
                  <?= (int) $c['unread_count'] ?>
                </span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>

    </div>
  </div>
</div>

<?php include '../includes/vendor_footer.php'; ?>
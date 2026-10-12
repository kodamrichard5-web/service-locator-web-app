<?php
/**
 * customer/messages.php
 * Lists conversations the customer has with vendors — either from an existing
 * message thread, or from a booking (so a fresh booking always shows up here
 * even before the first message is sent).
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$customer_id = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT
        v.id AS vendor_id,
        v.name AS vendor_name,
        (SELECT m2.message FROM messages m2
            WHERE m2.customer_id = :cid1 AND m2.vendor_id = v.id
            ORDER BY m2.created_at DESC LIMIT 1)       AS last_message,
        (SELECT m3.created_at FROM messages m3
            WHERE m3.customer_id = :cid2 AND m3.vendor_id = v.id
            ORDER BY m3.created_at DESC LIMIT 1)       AS last_time,
        (SELECT COUNT(*) FROM messages m4
            WHERE m4.customer_id = :cid3 AND m4.vendor_id = v.id
              AND m4.sender_role = 'vendor' AND m4.is_read = 0) AS unread_count
     FROM vendors v
     WHERE v.id IN (
         SELECT DISTINCT vendor_id FROM messages WHERE customer_id = :cid4
         UNION
         SELECT DISTINCT vendor_id FROM bookings WHERE customer_id = :cid5
     )
     ORDER BY last_time IS NULL, last_time DESC"
);
$stmt->execute([
    'cid1' => $customer_id, 'cid2' => $customer_id, 'cid3' => $customer_id,
    'cid4' => $customer_id, 'cid5' => $customer_id,
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
          <p class="text-gray-500 mt-2 mb-6">Book a service to start chatting with a technician</p>
          <a href="home.php"
             class="bg-brand-500 hover:bg-brand-700 text-white font-bold px-8 py-3 rounded-xl transition-colors duration-200 shadow-md shadow-brand-500/25">
            Browse Services
          </a>
        </div>

      <?php else: ?>

        <div class="space-y-2 pb-4">
          <?php foreach ($conversations as $c): ?>
            <a href="chat.php?vendor_id=<?= (int) $c['vendor_id'] ?>"
               class="flex items-center gap-3 bg-white border border-gray-100 rounded-2xl shadow-sm p-3.5 hover:shadow-md transition-shadow">
              <?= initials_avatar($c['vendor_name'], 'w-12 h-12', 'text-sm') ?>
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                  <p class="font-bold text-gray-900 truncate"><?= htmlspecialchars($c['vendor_name'], ENT_QUOTES, 'UTF-8') ?></p>
                  <?php if ($c['last_time']): ?>
                    <span class="text-[11px] text-gray-400 shrink-0"><?= date('M j', strtotime($c['last_time'])) ?></span>
                  <?php endif; ?>
                </div>
                <p class="text-sm text-gray-500 truncate mt-0.5">
                  <?= $c['last_message']
                      ? htmlspecialchars($c['last_message'], ENT_QUOTES, 'UTF-8')
                      : '<span class="italic text-gray-400">Say hello to get started</span>' ?>
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

<?php include '../includes/footer.php'; ?>
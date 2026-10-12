<?php
/**
 * admin/messages.php
 * Aggregate view of platform messaging activity — counts and most active
 * conversations, NOT a transcript reader. Deliberately scoped this way:
 * reading private customer↔vendor conversations by default raises privacy
 * concerns that are out of place for a first version of admin oversight.
 * Say the word if you want a full transcript viewer added later.
 */

session_start();
require_once '../db.php';
require_once 'includes/functions.php';
require_admin();

$totalMessages = (int) $pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE DATE(created_at) = CURDATE()");
$messagesToday = (int) $stmt->fetchColumn();

$activeConversations = $pdo->query(
    "SELECT customer_id, vendor_id, COUNT(*) AS message_count, MAX(created_at) AS last_message_at
     FROM messages
     GROUP BY customer_id, vendor_id
     ORDER BY message_count DESC
     LIMIT 10"
)->fetchAll();

// Resolve names for the list above.
$conversationRows = [];
foreach ($activeConversations as $c) {
    $u = $pdo->prepare('SELECT name FROM users WHERE id = :id LIMIT 1');
    $u->execute(['id' => $c['customer_id']]);
    $v = $pdo->prepare('SELECT name FROM vendors WHERE id = :id LIMIT 1');
    $v->execute(['id' => $c['vendor_id']]);
    $conversationRows[] = [
        'customer_name' => $u->fetchColumn() ?: 'Unknown',
        'vendor_name'   => $v->fetchColumn() ?: 'Unknown',
        'message_count' => (int) $c['message_count'],
        'last_message_at' => $c['last_message_at'],
    ];
}

$page_title = 'Messages';
$page_subtitle = 'Platform messaging activity overview';
$active_admin_tab = 'messages';
include 'includes/header.php';
?>

<div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
  <p class="text-sm text-blue-800">This shows activity counts only, not message contents — customer and provider conversations stay private.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <p class="text-xs text-gray-400">Total Messages Sent</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= number_format($totalMessages) ?></p>
  </div>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <p class="text-xs text-gray-400">Sent Today</p>
    <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= number_format($messagesToday) ?></p>
  </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
  <div class="p-5 border-b border-gray-100">
    <h3 class="font-bold text-gray-800">Most Active Conversations</h3>
  </div>
  <?php if (empty($conversationRows)): ?>
    <div class="p-12 text-center">
      <span class="w-14 h-14 rounded-full bg-brand-50 flex items-center justify-center mx-auto mb-3">
        <?= svg_icon('message', 'w-6 h-6 text-brand-500') ?>
      </span>
      <p class="text-sm font-semibold text-gray-700">No messages yet</p>
    </div>
  <?php else: ?>
    <div class="divide-y divide-gray-50">
      <?php foreach ($conversationRows as $c): ?>
        <div class="flex items-center justify-between p-4">
          <div>
            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?> ↔ <?= htmlspecialchars($c['vendor_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-gray-400">Last activity: <?= time_ago($c['last_message_at']) ?></p>
          </div>
          <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600"><?= $c['message_count'] ?> messages</span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
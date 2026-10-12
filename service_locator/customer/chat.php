<?php
/**
 * customer/chat.php
 * Simple message thread between the logged-in customer and one vendor.
 * Reloads on send (no websockets/polling) — good enough for a first version.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$customer_id = $_SESSION['user_id'];
$vendor_id = filter_input(INPUT_GET, 'vendor_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);

if (!$vendor_id) {
    header('Location: messages.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, name FROM vendors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $vendor_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    header('Location: messages.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $text = trim($_POST['message'] ?? '');

    if ($text === '') {
        $errors[] = 'Please type a message before sending.';
    } elseif (strlen($text) > 2000) {
        $errors[] = 'Message is too long.';
    }

    if (empty($errors)) {
        try {
            $insert = $pdo->prepare(
                "INSERT INTO messages (customer_id, vendor_id, sender_role, message, is_read)
                 VALUES (:customer_id, :vendor_id, 'customer', :message, 0)"
            );
            $insert->execute([
                'customer_id' => $customer_id,
                'vendor_id'   => $vendor_id,
                'message'     => $text,
            ]);

            header('Location: chat.php?vendor_id=' . $vendor_id);
            exit;
        } catch (PDOException $e) {
            error_log('chat.php insert failed: ' . $e->getMessage());
            $errors[] = 'Your message could not be sent. Please try again.';
        }
    }
}

// Mark any unread messages from this vendor as read now that the customer is viewing the thread.
$markRead = $pdo->prepare(
    "UPDATE messages SET is_read = 1
     WHERE customer_id = :customer_id AND vendor_id = :vendor_id
       AND sender_role = 'vendor' AND is_read = 0"
);
$markRead->execute(['customer_id' => $customer_id, 'vendor_id' => $vendor_id]);

$stmt = $pdo->prepare(
    "SELECT sender_role, message, created_at
     FROM messages
     WHERE customer_id = :customer_id AND vendor_id = :vendor_id
     ORDER BY created_at ASC"
);
$stmt->execute(['customer_id' => $customer_id, 'vendor_id' => $vendor_id]);
$thread = $stmt->fetchAll();

$page_title = 'Chat with ' . $vendor['name'];
$active_tab = 'messages';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24 flex flex-col">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl w-full flex flex-col flex-1">

    <!-- Header -->
    <header class="px-5 pt-6 pb-3 flex items-center gap-3 bg-brand-50 sticky top-0 z-10">
      <a href="messages.php" aria-label="Back" class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-brand-700 shrink-0">
        <?= svg_icon('arrow-left', 'w-5 h-5') ?>
      </a>
      <?= initials_avatar($vendor['name'], 'w-10 h-10', 'text-xs') ?>
      <h1 class="text-lg font-bold text-brand-900 truncate"><?= htmlspecialchars($vendor['name'], ENT_QUOTES, 'UTF-8') ?></h1>
    </header>

    <!-- Thread -->
    <div class="flex-1 px-5 py-4 space-y-3 overflow-y-auto">
      <?php if (empty($thread)): ?>
        <p class="text-center text-sm text-gray-400 mt-10">Say hello to start the conversation</p>
      <?php else: ?>
        <?php foreach ($thread as $msg):
            $isCustomer = $msg['sender_role'] === 'customer';
        ?>
          <div class="flex <?= $isCustomer ? 'justify-end' : 'justify-start' ?>">
            <div class="max-w-[75%] <?= $isCustomer
                ? 'bg-brand-500 text-white rounded-2xl rounded-br-sm'
                : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-sm' ?> px-4 py-2.5 shadow-sm">
              <p class="text-sm whitespace-pre-wrap break-words"><?= htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8') ?></p>
              <p class="text-[10px] mt-1 <?= $isCustomer ? 'text-brand-50/80' : 'text-gray-400' ?>">
                <?= date('g:i A \o\n M j', strtotime($msg['created_at'])) ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="px-5">
        <p class="text-sm text-red-600 mb-2"><?= htmlspecialchars($errors[0], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    <?php endif; ?>

    <!-- Composer -->
    <form action="" method="POST" class="px-5 pb-6 pt-2 flex items-center gap-2 bg-white border-t border-gray-100">
      <input type="hidden" name="vendor_id" value="<?= (int) $vendor['id'] ?>">
      <input
        type="text" name="message" placeholder="Type a message..." autocomplete="off" required
        class="flex-1 h-12 rounded-full border border-gray-300 bg-gray-50 px-4 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white"
      >
      <button type="submit" name="send_message" aria-label="Send"
        class="w-12 h-12 rounded-full bg-brand-500 hover:bg-brand-700 text-white flex items-center justify-center shrink-0 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.75 3.75 20.25 12 3.75 20.25 6 12zm0 0h9"/>
        </svg>
      </button>
    </form>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
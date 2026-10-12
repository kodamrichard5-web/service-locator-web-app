<?php
/**
 * vendor/bookings.php
 * Lists all bookings for the logged-in vendor and lets them move a booking
 * through its status lifecycle: pending -> accepted -> in-progress -> completed
 * (or cancelled from pending/accepted).
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_vendor();

$vendor_id = $_SESSION['user_id'];

// Allowed forward transitions, keyed by current status.
$allowed_transitions = [
    'pending'     => ['accepted', 'cancelled'],
    'accepted'    => ['in-progress', 'cancelled'],
    'in-progress' => ['completed'],
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['new_status'] ?? '';

    if ($booking_id) {
        $check = $pdo->prepare('SELECT status FROM bookings WHERE id = :id AND vendor_id = :vid LIMIT 1');
        $check->execute(['id' => $booking_id, 'vid' => $vendor_id]);
        $current = $check->fetchColumn();

        if ($current === false) {
            $errors[] = 'Booking not found.';
        } elseif (!isset($allowed_transitions[$current]) || !in_array($new_status, $allowed_transitions[$current], true)) {
            $errors[] = 'That status change is not allowed.';
        } else {
            $update = $pdo->prepare('UPDATE bookings SET status = :status WHERE id = :id AND vendor_id = :vid');
            $update->execute(['status' => $new_status, 'id' => $booking_id, 'vid' => $vendor_id]);
        }
    }

    if (empty($errors)) {
        header('Location: bookings.php');
        exit;
    }
}

$stmt = $pdo->prepare(
    "SELECT b.id, b.status, b.booking_date, b.booking_time, b.address, b.notes,
            u.id AS customer_id, u.name AS customer_name, u.phone AS customer_phone,
            s.name AS service_name, s.icon AS service_icon
     FROM bookings b
     JOIN users u    ON u.id = b.customer_id
     JOIN services s ON s.id = b.service_id
     WHERE b.vendor_id = :vid
     ORDER BY
        CASE b.status
            WHEN 'pending' THEN 1
            WHEN 'accepted' THEN 2
            WHEN 'in-progress' THEN 3
            WHEN 'completed' THEN 4
            WHEN 'cancelled' THEN 5
        END,
        b.created_at DESC"
);
$stmt->execute(['vid' => $vendor_id]);
$bookings = $stmt->fetchAll();

$page_title = 'Jobs';
$active_tab = 'jobs';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl">

    <div class="px-5 pt-6">
      <div class="bg-white rounded-2xl shadow-sm py-6 px-5">
        <h1 class="text-3xl font-extrabold text-brand-900 text-center">Jobs</h1>
      </div>
    </div>

    <div class="px-5 mt-6">

      <?php if (!empty($errors)): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
          <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if (empty($bookings)): ?>

        <div class="bg-white rounded-2xl shadow-sm p-10 flex flex-col items-center text-center">
          <span class="w-16 h-16 rounded-full bg-brand-50 flex items-center justify-center mb-4">
            <?= svg_icon('calendar', 'w-7 h-7 text-brand-500') ?>
          </span>
          <h3 class="text-xl font-bold text-gray-800">No bookings yet</h3>
          <p class="text-gray-500 mt-2">Customers will appear here once they book you</p>
        </div>

      <?php else: ?>

        <div class="space-y-4 pb-4">
          <?php foreach ($bookings as $booking):
              [$badge_bg, $badge_text, $badge_label] = status_badge_classes($booking['status']);
              $when = format_booking_datetime($booking['booking_date'], $booking['booking_time']);
              $next = $allowed_transitions[$booking['status']] ?? [];
          ?>
            <div class="bg-white rounded-2xl shadow-sm p-4">

              <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                  <span class="w-11 h-11 rounded-full bg-brand-100 flex items-center justify-center shrink-0">
                    <?= svg_icon($booking['service_icon'], 'w-5 h-5 text-brand-700') ?>
                  </span>
                  <div class="min-w-0">
                    <p class="font-bold text-gray-900 truncate"><?= htmlspecialchars($booking['notes'] !== null && $booking['notes'] !== '' ? $booking['notes'] : $booking['service_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-gray-500 truncate">for <?= htmlspecialchars($booking['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                  </div>
                </div>
                <span class="shrink-0 text-xs font-semibold px-3 py-1 rounded-full <?= $badge_bg ?> <?= $badge_text ?>">
                  <?= htmlspecialchars($badge_label, ENT_QUOTES, 'UTF-8') ?>
                </span>
              </div>

              <div class="mt-3 space-y-1.5">
                <?php if ($when): ?>
                  <p class="text-sm text-gray-600 flex items-center gap-1.5">
                    <?= svg_icon('calendar', 'w-4 h-4 text-brand-600 shrink-0') ?>
                    <?= htmlspecialchars($when, ENT_QUOTES, 'UTF-8') ?>
                  </p>
                <?php endif; ?>
                <?php if (!empty($booking['address'])): ?>
                  <p class="text-sm text-gray-600 flex items-center gap-1.5">
                    <?= svg_icon('pin', 'w-4 h-4 text-brand-600 shrink-0') ?>
                    <span class="truncate"><?= htmlspecialchars($booking['address'], ENT_QUOTES, 'UTF-8') ?></span>
                  </p>
                <?php endif; ?>
                <?php if (!empty($booking['customer_phone'])): ?>
                  <p class="text-sm text-gray-600 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-4 h-4 text-brand-600 shrink-0">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0-3.4 3.6-3.4 3.6-1.75 0 1.2.75 2.4 1.3 3.15.5.7.35 1.35-.15 1.85l-.85.85a11.5 11.5 0 005.6 5.6l.85-.85c.5-.5 1.15-.65 1.85-.15.75.55 1.95 1.3 3.15 1.3 1.65 0 1.65 3.6-1.75 3.6-6.075 0-13.6-7.525-13.6-13.6z"/>
                    </svg>
                    <?= htmlspecialchars($booking['customer_phone'], ENT_QUOTES, 'UTF-8') ?>
                  </p>
                <?php endif; ?>
              </div>

              <div class="mt-4 flex items-center gap-2 flex-wrap">
                <?php foreach ($next as $status_option):
                    $labels = [
                        'accepted'    => ['Accept', 'bg-brand-500 hover:bg-brand-700 text-white'],
                        'cancelled'   => ['Decline', 'border border-red-300 text-red-600 hover:bg-red-50'],
                        'in-progress' => ['Start Job', 'bg-brand-500 hover:bg-brand-700 text-white'],
                        'completed'   => ['Mark Complete', 'bg-brand-500 hover:bg-brand-700 text-white'],
                    ];
                    [$label, $classes] = $labels[$status_option] ?? [ucfirst($status_option), 'bg-gray-200 text-gray-700'];
                ?>
                  <form action="" method="POST" class="flex-1 min-w-[120px]">
                    <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                    <input type="hidden" name="new_status" value="<?= htmlspecialchars($status_option, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" name="update_status"
                      class="w-full text-center text-sm font-semibold py-2.5 rounded-xl transition-colors duration-200 <?= $classes ?>">
                      <?= $label ?>
                    </button>
                  </form>
                <?php endforeach; ?>

                <a href="chat.php?customer_id=<?= (int) $booking['customer_id'] ?>"
                   class="flex-1 min-w-[120px] text-center text-sm font-semibold border border-brand-500 text-brand-600 hover:bg-brand-50 py-2.5 rounded-xl transition-colors duration-200">
                  Chat
                </a>
              </div>

            </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>

    </div>
  </div>
</div>

<?php include '../includes/vendor_footer.php'; ?>
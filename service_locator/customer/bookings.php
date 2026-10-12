<?php
/**
 * customer/bookings.php
 * "My Bookings" — lists the logged-in customer's bookings.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_customer();

$customer_id = $_SESSION['user_id'];
$bookings = [];
$fetch_error = null;

try {
    $stmt = $pdo->prepare(
        "SELECT
            b.id            AS booking_id,
            b.status        AS status,
            b.booking_date  AS booking_date,
            b.booking_time  AS booking_time,
            b.address       AS address,
            v.id            AS vendor_id,
            v.name          AS vendor_name,
            s.id            AS service_id,
            s.name          AS service_name,
            r.id            AS review_id
         FROM bookings b
         JOIN vendors  v ON v.id = b.vendor_id
         JOIN services s ON s.id = b.service_id
         LEFT JOIN reviews r ON r.booking_id = b.id
         WHERE b.customer_id = :customer_id
         ORDER BY b.booking_date DESC, b.booking_time DESC"
    );
    $stmt->execute(['customer_id' => $customer_id]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('bookings.php query failed: ' . $e->getMessage());
    $fetch_error = 'We could not load your bookings right now. Please try again shortly.';
}

$page_title = 'My Bookings';
$active_tab = 'bookings';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-3xl">

    <!-- Header -->
    <div class="px-5 pt-6">
      <div class="bg-white rounded-2xl shadow-sm py-6 px-5">
        <h1 class="text-3xl font-extrabold text-brand-900 text-center">My Bookings</h1>
      </div>
    </div>

    <div class="px-5 mt-6">

      <?php if ($fetch_error): ?>

        <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
          <p class="text-sm text-red-600 font-medium"><?= htmlspecialchars($fetch_error, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

      <?php elseif (empty($bookings)): ?>

        <!-- Empty state -->
        <div class="bg-white rounded-2xl shadow-sm p-8 flex flex-col items-center text-center">
          <img
            src="../assets/images/bookings_screen_green.webp"
            alt=""
            class="w-40 h-40 object-contain mb-2 opacity-90"
            onerror="this.style.display='none'"
          >
          <h3 class="text-2xl font-bold text-gray-800 mt-2">No bookings yet</h3>
          <p class="text-gray-500 mt-2 mb-6">Book a service and it will appear here</p>
          <a
            href="home.php"
            class="w-full sm:w-auto inline-block bg-brand-500 hover:bg-brand-700 text-white font-bold px-8 py-3 rounded-xl transition-colors duration-200 shadow-md shadow-brand-500/25"
          >
            Browse Services
          </a>
        </div>

      <?php else: ?>

        <!-- Active booking cards -->
        <div class="space-y-4 pb-4">
          <?php foreach ($bookings as $booking):
              [$badge_bg, $badge_text, $badge_label] = status_badge_classes($booking['status']);
              $when = format_booking_datetime($booking['booking_date'], $booking['booking_time']);
          ?>
            <div class="bg-white rounded-2xl shadow-sm p-4">

              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="font-bold text-gray-900 truncate">
                    <?= htmlspecialchars($booking['vendor_name'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="text-gray-400 font-normal">&mdash;</span>
                    <?= htmlspecialchars($booking['service_name'], ENT_QUOTES, 'UTF-8') ?>
                  </p>
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
              </div>

              <div class="mt-4 flex items-center gap-3">
                <?php if ($booking['status'] === 'completed'): ?>
                  <?php if ($booking['review_id']): ?>
                    <span class="flex-1 text-center text-sm font-semibold bg-gray-100 text-gray-500 py-2.5 rounded-xl flex items-center justify-center gap-1.5">
                      <?= svg_icon('check', 'w-4 h-4') ?> Reviewed
                    </span>
                  <?php else: ?>
                    <a
                      href="review.php?booking_id=<?= urlencode($booking['booking_id']) ?>"
                      class="flex-1 text-center text-sm font-semibold bg-brand-500 hover:bg-brand-700 text-white py-2.5 rounded-xl transition-colors duration-200"
                    >
                      Rate Service
                    </a>
                  <?php endif; ?>
                <?php else: ?>
                  <a
                    href="tracking.php?booking_id=<?= urlencode($booking['booking_id']) ?>"
                    class="flex-1 text-center text-sm font-semibold bg-brand-500 hover:bg-brand-700 text-white py-2.5 rounded-xl transition-colors duration-200"
                  >
                    Track
                  </a>
                <?php endif; ?>
                <a
                  href="chat.php?vendor_id=<?= urlencode($booking['vendor_id']) ?>"
                  class="flex-1 text-center text-sm font-semibold border border-brand-500 text-brand-600 hover:bg-brand-50 py-2.5 rounded-xl transition-colors duration-200"
                >
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

<?php include '../includes/footer.php'; ?>
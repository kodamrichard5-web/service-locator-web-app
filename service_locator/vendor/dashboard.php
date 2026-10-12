<?php
/**
 * vendor/dashboard.php
 * Technician home page — matches the "Good morning" mockup layout.
 * Every value is queried live; there is no hardcoded technician anywhere.
 */

session_start();
require_once '../db.php';
require_once '../includes/functions.php';
require_vendor();

$vendor_id = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT v.*, s.name AS service_name, s.icon AS service_icon
     FROM vendors v JOIN services s ON s.id = v.service_id WHERE v.id = :id LIMIT 1"
);
$stmt->execute(['id' => $vendor_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    header('Location: ../logout.php');
    exit;
}

// Handle inline Accept / Decline directly from the dashboard.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['new_status'] ?? '';

    if ($booking_id && in_array($new_status, ['accepted', 'cancelled'], true)) {
        $check = $pdo->prepare("SELECT status FROM bookings WHERE id = :id AND vendor_id = :vid AND status = 'pending' LIMIT 1");
        $check->execute(['id' => $booking_id, 'vid' => $vendor_id]);
        if ($check->fetch()) {
            $update = $pdo->prepare('UPDATE bookings SET status = :status WHERE id = :id AND vendor_id = :vid');
            $update->execute(['status' => $new_status, 'id' => $booking_id, 'vid' => $vendor_id]);
        }
    }
    header('Location: dashboard.php');
    exit;
}

// Overview period selector (This Week / This Month / All Time) — affects the Earnings card only.
$period = $_GET['period'] ?? 'week';
if (!in_array($period, ['week', 'month', 'all'], true)) {
    $period = 'week';
}
$period_labels = ['week' => 'This week', 'month' => 'This month', 'all' => 'All time'];

$statusCounts = ['pending' => 0, 'accepted' => 0, 'in-progress' => 0, 'completed' => 0, 'cancelled' => 0];
$countStmt = $pdo->prepare("SELECT status, COUNT(*) AS c FROM bookings WHERE vendor_id = :id GROUP BY status");
$countStmt->execute(['id' => $vendor_id]);
foreach ($countStmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int) $row['c'];
}

$earningsSql = "SELECT COUNT(*) FROM bookings WHERE vendor_id = :id AND status = 'completed'";
if ($period === 'week') {
    $earningsSql .= " AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($period === 'month') {
    $earningsSql .= " AND YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())";
}
$earningsStmt = $pdo->prepare($earningsSql);
$earningsStmt->execute(['id' => $vendor_id]);
$completedInPeriod = (int) $earningsStmt->fetchColumn();
$earnings = $completedInPeriod * (float) $vendor['price'];

$completedTotalStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE vendor_id = :id AND status = 'completed'");
$completedTotalStmt->execute(['id' => $vendor_id]);
$completedTotal = (int) $completedTotalStmt->fetchColumn();

// Incoming (pending) job requests — newest first, top 3 shown here (matches the dashboard mockup).
$pendingStmt = $pdo->prepare(
    "SELECT b.id, b.booking_date, b.booking_time, b.address, b.notes, b.created_at,
            u.name AS customer_name, s.name AS service_name, s.icon AS service_icon
     FROM bookings b
     JOIN users u    ON u.id = b.customer_id
     JOIN services s ON s.id = b.service_id
     WHERE b.vendor_id = :id AND b.status = 'pending'
     ORDER BY b.created_at DESC
     LIMIT 3"
);
$pendingStmt->execute(['id' => $vendor_id]);
$pendingJobs = $pendingStmt->fetchAll();

$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE vendor_id = :id AND sender_role = 'customer' AND is_read = 0");
$unreadStmt->execute(['id' => $vendor_id]);
$unreadMessages = (int) $unreadStmt->fetchColumn();

$page_title = 'Dashboard';
$active_tab = 'dashboard';
include '../includes/header.php';
?>

<div class="min-h-screen pb-24 bg-gray-50">
  <div class="max-w-md mx-auto md:max-w-2xl lg:max-w-4xl">

    <!-- Header -->
    <div class="rounded-b-[2rem] px-5 pt-8 pb-8" style="background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
          <?php if (!empty($vendor['photo_url'])): ?>
            <img src="<?= htmlspecialchars($vendor['photo_url'], ENT_QUOTES, 'UTF-8') ?>" alt=""
                 class="w-16 h-16 rounded-full object-cover border-2 border-white/50 shrink-0">
          <?php else: ?>
            <div class="w-16 h-16 rounded-full bg-white/20 border-2 border-white/50 flex items-center justify-center text-white font-bold text-xl shrink-0">
              <?= htmlspecialchars(strtoupper(substr($vendor['name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>
          <div class="min-w-0">
            <p class="text-brand-50/90 text-sm"><?= time_of_day_greeting() ?>,</p>
            <h1 class="text-2xl font-extrabold text-white leading-tight truncate"><?= htmlspecialchars($vendor['name'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-brand-50/90 text-sm flex items-center gap-1.5 mt-0.5">
              <?= htmlspecialchars($vendor['service_name'], ENT_QUOTES, 'UTF-8') ?> Technician
              <?php if ((int) $vendor['is_verified'] === 1): ?>
                <span class="w-4 h-4 rounded-full bg-white text-brand-600 flex items-center justify-center shrink-0">
                  <?= svg_icon('check', 'w-2.5 h-2.5') ?>
                </span>
              <?php endif; ?>
            </p>
          </div>
        </div>
        <a href="messages.php" aria-label="Notifications" class="relative w-11 h-11 rounded-full bg-white/20 flex items-center justify-center shrink-0">
          <?= svg_icon('bell', 'w-5 h-5 text-white') ?>
          <?php if ($unreadMessages > 0): ?>
            <span class="absolute top-1 right-1.5 w-2.5 h-2.5 rounded-full bg-red-500 border-2 border-white"></span>
          <?php endif; ?>
        </a>
      </div>
    </div>

    <!-- Overview -->
    <div class="px-5 mt-6">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-bold text-gray-900">Your Overview</h2>
        <form method="GET" class="relative">
          <select name="period" onchange="this.form.submit()"
            class="appearance-none bg-transparent text-sm font-semibold text-brand-600 pr-5 focus:outline-none cursor-pointer">
            <?php foreach ($period_labels as $value => $label): ?>
              <option value="<?= $value ?>" <?= $period === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
          <span class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 text-brand-600">
            <?= svg_icon('chevron-down', 'w-4 h-4') ?>
          </span>
        </form>
      </div>

      <div class="grid grid-cols-3 gap-2.5 sm:gap-3">
        <div class="bg-brand-100/70 rounded-2xl p-3.5">
          <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shadow-sm">
            <?= svg_icon('clipboard', 'w-4 h-4 text-brand-700') ?>
          </span>
          <p class="text-[11px] text-gray-600 mt-2.5 leading-tight">New Requests</p>
          <p class="text-2xl font-extrabold text-brand-900 mt-0.5"><?= $statusCounts['pending'] ?></p>
        </div>
        <div class="bg-brand-100/70 rounded-2xl p-3.5">
          <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shadow-sm">
            <?= svg_icon('cash', 'w-4 h-4 text-brand-700') ?>
          </span>
          <p class="text-[11px] text-gray-600 mt-2.5 leading-tight"><?= $period_labels[$period] ?> Earnings</p>
          <p class="text-xl font-extrabold text-brand-900 mt-0.5"><?= format_ghs($earnings) ?></p>
        </div>
        <div class="bg-brand-100/70 rounded-2xl p-3.5">
          <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shadow-sm">
            <?= svg_icon('star', 'w-4 h-4 text-yellow-400') ?>
          </span>
          <p class="text-[11px] text-gray-600 mt-2.5 leading-tight">Rating</p>
          <p class="text-2xl font-extrabold text-brand-900 mt-0.5"><?= number_format((float) $vendor['rating'], 1) ?></p>
          <p class="text-[10px] text-gray-500 mt-0.5">
            <?= $completedTotal > 0 ? 'Based on ' . $completedTotal . ' job' . ($completedTotal === 1 ? '' : 's') : 'No jobs yet' ?>
          </p>
        </div>
      </div>
    </div>

    <!-- Incoming Job Requests -->
    <section class="mt-7 px-5 pb-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-bold text-gray-900">Incoming Job Requests</h3>
        <a href="bookings.php" class="text-sm font-semibold text-brand-600 flex items-center gap-1 hover:text-brand-700">
          See all (<?= $statusCounts['pending'] ?>) <span aria-hidden="true">&rarr;</span>
        </a>
      </div>

      <?php if (empty($pendingJobs)): ?>
        <div class="bg-brand-50 border border-dashed border-brand-200 rounded-2xl py-10 px-6 flex flex-col items-center text-center">
          <span class="w-14 h-14 rounded-full bg-white flex items-center justify-center mb-3 shadow-sm">
            <?= svg_icon('calendar', 'w-6 h-6 text-brand-600') ?>
          </span>
          <p class="text-sm font-semibold text-gray-700">No bookings yet</p>
          <p class="text-sm text-gray-500 mt-0.5">Complete your profile to start getting discovered by customers.</p>
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($pendingJobs as $job):
              $isNew = (time() - strtotime($job['created_at'])) < 3600;
          ?>
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                  <span class="w-11 h-11 rounded-full bg-brand-100 flex items-center justify-center shrink-0">
                    <?= svg_icon($job['service_icon'], 'w-5 h-5 text-brand-700') ?>
                  </span>
                  <div class="min-w-0">
                    <p class="font-bold text-gray-900 truncate"><?= htmlspecialchars($job['notes'] !== null && $job['notes'] !== '' ? $job['notes'] : $job['service_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-gray-500 truncate">for <?= htmlspecialchars($job['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                  </div>
                </div>
                <?php if ($isNew): ?>
                  <span class="shrink-0 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-700">New</span>
                <?php endif; ?>
              </div>

              <div class="mt-3 space-y-1.5">
                <p class="text-sm text-gray-600 flex items-center gap-1.5">
                  <?= svg_icon('pin', 'w-4 h-4 text-brand-600 shrink-0') ?>
                  <span class="truncate"><?= htmlspecialchars($job['address'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="text-gray-300">&bull;</span>
                  <span class="shrink-0"><?= time_ago($job['created_at']) ?></span>
                </p>
                <p class="text-sm text-gray-600 flex items-center gap-1.5">
                  <?= svg_icon('cash', 'w-4 h-4 text-brand-600 shrink-0') ?>
                  Est. Payout: <span class="font-semibold text-brand-700"><?= format_ghs((float) $vendor['price']) ?></span>
                </p>
              </div>

              <div class="mt-4 flex items-center gap-3">
                <form action="" method="POST" class="flex-1">
                  <input type="hidden" name="booking_id" value="<?= (int) $job['id'] ?>">
                  <input type="hidden" name="new_status" value="accepted">
                  <button type="submit" name="update_status"
                    class="w-full text-center text-sm font-semibold bg-brand-500 hover:bg-brand-700 text-white py-2.5 rounded-xl transition-colors duration-200">
                    Accept
                  </button>
                </form>
                <form action="" method="POST" class="flex-1">
                  <input type="hidden" name="booking_id" value="<?= (int) $job['id'] ?>">
                  <input type="hidden" name="new_status" value="cancelled">
                  <button type="submit" name="update_status"
                    class="w-full text-center text-sm font-semibold border border-brand-500 text-brand-600 hover:bg-brand-50 py-2.5 rounded-xl transition-colors duration-200">
                    Decline
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  </div>
</div>

<?php include '../includes/vendor_footer.php'; ?>